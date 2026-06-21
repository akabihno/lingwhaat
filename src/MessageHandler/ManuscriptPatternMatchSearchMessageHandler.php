<?php

namespace App\MessageHandler;

use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Repository\ManuscriptPatternMatchRepository;
use App\Repository\ManuscriptPatternMatchResultRepository;
use App\Repository\ManuscriptPatternMatchScheduleRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\WikipediaPatternSearchService;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ManuscriptPatternMatchSearchMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptPatternMatchSearchMessageHandler]';
    private const int RESULTS_PER_WINDOW = 5;
    private const int MAX_TOTAL_HITS = 400;
    private const string LOCK_RESOURCE_PREFIX = 'manuscript-pattern-search-language:';
    // Safety net: if a worker dies mid-search without releasing the lock, this is the maximum
    // time before another search for the same language can run. Normal runs release explicitly.
    private const int LOCK_TTL_SECONDS = 900;

    public function __construct(
        private readonly ManuscriptPatternMatchScheduleRepository $scheduleRepository,
        private readonly ManuscriptPatternMatchRepository $matchRepository,
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly WikipediaPatternSearchService $searchService,
        private readonly LockFactory $lockFactory,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptPatternMatchSearchMessage $message): void
    {
        $languageCode = $message->getLanguageCode();

        // Per-language (or 'all' for the cross-language sweep) dedup lock. With multiple async
        // workers and frequent dispatches, duplicate search messages for the same language pile
        // up; running the heavy ES sweep more than once concurrently is pure waste. If a search
        // for this language is already in flight, ack this duplicate and move on.
        $lock = $this->lockFactory->createLock(
            self::LOCK_RESOURCE_PREFIX . ($languageCode ?? 'all'),
            self::LOCK_TTL_SECONDS,
            false,
        );

        if (!$lock->acquire()) {
            $this->logger->info(sprintf('Search for %s already in flight — skipping duplicate', $languageCode ?? 'all'), [
                'service' => self::LOG_SERVICE,
                'languageCode' => $languageCode,
            ]);
            return;
        }

        try {
            $this->runSearch($languageCode);
        } finally {
            try {
                $lock->release();
            } catch (LockReleasingException) {
                // Lock already expired or taken over; nothing to release.
            }
        }
    }

    private function runSearch(?string $languageCode): void
    {
        $schedules = $this->scheduleRepository->getAll();
        $this->logger->info(sprintf('Processing %d manuscript schedules against language=%s', count($schedules), $languageCode ?? 'all'), [
            'service' => self::LOG_SERVICE,
            'languageCode' => $languageCode,
        ]);

        foreach ($schedules as $schedule) {
            $matches = $this->matchRepository->findBySourceId($schedule->getId());
            $this->logger->info(sprintf('Schedule "%s" (id: %d): found %d matches', $schedule->getManuscriptName(), $schedule->getId(), count($matches)), [
                'service' => self::LOG_SERVICE,
            ]);

            foreach ($matches as $match) {
                $normalized = $this->normalize($match->getSourceData());
                $textLength = mb_strlen($normalized);
                $windowSize = WikipediaPatternSearchService::DEFAULT_WINDOW_SIZE;

                if ($textLength < $windowSize) {
                    $this->logger->info(sprintf('Skipping match id=%d: normalized length %d < window size %d', $match->getId(), $textLength, $windowSize), [
                        'service' => self::LOG_SERVICE,
                    ]);
                    continue;
                }

                $allHits = [];
                $windowCount = $textLength - $windowSize + 1;

                for ($pos = 0; $pos <= $textLength - $windowSize; $pos++) {
                    $window = mb_substr($normalized, $pos, $windowSize);

                    try {
                        $windowHits = $this->searchService->search($window, self::RESULTS_PER_WINDOW, WikipediaPatternSearchService::DEFAULT_WINDOW_SIZE, $languageCode);
                    } catch (\InvalidArgumentException) {
                        continue;
                    }

                    foreach ($windowHits as $hit) {
                        $hit['cipher_position'] = $pos;
                        $hit['cipher_window'] = $window;
                        $allHits[] = $hit;
                    }

                    if (count($allHits) >= self::MAX_TOTAL_HITS) {
                        break;
                    }
                }

                $this->logger->info(sprintf('Match id=%d: found %d hits across %d windows', $match->getId(), count($allHits), $windowCount), [
                    'service' => self::LOG_SERVICE,
                ]);

                if (empty($allHits)) {
                    continue;
                }

                try {
                    $this->resultRepository->insert($match->getId(), $schedule->getId(), json_encode($allHits, JSON_THROW_ON_ERROR));
                    $this->logger->info(sprintf('Match id=%d: upsert complete', $match->getId()), ['service' => self::LOG_SERVICE]);
                } catch (\Throwable $e) {
                    $this->logger->error(sprintf('Match id=%d: upsert failed: %s', $match->getId(), $e->getMessage()), ['service' => self::LOG_SERVICE]);
                }
            }
        }

        $this->logger->info('Manuscript pattern search complete', ['service' => self::LOG_SERVICE]);
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }
}
