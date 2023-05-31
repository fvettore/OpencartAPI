<?php

require_once 'opencartAPI.php';

$test=new openCartProduct(283);

$test->deleteImage("catalog/articoli/pippo.jpg");
echo "Associated extra images:\n";
foreach($test->productImage as $images){
    //var_dump($images);    
    echo $images['image']."\n";
}
