<?php

require 'vendor/autoload.php';

use App\Model\LayoutModel;
use App\View\IndexView;

$layoutModel = new LayoutModel();
$landingPage = new IndexView($layoutModel);

$landingPage->drawHtml();
