<?php

namespace App\Service\LanguageDetection\LanguageTransliteration;

use App\Service\LanguageDetection\LanguageTransliteration\Constants\IpaPredictorConstants;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UseWordPredictorModelService
{
    protected string $modelName;
    protected string $dataPath;
    public function __construct(
        protected HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function run(string $lang, string $ipa): string
    {
        $this->modelName = "{$lang}_model.pt";
        $this->dataPath = "{$lang}.csv";

        $response = $this->httpClient->request(
            'GET',
            'http://' . IpaPredictorConstants::getMlServiceHost() .
            ':' . IpaPredictorConstants::getMlServicePort() .
            '/' . IpaPredictorConstants::getMlServicePredictWordRoute() . '/',
            [
                'query' => [
                    'ipa' => $ipa,
                    'model_name' => $this->modelName,
                    'file' => $this->dataPath,
                ],
            ]
        );

        $data = $response->toArray();
        return $data['word'];
    }

}