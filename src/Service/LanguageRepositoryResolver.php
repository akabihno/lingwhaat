<?php

namespace App\Service;

use App\Repository\AbstractLanguageRepository;
use App\Repository\AfrikaansLanguageRepository;
use App\Repository\AfarLanguageRepository;
use App\Repository\AlbanianLanguageRepository;
use App\Repository\ArabicLanguageRepository;
use App\Repository\ArmenianLanguageRepository;
use App\Repository\BengaliLanguageRepository;
use App\Repository\BretonLanguageRepository;
use App\Repository\BulgarianLanguageRepository;
use App\Repository\CzechLanguageRepository;
use App\Repository\DanishLanguageRepository;
use App\Repository\DutchLanguageRepository;
use App\Repository\EnglishLanguageRepository;
use App\Repository\EstonianLanguageRepository;
use App\Repository\FinnishLanguageRepository;
use App\Repository\FrenchLanguageRepository;
use App\Repository\GalicianLanguageRepository;
use App\Repository\GeorgianLanguageRepository;
use App\Repository\GermanLanguageRepository;
use App\Repository\GreekLanguageRepository;
use App\Repository\GullahLanguageRepository;
use App\Repository\HausaLanguageRepository;
use App\Repository\HebrewLanguageRepository;
use App\Repository\HindiLanguageRepository;
use App\Repository\HungarianLanguageRepository;
use App\Repository\IcelandicLanguageRepository;
use App\Repository\ItalianLanguageRepository;
use App\Repository\JapaneseLanguageRepository;
use App\Repository\KazakhLanguageRepository;
use App\Repository\KomiLanguageRepository;
use App\Repository\LatinLanguageRepository;
use App\Repository\LatvianLanguageRepository;
use App\Repository\LithuanianLanguageRepository;
use App\Repository\MandarinLanguageRepository;
use App\Repository\MiddleDutchLanguageRepository;
use App\Repository\MongolianLanguageRepository;
use App\Repository\NorwegianLanguageRepository;
use App\Repository\OldDutchLanguageRepository;
use App\Repository\PaliLanguageRepository;
use App\Repository\PolishLanguageRepository;
use App\Repository\PortugueseLanguageRepository;
use App\Repository\RomanianLanguageRepository;
use App\Repository\RussianLanguageRepository;
use App\Repository\SerboCroatianLanguageRepository;
use App\Repository\SomaliLanguageRepository;
use App\Repository\SpanishLanguageRepository;
use App\Repository\SwahiliLanguageRepository;
use App\Repository\SwedishLanguageRepository;
use App\Repository\TagalogLanguageRepository;
use App\Repository\TurkishLanguageRepository;
use App\Repository\UkrainianLanguageRepository;
use App\Repository\UrduLanguageRepository;
use App\Repository\UzbekLanguageRepository;
use App\Repository\VietnameseLanguageRepository;
use App\Repository\WolofLanguageRepository;

class LanguageRepositoryResolver
{
    private array $repositoryMap;

    public function __construct(
        AfrikaansLanguageRepository $afRepository,
        AfarLanguageRepository $aaRepository,
        AlbanianLanguageRepository $sqRepository,
        ArabicLanguageRepository $arRepository,
        ArmenianLanguageRepository $hyRepository,
        BengaliLanguageRepository $bnRepository,
        BretonLanguageRepository $brRepository,
        CzechLanguageRepository $csRepository,
        DanishLanguageRepository $daRepository,
        DutchLanguageRepository $nlRepository,
        EnglishLanguageRepository $enRepository,
        EstonianLanguageRepository $etRepository,
        FrenchLanguageRepository $frRepository,
        GalicianLanguageRepository $glRepository,
        GeorgianLanguageRepository $kaRepository,
        GermanLanguageRepository $deRepository,
        GreekLanguageRepository $elRepository,
        GullahLanguageRepository $gullahRepository,
        HausaLanguageRepository $haRepository,
        HebrewLanguageRepository $heRepository,
        HindiLanguageRepository $hiRepository,
        HungarianLanguageRepository $huRepository,
        IcelandicLanguageRepository $isRepository,
        ItalianLanguageRepository $itRepository,
        JapaneseLanguageRepository $jaRepository,
        KazakhLanguageRepository $kkRepository,
        KomiLanguageRepository $kvRepository,
        LatinLanguageRepository $laRepository,
        LatvianLanguageRepository $lvRepository,
        LithuanianLanguageRepository $ltRepository,
        MandarinLanguageRepository $zhRepository,
        MiddleDutchLanguageRepository $dumRepository,
        MongolianLanguageRepository $mnRepository,
        NorwegianLanguageRepository $noRepository,
        OldDutchLanguageRepository $odtRepository,
        PaliLanguageRepository $piRepository,
        PolishLanguageRepository $plRepository,
        PortugueseLanguageRepository $ptRepository,
        RomanianLanguageRepository $roRepository,
        RussianLanguageRepository $ruRepository,
        SerboCroatianLanguageRepository $shRepository,
        SomaliLanguageRepository $soRepository,
        SpanishLanguageRepository $esRepository,
        SwahiliLanguageRepository $swRepository,
        SwedishLanguageRepository $svRepository,
        TagalogLanguageRepository $tlRepository,
        TurkishLanguageRepository $trRepository,
        UkrainianLanguageRepository $ukRepository,
        UrduLanguageRepository $urRepository,
        UzbekLanguageRepository $uzRepository,
        VietnameseLanguageRepository $viRepository,
        WolofLanguageRepository $woRepository,
        FinnishLanguageRepository $fiRepository,
        BulgarianLanguageRepository $bgRepository,
    ) {
        $this->repositoryMap = [
            'af' => $afRepository,
            'aa' => $aaRepository,
            'sq' => $sqRepository,
            'ar' => $arRepository,
            'hy' => $hyRepository,
            'bn' => $bnRepository,
            'br' => $brRepository,
            'cs' => $csRepository,
            'da' => $daRepository,
            'nl' => $nlRepository,
            'en' => $enRepository,
            'et' => $etRepository,
            'fr' => $frRepository,
            'gl' => $glRepository,
            'ka' => $kaRepository,
            'de' => $deRepository,
            'el' => $elRepository,
            'gullah' => $gullahRepository,
            'ha' => $haRepository,
            'he' => $heRepository,
            'hi' => $hiRepository,
            'hu' => $huRepository,
            'is' => $isRepository,
            'it' => $itRepository,
            'ja' => $jaRepository,
            'kk' => $kkRepository,
            'kv' => $kvRepository,
            'la' => $laRepository,
            'lv' => $lvRepository,
            'lt' => $ltRepository,
            'zh' => $zhRepository,
            'dum' => $dumRepository,
            'mn' => $mnRepository,
            'no' => $noRepository,
            'odt' => $odtRepository,
            'pi' => $piRepository,
            'pl' => $plRepository,
            'pt' => $ptRepository,
            'ro' => $roRepository,
            'ru' => $ruRepository,
            'sh' => $shRepository,
            'so' => $soRepository,
            'es' => $esRepository,
            'sw' => $swRepository,
            'sv' => $svRepository,
            'tl' => $tlRepository,
            'tr' => $trRepository,
            'uk' => $ukRepository,
            'ur' => $urRepository,
            'uz' => $uzRepository,
            'vi' => $viRepository,
            'wo' => $woRepository,
            'fi' => $fiRepository,
            'bg' => $bgRepository,
        ];
    }

    public function getRepository(string $languageCode): ?AbstractLanguageRepository
    {
        return $this->repositoryMap[$languageCode] ?? null;
    }
}
