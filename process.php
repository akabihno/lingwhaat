<?php

require 'vendor/autoload.php';

use App\Controller\InputValidationController;
use App\Service\TransliterationService;

$transliterationService = new TransliterationService();
$inputValidationController = new InputValidationController();

$input = $inputValidationController->validate($_POST['language_input']);

$transliterationService->transliterate($input);