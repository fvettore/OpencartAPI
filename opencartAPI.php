<?php

//require_once "../config.php";
// DB
/*
define('DB_HOSTNAME', 'xxxxx.it');
define('DB_USERNAME', 'yyyyy');
define('DB_PASSWORD', 'zer0botUpdate');
define('DB_DATABASE', 'opencart');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');
*/


$db_catalog=new mysqli(DB_HOSTNAME,DB_USERNAME,DB_PASSWORD,DB_DATABASE,DB_PORT);
echo $db_catalog->host_info . "\n";

$db_catalog->set_charset('utf8');//adjust accordingly...

class openCartProduct{
    
    public $product, $productDescription;
    
     public function __construct($productId=0, $SKU=NULL) {        
        global $db_catalog;
        
        $this->productId=$productId;
        
        $query="select * from ".DB_PREFIX."product where product_id=? OR sku=?";
        $sm=$db_catalog->prepare($query);
        $sm->bind_param("is", $productId,$SKU);
        $sm->execute();
        $result=$sm->get_result();
        if(!$result->num_rows)return false;
        else{
            $this->product=$result->fetch_assoc();            
            $this->productId=$this->product['product_id'];              
            
            
            $query="select * from ".DB_PREFIX."product_description where product_id=?";
            $sm=$db_catalog->prepare($query);
            $sm->bind_param("i", $this->productId);
            $sm->execute();
            $result=$sm->get_result();            
            //load description for alla languages
            while($l=$result->fetch_assoc()){
                $language_id=$l['language_id'];
                $this->productDescription[$language_id]=$l; 
            }
        }
    }
    
    public function save(){
        global $db_catalog;
        if($this->productId){
            //patch to avoid error on update
            if(isset($this->product['date_available']))
                if($this->product['date_available']=='0000-00-00')$this->product['date_available']='1980-01-01';
            $sql="update ".DB_PREFIX."product set ".implode(array_keys($this->product),"=?,")."=? where product_id=".$this->productId;
            //echo "$sql\n";
            $params[0]='';
            $keys=array_keys($this->product);
                            //patch price cannot br null
            if(is_null($this->product['price']))$this->product['price']=0;

            for($x=1;$x<count($this->product)+1;$x++){
              //diciamo che sono tutte stringhe anche se non è vero
              $params[0].='s';
              //attenzione: va passato per riferimento, non per valore
              $params[$x]=&$this->product[$keys[$x-1]];
            }            
            $stmt = $db_catalog->prepare($sql) or die ("Failed to prepared save for product_id ".$this->productId);
            call_user_func_array(array($stmt, 'bind_param'), $params);
            if(!$stmt->execute()){
                echo "error executing: ".$db_catalog->error;
                }
            //save descrizioni for all languages lingue    
            foreach($this->productDescription as $record){                
                if(!is_null($record['language_id'])){                    
                    //save for alla languages                   
                    $sql="update ".DB_PREFIX."product_description set ".implode(array_keys($record),"=?,")."=? where product_id=".$this->productId." and language_id=".$record['language_id'];
                    $keys=array_keys($record);
                    unset($params);
                    $params[0]='';
                    for($y=1;$y<count($record)+1;$y++){
                        //diciamo che sono tutte stringhe anche se non è vero
                        $params[0].='s';
                        //attenzione: va passato per riferimento, non per valore
                        $params[$y]=&$record[$keys[$y-1]];
                    }
                    $stmt = $db_catalog->prepare($sql) or die ("Failed to prepare save for description language id=".$record['language_id']);
                    call_user_func_array(array($stmt, 'bind_param'), $params);
                    if(!$stmt->execute()){ 
                        echo "error executing: ".$db_catalog->error;
                    }
               
                }
            }
        }
    }    
    
    //ADD void product to catalogue
    public function create(){
        
        global $db_catalog;    
        $q="insert into ".DB_PREFIX."product set model='XXX', sku='',upc='',ean='',
                jan='',isbn='',mpn='',location='',stock_status_id=0,
                manufacturer_id=0,tax_class_id=0, date_added=now(),
                date_modified=now(),date_available=now(),image=''
                
        ";
        
        if(!$db_catalog->query($q)){
            echo "error: ".$db_catalog->error."\n";            
            die("\n\n$q\n\n");
        } 
        $this->productId=$db_catalog->insert_id;
        $r=$db_catalog->query("select language_id from ".DB_PREFIX."language");
        while($l=$r->fetch_array()){
            $language_id=$l['language_id'];
            $q="insert into ".DB_PREFIX."product_description set name='',
                description='',tag='',meta_title='',meta_description='',
                meta_keyword='',
                language_id=$language_id, product_id=".$this->productId;
            $this->productDescription[$language_id]['language_id']=$language_id;
            if(!$db_catalog->query($q)){
                  echo "error: ".$db_catalog->error."\n";
            }
        }
        //ADD default store. Mandatory! otherwise product not displayed
        //to be modified for multistore
        $q="insert into ".DB_PREFIX."product_to_store set store_id=0,product_id=$this->productId";
        $db_catalog->query($q);
        
    }
    
    //remove special discounts for product
    public function clearSpecialDiscount(){
        global $db_catalog;
        $q="delete from ".DB_PREFIX."product_special where product_id=?";
        $sm=$db_catalog->prepare($q);
        $sm->bind_param("i",$this->productId);
        $sm->execute();
        
    }
    
    //add special discount for product
    //customer groups not handled. Use default
    public function addSpecialDiscount($price,$from='1970-01-01',$to='2050-01-01'){
        global $db_catalog;
        $this->clearSpecialDiscount();
        $q="insert into ".DB_PREFIX."product_special
            SET price=?,date_start=?,date_end=?,product_id=?,
            customer_group_id=1;
            ";
            $sm=$db_catalog->prepare($q);
            $sm->bind_param("dssi",$price,$from,$to,$this->productId);
            $sm->execute();    
        
        echo $db_catalog->error;
    }
    
    //remove quantity discounts for product
    public function clearDiscount(){
        global $db_catalog;
        $q="delete from ".DB_PREFIX."product_discount where product_id=?";
        $sm=$db_catalog->prepare($q);
        $sm->bind_param("i",$this->productId);
        $sm->execute();
        
    }
    
    //add quantity discount for product
    //customer groups not handled. Use default
    public function addDiscount($price,$qty,$from='1970-01-01',$to='2050-01-01'){
        global $db_catalog;
        $q="insert into ".DB_PREFIX."product_discount
            SET price=?,quantity=?,date_start=?,date_end=?,product_id=?,
            customer_group_id=1;
            ";
            $sm=$db_catalog->prepare($q);
            $sm->bind_param("ddssi",$price,$qty,$from,$to,$this->productId);
            $sm->execute();    
        
        echo $db_catalog->error;
    }
    
    
    /***************************************************************************
     * SHORTCUTS for common actions ;)
     * Remind to save after...
     */
    
    //enable product in catalogue
    public function enable(){
        $this->product['status']=1;
    }    
    //disable product in catalogue
    public function disable(){
        $this->product['status']=0;        
    }
    //set price
    public function price($price){
        $this->product['price']=$price;        
    }
    //set quantity
    public function quantity($qty){
        $this->product['quantity']=$qty;        
    }
}
