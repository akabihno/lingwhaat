<?php

namespace App;

class LandingWebPage
{
    private const FREETEXT_MIN_LENGTH = 20;
    private const FREETEXT_MAX_LENGTH = 500;
    private const FREETEXT_SIZE = 100;
    public function drawHtml(): void
    {
        $this->drawPhpInfo();
        /*
        $this->drawPageTitle();
        $this->drawDelimiter();
        $this->drawTextInputForm();
        */
    }

    protected function drawPhpInfo(): void
    {
        phpinfo();
    }

    public function drawPageTitle(): void
    {
        echo('Input text to detect language');
    }

    protected function drawTextInputForm(): void
    {
        echo('<input type="text" id="name" name="name" required minlength="'
            .self::FREETEXT_MIN_LENGTH.'" maxlength="'
            .self::FREETEXT_MAX_LENGTH.'" size="'.self::FREETEXT_SIZE.'" />'
        );
    }

    protected function drawDelimiter(): void
    {
        echo('<br>');
    }

}