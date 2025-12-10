<?php

namespace App;

use App\Message\ParseWiktionaryArticlesMessage;
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
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache) // ensure missed tasks are executed
            ->processOnlyLastMissedRun(true) // ensure only last missed task is run

            // Parse Wiktionary articles for Dutch every 10 minutes
            ->add(
                RecurringMessage::every(
                    '5 minutes',
                    new ParseWiktionaryArticlesMessage('dutch', 300)
                )->withJitter(30) // Add 30 seconds jitter to avoid exact timing conflicts
            )
        ;
    }
}
