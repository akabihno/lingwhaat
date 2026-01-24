<?php

namespace App\Service;

use App\Entity\WikipediaArticleEntity;
use Doctrine\ORM\EntityManagerInterface;

class WikipediaPatternParserService extends AbstractWikiParserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function run(string $languageCode, int $limit): array
    {
        for ($i = 0; $i < $limit; $i++) {
            $randomTitle = $this->wikiGetRandomTitle($languageCode, 'wikipedia');

            if ($randomTitle) {
                echo "Fetching article: $randomTitle\n";
                $rawHtml = $this->wikiGetRequest($randomTitle, $languageCode, 'wikipedia');
                if (!$rawHtml) {
                    continue;
                }

                $cleanText = $this->sanitizeWikipediaHtml($rawHtml);

                if (empty($cleanText)) {
                    continue;
                }

                $entity = new WikipediaArticleEntity();
                $entity->setLanguageCode($languageCode);
                $entity->setWikipediaLink("https://$languageCode.wikipedia.org/wiki/" . str_replace(' ', '_', $randomTitle));
                $entity->setText($cleanText);
                $entity->setTsCreated(date('Y-m-d H:i:s'));

                $this->entityManager->persist($entity);

                if (($i + 1) % 10 === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            } else {
                echo "Could not fetch a random title.\n";
            }
        }
        $this->entityManager->flush();

        return [];
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