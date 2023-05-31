<?php

require_once 'opencartAPI.php';

$test=new openCartProduct(54);

//clear existing discounts
$test->clearSpecialDiscount();

//add new discounted price
$test->addSpecialDiscountDiscount(80.10);


