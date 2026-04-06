<?php

namespace App\Entity\ManuscriptMatch;

use App\Repository\ManuscriptMatch\ManuscriptPatternMatchScheduleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManuscriptPatternMatchScheduleRepository::class)]
#[ORM\Table(name: "manuscript_pattern_match_schedule")]
class ManuscriptPatternMatchScheduleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "string", length: 128)]
    private string $manuscriptName;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ManuscriptPatternMatchScheduleEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getManuscriptName(): string
    {
        return $this->manuscriptName;
    }

    public function setManuscriptName(string $manuscriptName): ManuscriptPatternMatchScheduleEntity
    {
        $this->manuscriptName = $manuscriptName;
        return $this;
    }

}