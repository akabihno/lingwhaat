<?php

namespace App\Service\LanguageDetection\LanguageTransliteration\Constants;

class IpaPredictorConstants
{
    private const string ML_SERVICE_HOST = '127.0.0.1';
    private const string ML_SERVICE_PORT = '8000';
    private const string ML_SERVICE_TRAIN_IPA_ROUTE = 'train-ipa';
    private const string ML_SERVICE_TRAIN_WORD_ROUTE = 'train-word';
    private const string ML_SERVICE_PREDICT_IPA_ROUTE = 'predict-ipa';
    private const string ML_SERVICE_PREDICT_WORD_ROUTE = 'predict-word';
    private const string ML_SERVICE_MODELS_PATH = 'ml_service/models/';
    private const string ML_SERVICE_DATA_PATH = 'ml_service/data/';

    public  static function getMlServiceHost(): string
    {
        return self::ML_SERVICE_HOST;
    }

    public  static function getMlServicePort(): string
    {
        return self::ML_SERVICE_PORT;
    }

    public  static function getMlServiceTrainIpaRoute(): string
    {
        return self::ML_SERVICE_TRAIN_IPA_ROUTE;
    }

    public  static function getMlServiceTrainWordRoute(): string
    {
        return self::ML_SERVICE_TRAIN_WORD_ROUTE;
    }

    public  static function getMlServicePredictIpaRoute(): string
    {
        return self::ML_SERVICE_PREDICT_IPA_ROUTE;
    }

    public  static function getMlServicePredictWordRoute(): string
    {
        return self::ML_SERVICE_PREDICT_WORD_ROUTE;
    }

    public  static function getMlServiceModelsPath(): string
    {
        return self::ML_SERVICE_MODELS_PATH;
    }

    public static function getMlServiceDataPath(): string
    {
        return self::ML_SERVICE_DATA_PATH;
    }


}