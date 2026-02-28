<?php

namespace App\Entity;

use App\Repository\WordsPopularityScoreSetScheduleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WordsPopularityScoreSetScheduleRepository::class)]
#[ORM\Table(name: "words_popularity_score_set_schedule")]
class WordsPopularityScoreSetScheduleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "string", length: 8)]
    private string $languageCode;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $offset;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): WordsPopularityScoreSetScheduleEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): WordsPopularityScoreSetScheduleEntity
    {
        $this->languageCode = $languageCode;
        return $this;
    }

    public function getOffset(): string
    {
        return $this->offset;
    }

    public function setOffset(string $offset): WordsPopularityScoreSetScheduleEntity
    {
        $this->offset = $offset;
        return $this;
    }

}