<?php

namespace App\Service;

class MarkdownGeneratorService
{
    const WIKTIONARY_PREFIX = 'en_wiktionary_';
    public function __construct(protected array $args)
    {

    }

    /**
     * @throws \Exception
     */
    public function generateMarkdown(): void
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

        if ($this->args[1] == 'serbocroatian') {
            $language = 'SerboCroatian';
        } else {
            $language = ucfirst($this->args[1]);
        }
        $letters = explode(',', $this->args[2]);

        $queryClassName = 'App\Query\PronunciationQuery'.$language.'Language';

        $languageQuery = new $queryClassName();

        $linksTable = $languageQuery->getLinksTable();

        $language = strtolower($language);

        foreach ($letters as $letter) {

            $query = 'SELECT DISTINCT link FROM lingwhaat.'.$linksTable.' WHERE name LIKE "'.$letter.'%"';

            $languageQuery->connect();
            $links = $languageQuery->fetch($query);

            if (!empty($links)) {
                $this->writeFileHeader($language, $letter);

                foreach ($links as $linkArr) {
                    foreach ($linkArr as $link) {
                        $this->writeLink($language, $letter, $link);
                    }
                }
            }

        }

    }

    protected function writeFileHeader(string $language, string $letter): void
    {
        file_put_contents($this->getFileName($language, $letter), "|link|\n", FILE_APPEND);
        file_put_contents($this->getFileName($language, $letter), "|----|\n", FILE_APPEND);

    }

    protected function writeLink(string $language, string $letter, string $link): void
    {
        $linkPrepared = '|'.$link.'|';
        $linkPrepared = str_replace(' ', '%20', $linkPrepared);
        file_put_contents($this->getFileName($language, $letter), $linkPrepared."\n", FILE_APPEND);
    }

    protected function getFileName(string $language, string $letter): string
    {
        return '/var/www/html/docs/Unsorted/'.self::WIKTIONARY_PREFIX.$language.'_'.$letter.'.md';
    }


}