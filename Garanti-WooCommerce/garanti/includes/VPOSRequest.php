<?php

class VPOSRequest
{
    public $Mode;
    public $Version;
    public $Terminal;
    public $Customer;
    public $Card;
    public $Order;
    public $Transaction;
}

class Terminal
{
    // public function Terminal()
    // {
        // $this->ID="30691297";// Garanti bankası tarafından sağlanan kendi bilgileriniz ile değiştirmelisiniz. 
        // $this->MerchantID="7000679";//Garanti bankası tarafından sağlanan kendi bilgileriniz ile değiştirmelisiniz. 
        // $this->ProvUserID="PROVAUT"; //Garanti bankası tarafından sağlanan kendi bilgileriniz ile değiştirmelisiniz. 
        // $this->UserID="PROVAUT";//Garanti bankası tarafından sağlanan kendi bilgileriniz ile değiştirmelisiniz. 
        // $this->HashData=""; //Hesaplanacak
    // }
    public $ProvUserID;
    public $HashData;
    public $UserID;
    public $ID;
    public $MerchantID;
}
class Customer
{
    public $IPAddress;
    public $EmailAddr;
}
class Card
{
    public $Number;
    public $ExpireDate;
    public $CVV2;
}
class Order
{
    public $OrderID;
    public $GroupID;
    public $Description;
}
class Transaction
{
    public $Type;
    public $Amount;
}