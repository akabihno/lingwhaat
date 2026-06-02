<?php

namespace App\MessageHandler;

use App\Message\ManuscriptLanguageScoreDispatchMessage;
use App\Message\ManuscriptLanguageScoreMessage;
use App\Repository\ManuscriptPatternMatchResultRepository;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ManuscriptLanguageScoreDispatchMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptLanguageScoreDispatchMessageHandler]';
    private const string LOCK_RESOURCE = 'manuscript-language-score-dispatch';
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

    public function __invoke(ManuscriptLanguageScoreDispatchMessage $message): void
    {
        $lock = $this->lockFactory->createLock(self::LOCK_RESOURCE, self::LOCK_TTL_SECONDS, false);

        if (!$lock->acquire()) {
            $this->logger->info('Previous score dispatch still in flight — skipping this tick', [
                'service' => self::LOG_SERVICE,
            ]);
            return;
        }

        $unscored = $this->resultRepository->findUnscored();

        $unscored
            |> count(...)
            |> (fn($x) => sprintf('Dispatching language score for %d unscored results', $x))
            |> (fn($x) => $this->logger->info($x, ['service' => self::LOG_SERVICE,]));

        foreach ($unscored as $result) {
            $this->bus->dispatch(new ManuscriptLanguageScoreMessage($result->getId()));
        }

        // Lock intentionally NOT released here. The dispatch handler returns in seconds, but the
        // spawned per-row messages take minutes to drain. Holding the lock for its full TTL is what
        // prevents the next scheduler tick from re-fanning-out the same rows. The TTL acts as the
        // batch-completion safety net; the lock self-clears when it expires.
    }
}
