<?php

namespace App;

class LandingWebPage
{
    private const FREETEXT_MIN_LENGTH = 20;
    private const FREETEXT_MAX_LENGTH = 1000;
    public function drawHtml(): void
    {
        $this->drawPageTitle();
        $this->drawTextInputForm();
    }

    public function drawPageTitle(): void
    {
        echo('Input text to detect language');
    }

    protected function drawTextInputForm(): void
    {
        echo('<input type="text" id="name" name="name" required minlength="'
            .self::FREETEXT_MIN_LENGTH.'" maxlength="'
            .self::FREETEXT_MAX_LENGTH.'" size="1000" />'
        );
    }

}