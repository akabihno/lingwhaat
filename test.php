<?php

require 'vendor/autoload.php';

use App\Service\TransliterationService;
use Tests\TransliterationServiceTest;

$transliterationService = new TransliterationService();
$test = new TransliterationServiceTest($transliterationService);

$test->testTransliteration('абвгде');