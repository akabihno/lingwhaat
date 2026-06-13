<?php

namespace App;

use App\Message\ManuscriptAlphabetDecodeSelectionDispatchMessage;
use App\Message\ManuscriptLanguageScoreDispatchMessage;
use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Message\ParseWikipediaLanguagesMessage;
use App\Message\ParseWiktionaryLanguagesMessage;
use App\Message\WikipediaPatternIndexDispatchMessage;
use App\Message\WordsPopularityScoreSetDispatchMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    private const int JITTER_SECONDS = 30;

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        $schedule = (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true);

        $schedule->add(
            RecurringMessage::every('5 minutes', new ParseWiktionaryLanguagesMessage())
                ->withJitter(self::JITTER_SECONDS)
        );

        $schedule->add(
            RecurringMessage::every('1 minute', new ParseWikipediaLanguagesMessage())
                ->withJitter(self::JITTER_SECONDS)
        );

        $schedule->add(
            RecurringMessage::every('10 minutes', new WordsPopularityScoreSetDispatchMessage())
                ->withJitter(self::JITTER_SECONDS)
        );

        // Dispatch is deduped per language (see WikipediaPatternIndexDispatchMessageHandler),
        // so a tick only enqueues languages that aren't already pending/in-flight. 5 minutes
        // roughly matches a full pass over all languages given current worker throughput.
        $schedule->add(
            RecurringMessage::every('5 minutes', new WikipediaPatternIndexDispatchMessage())
                ->withJitter(self::JITTER_SECONDS)
        );

        $schedule->add(
            RecurringMessage::every('10 minutes', new ManuscriptLanguageScoreDispatchMessage())
                ->withJitter(self::JITTER_SECONDS)
        );

        $schedule->add(
            RecurringMessage::every('5 minutes', new ManuscriptAlphabetDecodeSelectionDispatchMessage())
                ->withJitter(self::JITTER_SECONDS)
        );

        $schedule->add(
            RecurringMessage::every('5 minutes', new ManuscriptPatternMatchSearchMessage())
                ->withJitter(self::JITTER_SECONDS)
        );

        return $schedule;
    }
}
