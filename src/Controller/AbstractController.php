<?php

namespace App\Controller;

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

abstract class AbstractController
{
    public function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__).'/.env');
    }

}