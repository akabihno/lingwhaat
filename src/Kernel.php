<?php

namespace App;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

#[OA\Info(
    version: '1.0.0',
    title: 'Lingwhaat API'
)]
#[OA\Tag(
    name: 'Search',
    description: 'Word search operations'
)]
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}