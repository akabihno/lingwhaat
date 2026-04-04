<?php

namespace App\Service\Categorization;

interface WordCategorizationInterface
{
    /**
     * @param string[] $words
     * @return array<string, array<string, float>>  word => [category => score]
     */
    public function categorize(array $words): array;
}
