<?php

namespace App;

class LandingWebPage
{
    public function drawHtml(): void
    {
        $this->drawTextInputForm();
    }

    protected function drawTextInputForm(): void
    {
        echo('<input type="text" id="name" name="name" required minlength="4" maxlength="8" size="10" />');
    }

}