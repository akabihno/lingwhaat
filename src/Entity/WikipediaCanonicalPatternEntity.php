<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "wikipedia_canonical_pattern")]
class WikipediaCanonicalPatternEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "bigint")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: WikipediaArticleEntity::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private WikipediaArticleEntity $article;

    #[ORM\Column(type: "text")]
    private string $pattern;

    #[ORM\Column(type: "bigint")]
    private int $patternHash;

    #[ORM\Column(type: "string", length: 64)]
    private string $script;

    #[ORM\Column(name: "ts_created", length: 255)]
    private string $tsCreated;

    public function getId(): int
    {
        return $this->id;
    }

    public function getArticle(): WikipediaArticleEntity
    {
        return $this->article;
    }
    public function setArticle(WikipediaArticleEntity $article): WikipediaCanonicalPatternEntity
    {
        $this->article = $article;
        return $this;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }
    public function setPattern(string $pattern): WikipediaCanonicalPatternEntity
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function getPatternHash(): int
    {
        return $this->patternHash;
    }
    public function setPatternHash(int $patternHash): WikipediaCanonicalPatternEntity
    {
        $this->patternHash = $patternHash;
        return $this;
    }

    public function getScript(): string
    {
        return $this->script;
    }
    public function setScript(string $script): WikipediaCanonicalPatternEntity
    {
        $this->script = $script;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }
    public function setTsCreated(string $tsCreated): WikipediaCanonicalPatternEntity
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }
}