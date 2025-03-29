<?php

namespace App\View;

use App\Model\LayoutModel;

class IndexView
{

    public function  __construct(protected LayoutModel $layoutModel)
    {

    }
    public function drawHtml(): void
    {
        $this->drawFavicon();
        $this->drawSubmitForm();
    }

    protected function drawFavicon(): void
    {
        echo '<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">';
        echo '<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">';
        echo '<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">';
        echo '<link rel="manifest" href="/site.webmanifest">';

    }

    protected function drawSubmitForm(): void
    {
        echo '<form action="/language" method="POST">
                <b>Input text:</b> '.
                $this->drawLanguageInputField().'
                <input type="submit">
            </form>';
    }

    protected function drawLanguageInputField(): string
    {
        return $this->layoutModel->freeTextField(
            'language_input',
            'language_input',
            10,
            300,
            80
        );

    }

}