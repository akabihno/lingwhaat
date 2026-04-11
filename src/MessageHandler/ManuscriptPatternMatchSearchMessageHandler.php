<?php

namespace App\MessageHandler;

use App\Entity\ManuscriptPatternMatchResultEntity;
use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Repository\ManuscriptPatternMatchRepository;
use App\Repository\ManuscriptPatternMatchResultRepository;
use App\Repository\ManuscriptPatternMatchScheduleRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\WikipediaPatternSearchService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ManuscriptPatternMatchSearchMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptPatternMatchSearchMessageHandler]';

    public function __construct(
        private readonly ManuscriptPatternMatchScheduleRepository $scheduleRepository,
        private readonly ManuscriptPatternMatchRepository $matchRepository,
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly WikipediaPatternSearchService $searchService,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptPatternMatchSearchMessage $message): void
    {
        $schedules = $this->scheduleRepository->getAll();
        $this->logger->info(sprintf('Processing %d manuscript schedules', count($schedules)), [
            'service' => self::LOG_SERVICE,
        ]);

        foreach ($schedules as $schedule) {
            $matches = $this->matchRepository->findBySourceId($schedule->getId());
            $this->logger->info(sprintf('Schedule "%s" (id: %d): found %d matches', $schedule->getManuscriptName(), $schedule->getId(), count($matches)), [
                'service' => self::LOG_SERVICE,
            ]);

            foreach ($matches as $match) {
                $normalized = $this->normalize($match->getSourceData());
                $normalizedLength = mb_strlen($normalized);

                if ($normalizedLength !== WikipediaPatternSearchService::DEFAULT_WINDOW_SIZE) {
                    $this->logger->info(sprintf('Skipping match id=%d: normalized length %d != window size %d', $match->getId(), $normalizedLength, WikipediaPatternSearchService::DEFAULT_WINDOW_SIZE), [
                        'service' => self::LOG_SERVICE,
                    ]);
                    continue;
                }

                try {
                    $results = $this->searchService->search($match->getSourceData(), 50);
                } catch (\InvalidArgumentException $e) {
                    $this->logger->warning(sprintf('Search failed for match id=%d: %s', $match->getId(), $e->getMessage()), [
                        'service' => self::LOG_SERVICE,
                    ]);
                    continue;
                }

                $this->logger->info(sprintf('Match id=%d: found %d results', $match->getId(), count($results)), [
                    'service' => self::LOG_SERVICE,
                ]);

                $this->resultRepository->upsert($match->getId(), $schedule->getId(), json_encode($results, JSON_THROW_ON_ERROR));
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
