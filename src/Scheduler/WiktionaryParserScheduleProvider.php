<?php

namespace App\Scheduler;

use App\Message\ParseWiktionaryArticlesMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('wiktionary_parser')]
class WiktionaryParserScheduleProvider implements ScheduleProviderInterface
{
    public function __construct(
        private readonly CacheInterface $cache
    ) {
    }

    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        $schedule->add(
            RecurringMessage::every(
                '10 minutes',
                new ParseWiktionaryArticlesMessage('dutch', 300)
            )->withJitter(30) // Add 30 seconds jitter to avoid exact timing conflicts
        );

        return $schedule;
    }
}
