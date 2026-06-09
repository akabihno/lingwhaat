<?php

namespace App\MessageHandler;

use App\Message\WikipediaPatternIndexDispatchMessage;
use App\Message\WikipediaPatternIndexLanguageMessage;
use App\Repository\WikipediaArticleRepository;
use App\Repository\WikipediaPatternIndexOffsetRepository;
use App\Service\Cache\RedisCacheService;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class WikipediaPatternIndexDispatchMessageHandler
{
    private const string LOG_SERVICE = '[WikipediaPatternIndexDispatchMessageHandler]';
    // Safety-net lifetime for a language's "pending" marker. Cleared explicitly on completion, so
    // this only governs recovery when a worker dies without clearing it. Sized at one dispatch
    // interval (5 min): comfortably above the real in-flight time — a 5-article keyset batch now
    // runs in ~seconds plus brief queue wait — while letting a dead worker's language re-dispatch
    // on the next tick instead of stalling for many ticks. Re-dispatching a still-in-flight language
    // is harmless: the per-language lock dedups it into a no-op.
    private const int PENDING_MARKER_TTL_SECONDS = 300;

    public function __construct(
        private readonly WikipediaArticleRepository $articleRepository,
        private readonly WikipediaPatternIndexOffsetRepository $offsetRepository,
        private readonly MessageBusInterface $bus,
        private readonly RedisCacheService $cache,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(WikipediaPatternIndexDispatchMessage $message): void
    {
        $this->logger->info('Starting Wikipedia pattern index dispatch', [
            'service' => self::LOG_SERVICE,
            'windowSize' => $message->getWindowSize(),
            'articleLimit' => $message->getArticleLimit(),
        ]);

        $languageCodes = $this->articleRepository->getDistinctLanguageCodes();
        $offsetsByLanguage = $this->offsetRepository->getOffsetsByLanguageCode();

        // Sort least-processed first: languages never indexed (no offset row) get offset=0 and
        // sort to the top. Tie-break alphabetically for determinism.
        usort($languageCodes, function (string $a, string $b) use ($offsetsByLanguage): int {
            $oa = $offsetsByLanguage[$a] ?? 0;
            $ob = $offsetsByLanguage[$b] ?? 0;
            return $oa === $ob ? strcmp($a, $b) : $oa <=> $ob;
        });

        $this->logger->info(sprintf('Fanning out indexing for %d languages (lowest-offset first)', count($languageCodes)), [
            'service' => self::LOG_SERVICE,
            'languageCodes' => $languageCodes,
        ]);

        // Dedup: only enqueue a language that has no message already pending or in-flight.
        // The handler clears the marker once the batch completes, so the next tick picks it
        // up again. This caps the transport at one message per language and stops the queue
        // from snowballing when batches take longer than the dispatch interval.
        $dispatched = 0;
        $skipped = [];
        foreach ($languageCodes as $languageCode) {
            $markerKey = WikipediaPatternIndexLanguageMessage::pendingMarkerKey($languageCode);
            if (!$this->cache->acquirePendingMarker($markerKey, self::PENDING_MARKER_TTL_SECONDS)) {
                $skipped[] = $languageCode;
                continue;
            }

            $this->bus->dispatch(new WikipediaPatternIndexLanguageMessage(
                $languageCode,
                $message->getWindowSize(),
                $message->getArticleLimit(),
            ));
            ++$dispatched;
        }

        $this->logger->info(sprintf('Dispatched %d languages, skipped %d already in flight', $dispatched, count($skipped)), [
            'service' => self::LOG_SERVICE,
            'skippedLanguageCodes' => $skipped,
        ]);

        // No central search dispatch here. Each WikipediaPatternIndexLanguageMessageHandler that
        // actually does work (i.e. acquires its per-language lock) dispatches its own
        // ManuscriptPatternMatchSearchMessage with that language code attached.
    }
}
