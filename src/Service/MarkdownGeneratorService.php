<?php

namespace App\Service;

use App\Query\AbstractQuery;

class MarkdownGeneratorService
{
    const string WIKTIONARY_PREFIX = 'en_wiktionary_';
    const int DEFAULT_CHUNK_SIZE = 100;

    public function __construct(protected AbstractQuery $abstractQuery)
    {
    }

    public function generateMarkdown(
        string $language,
        bool $rightToLeft = false,
        bool $usePagination = false,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ): void {
        if ($usePagination) {
            $this->generatePaginatedMarkdown($language, $chunkSize);
        } else {
            $this->generateLetterBasedMarkdown($language, $rightToLeft);
        }
    }

    protected function generateLetterBasedMarkdown(string $language, bool $rightToLeft = false): void
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

    protected function generatePaginatedMarkdown(string $language, int $chunkSize): void
    {
        $this->abstractQuery->connect();

        $query = 'SELECT DISTINCT LOWER(LEFT(name, 1)) AS first_char FROM '
            .$this->abstractQuery->getBaseTable($language).
            ' ORDER BY first_char;';

        $characters = $this->abstractQuery->fetch($query);

        $characters = array_filter($characters, function($charArr) {
            foreach ($charArr as $char) {
                return !empty($char);
            }
            return false;
        });

        $flatCharacters = [];
        foreach ($characters as $charArr) {
            foreach ($charArr as $char) {
                $flatCharacters[] = $char;
            }
        }

        $chunks = array_chunk($flatCharacters, $chunkSize);
        $totalPages = count($chunks);

        $this->createPaginatedDirectory($language);
        $indexContent = $this->createIndexHeader($language, $totalPages, count($flatCharacters));

        foreach ($chunks as $pageNum => $chunk) {
            $pageNumber = $pageNum + 1;
            $pageFileName = $this->getPaginatedFileName($language, $pageNumber);

            $this->writePaginatedFileHeader($pageFileName);

            foreach ($chunk as $char) {
                $query = 'SELECT DISTINCT link FROM lingwhaat.'
                    .$this->abstractQuery->getLinksTable($language).' WHERE name LIKE "'.$char.'%"';

                $links = $this->abstractQuery->fetch($query);

                if (!empty($links)) {
                    file_put_contents($pageFileName, "\n## {$char}\n\n", FILE_APPEND);
                    file_put_contents($pageFileName, "|link|\n", FILE_APPEND);
                    file_put_contents($pageFileName, "|----|\n", FILE_APPEND);

                    foreach ($links as $linkArr) {
                        foreach ($linkArr as $link) {
                            $linkPrepared = '|'.$link.'|';
                            $linkPrepared = str_replace(' ', '%20', $linkPrepared);
                            file_put_contents($pageFileName, $linkPrepared."\n", FILE_APPEND);
                        }
                    }
                }
            }

            $charRange = $chunk[0].' - '.end($chunk);
            $indexContent .= sprintf(
                "- [Page %d](%s) (%s) - %d characters\n",
                $pageNumber,
                basename($pageFileName),
                $charRange,
                count($chunk)
            );

            echo sprintf("Generated page %d/%d for %s\n", $pageNumber, $totalPages, $language);
        }

        file_put_contents($this->getIndexFileName($language), $indexContent);
        echo sprintf("Generated index for %s with %d pages\n", $language, $totalPages);
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

    protected function createPaginatedDirectory(string $language): void
    {
        $dir = '/var/www/html/docs/'.ucfirst($language);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected function createIndexHeader(string $language, int $totalPages, int $totalCharacters): string
    {
        $header = "# ".ucfirst($language)." - Wiktionary Index\n\n";
        $header .= "Total characters: {$totalCharacters}\n";
        $header .= "Total pages: {$totalPages}\n\n";
        $header .= "## Pages\n\n";
        return $header;
    }

    protected function getPaginatedFileName(string $language, int $pageNumber): string
    {
        return sprintf(
            '/var/www/html/docs/%s/%spage_%03d.md',
            ucfirst($language),
            self::WIKTIONARY_PREFIX,
            $pageNumber
        );
    }

    protected function getIndexFileName(string $language): string
    {
        return sprintf(
            '/var/www/html/docs/%s/index.md',
            ucfirst($language)
        );
    }

    protected function writePaginatedFileHeader(string $fileName): void
    {
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        $header = "# Words\n\n";
        file_put_contents($fileName, $header);
    }

}