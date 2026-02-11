<?php

namespace App\Entity;

use App\Repository\WolofLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WolofLanguageRepository::class)]
#[ORM\Table(
    name: "pronunciation_wolof_language",
    indexes: [
        new ORM\Index(name: 'idx_name', columns: ['name']),
    ]
)]
class WolofLanguageEntity
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
    private int $score;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): WolofLanguageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): WolofLanguageEntity
    {
        $this->name = $name;
        return $this;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function setIpa(string $ipa): WolofLanguageEntity
    {
        $this->ipa = $ipa;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }

    public function setTsCreated(string $tsCreated): WolofLanguageEntity
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }
    
    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): WolofLanguageEntity
    {
        $this->score = $score;
        return $this;
    }

}
