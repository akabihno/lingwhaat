<?php

namespace App\Service;

use App\Entity\WikipediaArticleEntity;
use App\Exception\WikipediaArticleLimitExceededException;
use App\Repository\WikipediaArticleRepository;
use App\Service\Metrics\MetricName;
use App\Service\Metrics\PrometheusMetricsService;
use Doctrine\ORM\EntityManagerInterface;

class WikipediaPatternParserService extends AbstractWikiParserService
{
    private const int ARTICLE_LIMIT = 300000;
    private const int FLUSH_BATCH_SIZE = 10;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PrometheusMetricsService $metrics,
    )
    {
    }

    public function run(string $languageCode, int $limit): array
    {
        if (!$languageCode) {
            throw new \InvalidArgumentException('languageCode must be provided.');
        }

        if ($limit <= 0) {
            throw new \InvalidArgumentException('limit must be greater than 0.');
        }

        /** @var WikipediaArticleRepository $repo */
        $repo = $this->entityManager->getRepository(WikipediaArticleEntity::class);
        $existingCount = $repo->countByLanguageCode($languageCode);
        if ($existingCount > self::ARTICLE_LIMIT) {
            throw new WikipediaArticleLimitExceededException($languageCode, $existingCount, self::ARTICLE_LIMIT);
        }

        $titles = $this->wikiGetRandomTitles($languageCode, 'wikipedia', $limit);
        if (!$titles) {
            echo "Could not fetch random titles.\n";

            return [];
        }

        $pending = 0;

        foreach ($titles as $title) {
            $link = $this->buildArticleLink($languageCode, $title);

            // Titles come from list=random, so a run keeps drawing articles we already hold — and
            // for every language whose Wikipedia is smaller than ARTICLE_LIMIT the ceiling is never
            // reached, so that would go on forever. There is no unique constraint on
            // (language_code, wikipedia_link) to lean on, so check before spending a fetch on it.
            if ($repo->existsByLanguageCodeAndLink($languageCode, $link)) {
                echo "Skipping already stored article: $title\n";
                continue;
            }

            echo "Fetching article: $title\n";
            $rawHtml = $this->wikiGetRequest($title, $languageCode, 'wikipedia');
            if (!$rawHtml) {
                continue;
            }

            $cleanText = $this->sanitizeWikipediaHtml($rawHtml);

            if (empty($cleanText)) {
                continue;
            }

            $entity = new WikipediaArticleEntity();
            $entity->setLanguageCode($languageCode);
            $entity->setWikipediaLink($link);
            $entity->setText($cleanText);
            $entity->setTsCreated(date('Y-m-d H:i:s'));

            $this->entityManager->persist($entity);
            $pending++;

            if ($pending === self::FLUSH_BATCH_SIZE) {
                $this->flushBatch($languageCode, $pending);
                $pending = 0;
            }
        }

        $this->flushBatch($languageCode, $pending);

        return [];
    }

    /**
     * Commit the pending inserts, and only once they are committed count them. Counting at
     * persist() time would credit articles that a failing flush never wrote — and since the handler
     * rethrows and Messenger retries the message, those would then be counted a second time.
     */
    private function flushBatch(string $languageCode, int $pending): void
    {
        if ($pending === 0) {
            return;
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->metrics
            ->counter(
                MetricName::WIKIPEDIA_ARTICLES_FETCHED_TOTAL,
                'Total Wikipedia articles stored in DB by parse-wikipedia-articles pipeline.',
                ['language'],
            )
            ->incBy($pending, [$languageCode]);
    }

    private function buildArticleLink(string $languageCode, string $title): string
    {
        return "https://$languageCode.wikipedia.org/wiki/" . str_replace(' ', '_', $title);
    }

    private function sanitizeWikipediaHtml(string $html): string
    {
        $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $clean = preg_replace('/\.(mw-parser-output|[a-z0-9\-_]+)\s*{[^}]+}(\s*)?/is', '', $clean);
        $clean = preg_replace('/<!--.*?-->/s', '', $clean);
        $clean = preg_replace('/<table\b.*?<\/table>/is', '', $clean);
        $clean = preg_replace('/<div class="catlinks".*?<\/div>/is', '', $clean);
        $clean = preg_replace('/<sup.*?<\/sup>/is', '', $clean);

        $clean = preg_replace_callback("/<a [^>]+>(.*?)<\/a>/is", function ($m) {
            return $m[1];
        }, $clean);

        $clean = strip_tags($clean);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = preg_replace('/\s{2,}/', ' ', $clean);

        return trim($clean);
    }


}
