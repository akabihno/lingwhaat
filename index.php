<?php

require 'vendor/autoload.php';

use App\TransliterationService;

$transliterationService = new TransliterationService();

$transliterationService->transliterate('Beobahten');
