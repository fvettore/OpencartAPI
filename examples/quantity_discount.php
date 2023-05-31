<?php

require_once 'opencartAPI.php';

$test=new openCartProduct(54);

//clear existing special discounts (otherwise new quantity discounts will be overrided)
$test->clearSpecialDiscount();

//add new discounted price for quantities
$test->addDiscount(80.10,10);
$test->addDiscount(70.0,20);
$test->addDiscount(50,100) ;

