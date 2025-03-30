<?php

require 'vendor/autoload.php';

use App\Controller\LanguageDetectionController;
use App\Controller\InputValidationController;
use App\Service\TransliterationService;
use Doctrine\ORM\EntityManagerInterface;

$inputValidationController = new InputValidationController();
$transliterationService = new TransliterationService(new LanguageDetectionController(), new EntityManagerInterface());

$input = $inputValidationController->validate($_POST['language_input']);

$transliterationService->transliterate($input);