<?php

namespace App\MessageHandler;

use App\Message\WordsPopularityScoreSetDispatchMessage;
use App\Message\WordsPopularityScoreSetMessage;
use App\Repository\WikipediaArticleRepository;
use App\Repository\WordsPopularityScoreSetOffsetRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class WordsPopularityScoreSetDispatchMessageHandler
{
    private const int ARTICLE_LIMIT = 10;

    public function __construct(
        private readonly WikipediaArticleRepository $articleRepository,
        private readonly WordsPopularityScoreSetOffsetRepository $offsetRepository,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(WordsPopularityScoreSetDispatchMessage $message): void
    {
        // Languages are taken straight from the corpus (busiest first) rather than a separate
        // schedule table, so any language with articles is covered automatically.
        foreach ($this->articleRepository->getLanguageCodesByArticleCountDesc() as $languageCode) {
            // Offset lives in its own table (words_popularity_score_set_offset), keyed by language.
            // Ensure a row exists so the per-batch increment (a bulk UPDATE) has something to advance.
            $offset = $this->offsetRepository->getOrCreate($languageCode)->getOffset();

            $this->bus->dispatch(
                new WordsPopularityScoreSetMessage(
                    $languageCode,
                    self::ARTICLE_LIMIT,
                    $offset
                )
            );
        }
    }
}
