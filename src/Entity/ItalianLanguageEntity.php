<?php

namespace App\Entity;

use App\Repository\ItalianLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItalianLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_italian_language")]
class ItalianLanguageEntity extends EsuLanguageEntity
{

}