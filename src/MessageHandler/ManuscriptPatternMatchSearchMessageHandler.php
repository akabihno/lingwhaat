<?php

namespace App\MessageHandler;

use App\Entity\ManuscriptMatch\ManuscriptPatternMatchResultEntity;
use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Repository\ManuscriptMatch\ManuscriptPatternMatchRepository;
use App\Repository\ManuscriptMatch\ManuscriptPatternMatchResultRepository;
use App\Repository\ManuscriptMatch\ManuscriptPatternMatchScheduleRepository;
use App\Service\Search\WikipediaPatternSearchService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ManuscriptPatternMatchSearchMessageHandler
{
    public function __construct(
        private readonly ManuscriptPatternMatchScheduleRepository $scheduleRepository,
        private readonly ManuscriptPatternMatchRepository $matchRepository,
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly WikipediaPatternSearchService $searchService,
    ) {
    }

    public function __invoke(ManuscriptPatternMatchSearchMessage $message): void
    {
        foreach ($this->scheduleRepository->getAll() as $schedule) {
            $matches = $this->matchRepository->findBySourceId($schedule->getId());

            foreach ($matches as $match) {
                $normalized = $this->normalize($match->getSourceData());
                $windowSize = mb_strlen($normalized);

                if ($windowSize === 0) {
                    continue;
                }

                try {
                    $results = $this->searchService->search($match->getSourceData(), 50, $windowSize);
                } catch (\InvalidArgumentException) {
                    continue;
                }

                $result = (new ManuscriptPatternMatchResultEntity())
                    ->setMatchId($match->getId())
                    ->setResults(json_encode($results, JSON_THROW_ON_ERROR))
                    ->setTsCreated((new \DateTimeImmutable())->format('Y-m-d H:i:s'));

                $this->resultRepository->save($result);
            }
        }
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }
}
