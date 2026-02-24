<?php

namespace App;

use App\Message\ParseWiktionaryArticlesMessage;
use App\Message\ParseWikipediaArticlesMessage;
use App\Message\WordsPopularityScoreSetMessage;
use App\Repository\LanguageParseScheduleRepository;
use App\Repository\WikipediaPatternParseScheduleRepository;
use App\Repository\WordsPopularityScoreSetScheduleRepository;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    private const int WORDS_POPULARITY_ARTICLE_LIMIT = 10;
    private const int JITTER_SECONDS = 30;

    public function __construct(
        private CacheInterface $cache,
        private LanguageParseScheduleRepository $languageParseScheduleRepository,
        private WikipediaPatternParseScheduleRepository $wikipediaPatternParseScheduleRepository,
        private WordsPopularityScoreSetScheduleRepository $wordsPopularityScoreSetScheduleRepository,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        $schedule = (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true);

        $languages = $this->languageParseScheduleRepository->getAll();

        foreach ($languages as $language) {
            $schedule->add(
                RecurringMessage::every(
                    '5 minutes',
                    new ParseWiktionaryArticlesMessage($language->getLanguageName(), 400)
                )->withJitter(self::JITTER_SECONDS)
            );
        }

        $wikipediaLanguages = $this->wikipediaPatternParseScheduleRepository->getAll();

        foreach ($wikipediaLanguages as $language) {
            $schedule->add(
                RecurringMessage::every(
                    '1 minute',
                    new ParseWikipediaArticlesMessage($language->getLanguageCode())
                )->withJitter(self::JITTER_SECONDS)
            );
        }

        $entities = $this->wordsPopularityScoreSetScheduleRepository->getAll();

        foreach ($entities as $entity) {
            $schedule->add(
                RecurringMessage::every(
                    '5 minutes',
                    new WordsPopularityScoreSetMessage(
                        $entity->getLanguageCode(),
                        self::WORDS_POPULARITY_ARTICLE_LIMIT,
                        $entity->getOffset()
                    )
                )->withJitter(self::JITTER_SECONDS)
            );
            $this->wordsPopularityScoreSetScheduleRepository->incrementOffsetByLanguageCode(
                $entity->getLanguageCode(),
                self::WORDS_POPULARITY_ARTICLE_LIMIT
            );
        }

        return $schedule;
    }
}
