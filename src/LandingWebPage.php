<?php

namespace App;

class LandingWebPage
{
    private const FREETEXT_MIN_LENGTH = 20;
    private const FREETEXT_MAX_LENGTH = 500;
    private const FREETEXT_SIZE = 100;
    public function drawHtml(): void
    {
        $this->drawFavicon();
        $this->drawPhpInfo();
        /*
        $this->drawPageTitle();
        $this->drawDelimiter();
        $this->drawTextInputForm();
        */
    }

    protected function drawFavicon(): void
    {
        echo '<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">';
        echo '<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">';
        echo '<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">';
        echo '<link rel="manifest" href="/site.webmanifest">';

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