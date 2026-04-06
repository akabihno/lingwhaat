<?php

namespace App\Entity;

use App\Repository\ManuscriptPatternMatchResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManuscriptPatternMatchResultRepository::class)]
#[ORM\Table(name: "manuscript_pattern_match_result")]
class ManuscriptPatternMatchResultEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $matchId;

    #[ORM\Column(type: "text")]
    private string $results;

    #[ORM\Column(name: "ts_created", type: "string", length: 255)]
    private string $tsCreated;

    public function getId(): int
    {
        return $this->id;
    }

    public function getMatchId(): int
    {
        return $this->matchId;
    }

    public function setMatchId(int $matchId): self
    {
        $this->matchId = $matchId;
        return $this;
    }

    public function getResults(): string
    {
        return $this->results;
    }

    public function setResults(string $results): self
    {
        $this->results = $results;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }

    public function setTsCreated(string $tsCreated): self
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }
}
