<?php

namespace App\Service;
class TransliterationService
{
    protected TransliterationMappingService $mappingService;
    public function transliterate($text) {
        $result = '';

        if ($this->textIsCyrillic($text)) {
            $this->mappingService = new CyrillicLatinMappingService();
            var_dump($this->mappingService->get());
        } else {
            var_dump('No transliteration needed');
        }


        return $result;
    }

    protected function textIsCyrillic($text): bool
    {
        return (bool) preg_match('/\p{Cyrillic}/u', $text);
    }

}