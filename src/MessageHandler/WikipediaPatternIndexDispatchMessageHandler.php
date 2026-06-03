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

        foreach ($languageCodes as $languageCode) {
            $this->bus->dispatch(new WikipediaPatternIndexLanguageMessage(
                $languageCode,
                $message->getWindowSize(),
                $message->getArticleLimit(),
            ));
        }

        // No central search dispatch here. Each WikipediaPatternIndexLanguageMessageHandler that
        // actually does work (i.e. acquires its per-language lock) dispatches its own
        // ManuscriptPatternMatchSearchMessage with that language code attached.
    }
}
