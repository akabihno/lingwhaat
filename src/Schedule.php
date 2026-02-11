<?php

namespace App;

use App\Message\ParseWiktionaryArticlesMessage;
use App\Message\ParseWikipediaArticlesMessage;
use App\Repository\LanguageParseScheduleRepository;
use App\Repository\WikipediaPatternParseScheduleRepository;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
        private LanguageParseScheduleRepository $languageParseScheduleRepository,
        private WikipediaPatternParseScheduleRepository $wikipediaPatternParseScheduleRepository,
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
                )->withJitter(30)
            );
        }

        $wikipediaLanguages = $this->wikipediaPatternParseScheduleRepository->getAll();

        foreach ($wikipediaLanguages as $language) {
            $schedule->add(
                RecurringMessage::every(
                    '1 minute',
                    new ParseWikipediaArticlesMessage($language->getLanguageCode())
                )->withJitter(30)
            );
        }

        return $schedule;
    }
}
