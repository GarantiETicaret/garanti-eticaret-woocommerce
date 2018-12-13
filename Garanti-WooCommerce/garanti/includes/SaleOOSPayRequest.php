
<?php

class SaleOOSPayRequest 
{
    public $mode;
    public $apiversion;
    public $terminalprovuserid;
    public $terminaluserid;
    public $terminalmerchantid;
    public $terminalid;
    public $errorurl;
    public $secure3dsecuritylevel;
    public $successurl;
    public $secure3dhash;
    public $orderid;
    public $txnamount;
    public $txntype;
    public $txninstallmentcount;
    public $txncurrencycode;
    public $customeremailaddress;
    public $customeripaddress;
    public $storekey;
    public $txntimestamp;
    public $lang;
    public $refreshtime;
    public $companyname;
   
		
    public static function Execute(SaleOOSPayRequest $request,Settings3D $settings3D)
    {
      
	  $Data=new NameValueCollection();
	  
	  $Data->Add("mode",$request->mode);
	  $Data->Add("secure3dsecuritylevel",$request->secure3dsecuritylevel);
	  $Data->Add("apiversion",$request->apiversion);
	  $Data->Add("terminalprovuserid",$request->terminalprovuserid);
	  $Data->Add("terminaluserid",$request->terminaluserid);
	  $Data->Add("terminalmerchantid",$request->terminalmerchantid);
	  $Data->Add("terminalid",$request->terminalid);
	  $Data->Add("txntype",$request->txntype);
	  $Data->Add("txnamount",$request->txnamount);
	  $Data->Add("txncurrencycode",$request->txncurrencycode);
	  $Data->Add("txninstallmentcount",$request->txninstallmentcount);
	  $Data->Add("orderid",$request->orderid);
	  $Data->Add("successurl",$request->successurl);
	  $Data->Add("errorurl",$request->errorurl);
	  $Data->Add("customeremailaddress",$request->customeremailaddress );
	  $Data->Add("customeripaddress",$request->customeripaddress);
	  $Data->Add("secure3dhash",$request->secure3dhash);
	  $Data->Add("lang", $request->lang);
	  $Data->Add("refreshtime",$request->refreshtime);
	  $Data->Add("txntimestamp",$request->txntimestamp);
	  $Data->Add("companyname",$request->companyname);
	  // $Data->Add("cardnumber", $request->cardnumber);
	  // $Data->Add("cardexpiredatemonth",$request->cardexpiredatemonth);
	  // $Data->Add("cardexpiredateyear",$request->cardexpiredateyear);
	  // $Data->Add("cardcvv2", $request->cardcvv2);

	  
        return  $request->PreparePostForm ( $settings3D->BaseUrl, $Data );
    }
	
	public static function Compute3DHash(SaleOOSPayRequest $request,Settings3D $settings3D)
    {
        $SecurityData = strtoupper(sha1($settings3D->Password . str_pad($request->terminalid, 9, '0', STR_PAD_LEFT)));
        $HashData = strtoupper(sha1($request->terminalid . $request->orderid . $request->txnamount . $request->successurl . $request->errorurl . $request->txntype .$request->txninstallmentcount .$request->storekey .$SecurityData));
        return $HashData;
    }
	
	
	
	
	
	
	
	public static function PreparePostForm($url,NameValueCollection $data)
    {
		
        $formName="PostForm";
		$formID ="<form id=\"" . $formName . "\" name=\"" . $formName . "\" action=\"" . $url . "\" method=\"POST\">";
		foreach ($data->AllKeys()  as $key)
            {
                $formID .="<input type=\"hidden\" name=\"" . $key .
                               "\" value=\"" . $data->Get($key) . "\">";
           
			}
			$formID .="</form>";
			$strScript ="<script language=\"javascript\">";
		$strScript .="var v" . $formName . " = document." . $formName . ";";
		$strScript .="v" . $formName . ".submit();"; 
		$strScript .="</script>";
		$form=$formID .	$strScript ;
		return $form;
							 
    }
	
	
	
	
      //Post edilmesi istenen xml metni oluşturulup bu xml metni belirtilen adrese post edilir.
     
	
}
