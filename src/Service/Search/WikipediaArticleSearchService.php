<?php

namespace App\Service\Search;

use App\Repository\WikipediaArticleRepository;

class WikipediaArticleSearchService
{
    public function __construct(private readonly WikipediaArticleRepository $wikipediaArticleRepository)
    {
    }

    public function get(int $articleId): string
    {
        $article = $this->wikipediaArticleRepository->find($articleId);

        return $article->getText();
    }

}