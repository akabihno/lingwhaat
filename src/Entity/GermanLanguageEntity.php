<?php

namespace App\Entity;

use App\Repository\GermanLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GermanLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_german_language")]
class GermanLanguageEntity extends EsuLanguageEntity
{

}