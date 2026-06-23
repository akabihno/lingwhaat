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
    // Cap how many unscored results each tick fans out, so a large backlog drains in bounded
    // batches across scheduler ticks instead of flooding the single score worker at once.
    private const int DISPATCH_BATCH_SIZE = 5;
    // TTL covers expected end-to-end batch time. If the spawned per-row work isn't done within
    // this window the next tick may re-queue duplicates — handler is idempotent so that's wasted
    // CPU, not corruption. If the lock holder dies, ticks resume after TTL.
    private const int LOCK_TTL_SECONDS = 300;

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
