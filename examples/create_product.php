<?php

require_once 'opencartAPI.php';

//TEST create e new product
$test=new openCartProduct();

$test->create();

$test->enable();

$test->product['model']='prodotto di test';
$test->product['quantity']=100;
//adjust accordingly with your definitions in ....._stock_status
$test->product['stock_status_id']=5;
$test->product['price']=125;

//set valid id values according to _language table
//2 languages define2: 1=english; 2=italian
$test->productDescription[1]['description']="This is really an awesome product!";
$test->productDescription[2]['description']="Questo è veramente un prodotto meraviglioso!";
$test->productDescription[1]['name']="added product";
$test->productDescription[2]['name']="prodotto aggiunto";
$test->productDescription[1]['meta_title']="meta product";
$test->productDescription[2]['meta_title']="meta prodotto";

$test->save();
