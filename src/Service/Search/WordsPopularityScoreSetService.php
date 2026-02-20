<?php

namespace App\Service\Search;

use App\Repository\WikipediaArticleRepository;

class WordsPopularityScoreSetService
{
    public function __construct(private WikipediaArticleRepository $wikipediaArticleRepository)
    {
    }

    public function execute(string $languageCode): void
    {
        $articles = $this->wikipediaArticleRepository->findBy(['languageCode' => $languageCode]);

        foreach ($articles as $article) {
            var_dump($article['text']);
        }
    }

}