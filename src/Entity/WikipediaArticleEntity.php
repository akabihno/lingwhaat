<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "wikipedia_article")]
class WikipediaArticleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "bigint")]
    private int $id;

    #[ORM\Column(type: "string", length: 8)]
    private string $languageCode;

    #[ORM\Column(type: "string", length: 2048)]
    private string $wikipediaLink;

    #[ORM\Column(type: "text")]
    private string $text;

    #[ORM\Column(name: 'ts_created', length: 255)]
    private string $tsCreated;


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): WikipediaArticleEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): WikipediaArticleEntity
    {
        $this->languageCode = $languageCode;
        return $this;
    }

    public function getWikipediaLink(): string
    {
        return $this->wikipediaLink;
    }

    public function setWikipediaLink(string $wikipediaLink): WikipediaArticleEntity
    {
        $this->wikipediaLink = $wikipediaLink;
        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): WikipediaArticleEntity
    {
        $this->text = $text;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }

    public function setTsCreated(string $tsCreated): WikipediaArticleEntity
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }
}