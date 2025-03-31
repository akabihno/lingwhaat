<?php

use App\Service\MarkdownGeneratorService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/generate_docs.php serbocroatian a,b,c,č,ć,d,dž,đ,e,f,g,h,i,j,k,l,lj,m,n,nj,o,p,r,s,š,t,u,v,z,ž

$markdownGenerator = new MarkdownGeneratorService($argv);
try {
    $markdownGenerator->generateMarkdown();
} catch (Exception $e) {
    echo $e->getMessage()."\n";
}