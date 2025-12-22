<?php

namespace App\Service;

use App\Query\AbstractQuery;

class MarkdownGeneratorService
{
    const string WIKTIONARY_PREFIX = 'en_wiktionary_';
    public function __construct(protected AbstractQuery $abstractQuery)
    {
    }

    public function generateMarkdown(string $language, bool $rightToLeft = false): void
    {
        $this->abstractQuery->connect();

        $query = 'SELECT DISTINCT LOWER('.$this->getDirection($rightToLeft).'
        (name, 1)) AS first_letter FROM '
            .$this->abstractQuery->getBaseTable($language).
            ' ORDER BY first_letter;';

        $letters = $this->abstractQuery->fetch($query);

        $letters = array_filter($letters, function($letterArr) {
            foreach ($letterArr as $letter) {
                return preg_match('/^\p{L}$/u', $letter) === 1;
            }
            return false;
        });

        foreach ($letters as $letterArr) {
            foreach ($letterArr as $key => $letter) {
                $query = 'SELECT DISTINCT link FROM lingwhaat.'
                    .$this->abstractQuery->getLinksTable($language).' WHERE name LIKE "'.$letter.'%"';

                $links = $this->abstractQuery->fetch($query);

                if (!empty($links)) {
                    $this->writeFileHeader($language, $letter);
                    $this->echoLetterLineForMarkdown($language, $letter);

                    foreach ($links as $linkArr) {
                        foreach ($linkArr as $link) {
                            $this->writeLink($language, $letter, $link);
                        }
                    }
                }
            }
        }

    }

    protected function getDirection(bool $rightToLeft = false): string
    {
        return ($rightToLeft) ? 'RIGHT' : 'LEFT';
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

    protected function echoLetterLineForMarkdown(string $language, string $letter): void
    {
        echo sprintf("[%s](docs/%s/%s%s_%s.md),", $letter, ucfirst($language), self::WIKTIONARY_PREFIX, $language, $letter)."\n";
    }


}