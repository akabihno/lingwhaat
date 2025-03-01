<?php

namespace App\Service;

class MarkdownGeneratorService
{
    public function __construct(protected array $args)
    {

    }

    /**
     * @throws \Exception
     */
    public function generateMarkdown(): string
    {
        if (empty($this->args)) {
            throw new \Exception("Missing arguments");
        }

        if (!$this->args[1]) {
            throw new \Exception("Missing language");
        }

        if (!$this->args[2]) {
            throw new \Exception("Missing letters");
        }

        $language = ucfirst($this->args[1]);
        $letters = explode(',', $this->args[2]);

        $queryClassName = 'App\Query\PronunciationQuery'.$language.'Language';

        $languageQuery = new $queryClassName();

        $linksTable = $languageQuery->getLinksTable();

        foreach ($letters as $letter) {

            $query = 'SELECT DISTINCT link FROM lingwhaat.'.$linksTable.' WHERE name LIKE "'.$letter.'%"';

            $languageQuery->connect();
            var_dump($languageQuery->fetch($query));
        }

    }


}