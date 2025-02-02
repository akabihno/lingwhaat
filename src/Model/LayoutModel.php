<?php

namespace App\Model;

class LayoutModel
{
    protected const DEFAULT_FREETEXT_MIN_LENGTH = 20;
    protected const DEFAULT_FREETEXT_MAX_LENGTH = 500;
    protected const DEFAULT_FREETEXT_SIZE = 100;
    public function freeTextField(
        string $id,
        string $name,
        int $minLength = self::DEFAULT_FREETEXT_MIN_LENGTH,
        int $maxLength = self::DEFAULT_FREETEXT_MAX_LENGTH,
        int $size = self::DEFAULT_FREETEXT_SIZE
    ): string
    {

        return sprintf(
            "<input type='text' id='%s' name='%s' required minlength='%s' maxlength='%s' size='%s' />",
            $id,
            $name,
            $minLength,
            $maxLength,
            $size
        );
    }

    protected function validateInput($input)
    {

    }

}