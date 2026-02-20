<?php

namespace App\Controller;

use App\Service\LanguageDetection\LanguageValidation\LanguageVerificationService;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LanguageVerificationController extends AbstractController
{
    public function __construct(
        protected ElasticsearchLogger $logger,
        protected LanguageVerificationService $languageVerificationService
    )
    {
    }



}