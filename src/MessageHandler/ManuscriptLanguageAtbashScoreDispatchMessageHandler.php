<?php

namespace App\MessageHandler;

use App\Message\ManuscriptLanguageAtbashScoreDispatchMessage;
use App\Message\ManuscriptLanguageAtbashScoreMessage;
use App\Repository\ManuscriptPatternMatchResultRepository;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ManuscriptLanguageAtbashScoreDispatchMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptLanguageAtbashScoreDispatchMessageHandler]';
    private const string LOCK_RESOURCE = 'manuscript-language-atbash-score-dispatch';
    // Cap how many unscored results each tick fans out. Matched to the number of atbash score
    // worker pods so one tick hands every worker exactly one row and the whole batch drains in a
    // single round within LOCK_TTL_SECONDS — no in-flight row is still NULL when the lock frees, so
    // the next tick never re-fans rows that are already being scored.
    private const int DISPATCH_BATCH_SIZE = 2;
    // Must exceed the real end-to-end time for one batch. Per-row score() is ES-heavy and measured
    // at ~12-30 min, so the previous 300s TTL expired mid-batch and let the 5-min scheduler
    // re-queue the same in-flight rows as duplicates. 1800s comfortably covers a worst-case round.
    // Re-dispatch is only wasted CPU (handler is idempotent), never corruption; if the lock holder
    // dies, ticks resume after TTL.
    private const int LOCK_TTL_SECONDS = 1800;

    public function __construct(
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly MessageBusInterface $bus,
        private readonly LockFactory $lockFactory,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptLanguageAtbashScoreDispatchMessage $message): void
    {
        $lock = $this->lockFactory->createLock(self::LOCK_RESOURCE, self::LOCK_TTL_SECONDS, false);

        if (!$lock->acquire()) {
            $this->logger->info('Previous atbash score dispatch still in flight — skipping this tick', [
                'service' => self::LOG_SERVICE,
            ]);
            return;
        }

        $unscored = $this->resultRepository->findUnscoredAtbash(self::DISPATCH_BATCH_SIZE);

        $unscored
            |> count(...)
            |> (fn($x) => sprintf('Dispatching atbash language score for %d unscored results', $x))
            |> (fn($x) => $this->logger->info($x, ['service' => self::LOG_SERVICE,]));

        foreach ($unscored as $result) {
            $this->bus->dispatch(new ManuscriptLanguageAtbashScoreMessage($result->getId()));
        }

        // Lock intentionally NOT released here. The dispatch handler returns in seconds, but the
        // spawned per-row messages take minutes to drain. Holding the lock for its full TTL is what
        // prevents the next scheduler tick from re-fanning-out the same rows. The TTL acts as the
        // batch-completion safety net; the lock self-clears when it expires.
    }
}
