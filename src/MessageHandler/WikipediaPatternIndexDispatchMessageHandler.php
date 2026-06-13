<?php

namespace App\MessageHandler;

use App\Message\WikipediaPatternIndexDispatchMessage;
use App\Message\WikipediaPatternIndexLanguageMessage;
use App\Repository\WikipediaArticleRepository;
use App\Repository\WikipediaPatternIndexOffsetRepository;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class WikipediaPatternIndexDispatchMessageHandler
{
    private const string LOG_SERVICE = '[WikipediaPatternIndexDispatchMessageHandler]';
    // Re-kick a language whose self-chain has been idle for this long. Covers dead workers and
    // messages that exhausted their retry budget without clearing lastRunAt. Should comfortably
    // exceed the worst-case time between a run's start and the next self-dispatch (i.e. the
    // maximum batch duration plus queue latency).
    private const int MAX_IDLE_SECONDS = 600;

    public function __construct(
        private readonly WikipediaArticleRepository $articleRepository,
        private readonly WikipediaPatternIndexOffsetRepository $offsetRepository,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(WikipediaPatternIndexDispatchMessage $message): void
    {
        $this->logger->info('Starting Wikipedia pattern index dispatch', [
            'service' => self::LOG_SERVICE,
            'windowSize' => $message->getWindowSize(),
            'defaultArticleLimit' => $message->getArticleLimit(),
        ]);

        $languageCodes = $this->articleRepository->getDistinctLanguageCodes();
        $offsetEntities = $this->offsetRepository->findAllKeyedByLanguageCode();

        // Sort least-processed first so recovering languages get priority.
        usort($languageCodes, function (string $a, string $b) use ($offsetEntities): int {
            $oa = ($offsetEntities[$a] ?? null)?->getLastArticleId() ?? 0;
            $ob = ($offsetEntities[$b] ?? null)?->getLastArticleId() ?? 0;
            return $oa === $ob ? strcmp($a, $b) : $oa <=> $ob;
        });

        $idleThreshold = new \DateTimeImmutable('-' . self::MAX_IDLE_SECONDS . ' seconds');

        $dispatched = 0;
        $skipped = [];
        foreach ($languageCodes as $languageCode) {
            $entity = $offsetEntities[$languageCode] ?? null;
            $lastRunAt = $entity?->getLastRunAt();

            // Skip languages whose self-chain is still active (ran recently enough).
            if ($lastRunAt !== null && $lastRunAt > $idleThreshold) {
                $skipped[] = $languageCode;
                continue;
            }

            // Dead chain or never started — use the stored limit so recovery picks up where the
            // adaptive algorithm left off, falling back to the message default for new languages.
            $articleLimit = $entity?->getNextArticleLimit() ?? $message->getArticleLimit();

            $this->bus->dispatch(new WikipediaPatternIndexLanguageMessage(
                $languageCode,
                $message->getWindowSize(),
                $articleLimit,
            ));
            ++$dispatched;
        }

        $this->logger->info(sprintf('Dispatched %d languages, skipped %d with recent activity', $dispatched, count($skipped)), [
            'service' => self::LOG_SERVICE,
            'skippedLanguageCodes' => $skipped,
        ]);
    }
}
