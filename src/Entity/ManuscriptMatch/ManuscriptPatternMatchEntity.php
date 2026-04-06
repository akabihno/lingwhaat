<?php

namespace App\Entity\ManuscriptMatch;

use App\Repository\ManuscriptMatch\ManuscriptPatternMatchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManuscriptPatternMatchRepository::class)]
#[ORM\Table(name: "manuscript_pattern_match")]
class ManuscriptPatternMatchEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $sourceId;

    #[ORM\Column(type: "string", length: 2048)]
    private string $sourceData;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ManuscriptPatternMatchEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getSourceId(): int
    {
        return $this->sourceId;
    }

    public function setSourceId(int $sourceId): ManuscriptPatternMatchEntity
    {
        $this->sourceId = $sourceId;
        return $this;
    }

    public function getSourceData(): string
    {
        return $this->sourceData;
    }

    public function setSourceData(string $sourceData): ManuscriptPatternMatchEntity
    {
        $this->sourceData = $sourceData;
        return $this;
    }


}