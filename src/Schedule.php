<?php

namespace App;

use App\Message\ParseWiktionaryArticlesMessage;
use App\Repository\LanguageParseScheduleRepository;
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
                    new ParseWiktionaryArticlesMessage($language->getLanguageName(), 300)
                )->withJitter(30)
            );
        }

        return $schedule;
    }
}
