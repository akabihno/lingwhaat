<?php

namespace App\Entity;

use App\Repository\FrenchLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FrenchLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_french_language")]
class FrenchLanguageEntity extends EsuLanguageEntity
{

}