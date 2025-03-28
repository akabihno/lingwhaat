<?php

require 'vendor/autoload.php';

use App\Controller\EsuLanguageController;
use App\Controller\InputValidationController;
use App\Service\TransliterationService;
use Doctrine\ORM\EntityManagerInterface;

$transliterationService = new TransliterationService(new EsuLanguageController(), new EntityManagerInterface());
$inputValidationController = new InputValidationController();

$input = $inputValidationController->validate($_POST['language_input']);

$transliterationService->transliterate($input);