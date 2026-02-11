<?php

namespace App\Entity;

use App\Repository\RomanianLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RomanianLanguageRepository::class)]
#[ORM\Table(
    name: "pronunciation_romanian_language",
    indexes: [
        new ORM\Index(name: 'i_name', columns: ['name']),
    ]
)]
class RomanianLanguageEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 256)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $ipa;

    #[ORM\Column(name: 'ts_created', length: 255)]
    private string $tsCreated;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $score = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): RomanianLanguageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): RomanianLanguageEntity
    {
        $this->name = $name;
        return $this;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function setIpa(string $ipa): RomanianLanguageEntity
    {
        $this->ipa = $ipa;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }

    public function setTsCreated(string $tsCreated): RomanianLanguageEntity
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): RomanianLanguageEntity
    {
        $this->score = $score;
        return $this;
    }
}