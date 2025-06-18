<?php

namespace App\Service\LanguageDetection\LanguageTransliteration\Constants;

class IpaPredictorConstants
{
    private const string ML_SERVICE_HOST = '127.0.0.1';
    private const string ML_SERVICE_PORT = '8000';
    private const string ML_SERVICE_PREDICT_ROUTE = 'predict';

    public  static function getMlServiceHost(): string
    {
        return self::ML_SERVICE_HOST;
    }

    public  static function getMlServicePort(): string
    {
        return self::ML_SERVICE_PORT;
    }

    public  static function getMlServicePredictRoute(): string
    {
        return self::ML_SERVICE_PREDICT_ROUTE;
    }


}