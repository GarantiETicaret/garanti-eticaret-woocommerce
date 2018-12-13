<?php

class Sale3DSecureRequest
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
    public $cardnumber;
    public $cardexpiredatemonth;
    public $cardexpiredateyear;
    public $cardcvv2;
    public $txntimestamp;
    public $lang;
    public $refreshtime;
   
		
    public static function Execute(Sale3DSecureRequest $request,Settings3D $settings3D)
    {
      
        return  $request->toHtmlString ( $request, $settings3D );
    }
	
	public static function Compute3DHash(Sale3DSecureRequest $request,Settings3D $settings3D)
    {
        $SecurityData = strtoupper(sha1($settings3D->Password . str_pad($request->terminalid, 9, '0', STR_PAD_LEFT)));
        $HashData = strtoupper(sha1($request->terminalid . $request->orderid . $request->txnamount . $request->successurl . $request->errorurl . $request->txntype .$request->txninstallmentcount .$request->storekey .$SecurityData));
        return $HashData;
    }
	
	
	
	
	
	
      //Post edilmesi istenen xml metni oluşturulup bu xml metni belirtilen adrese post edilir.
      public function toHtmlString(Sale3DSecureRequest $request, Settings3D $settings3D) {
		$builder = "";
		
		$builder .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">";
		$builder .= "<html>";
		$builder .= "<body>";
		$builder .= "<form action=\"" . $settings3D->BaseUrl . "\" method=\"post\" id=\"three_d_form\" >";
		$builder .= "<input type=\"hidden\" name=\"secure3dsecuritylevel\" id=\"secure3dsecuritylevel\" value=\"" . $request->secure3dsecuritylevel . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"mode\" id=\"mode\" value=\"" . $request->mode . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"apiversion\" id=\"apiversion\" value=\"" . $request->apiversion . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"terminalid\" id=\"terminalid\" value=\"" . $request->terminalid . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"terminalprovuserid\" id=\"terminalprovuserid\" value=\"" . $request->terminalprovuserid . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"terminaluserid\" id=\"terminaluserid\" value=\"" . $request->terminaluserid . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"terminalmerchantid\" id=\"terminalmerchantid\" value=\"" . $request->terminalmerchantid . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"txntype\" id=\"txntype\" value=\"" . $request->txntype . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"cardnumber\" id=\"cardnumber\" value=\"" . $request->cardnumber . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"cardexpiredatemonth\" id=\"cardexpiredatemonth\" value=\"" . $request->cardexpiredatemonth . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"cardexpiredateyear\" id=\"cardexpiredateyear\" value=\"" . $request->cardexpiredateyear . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"cardcvv2\" id=\"cardcvv2\" value=\"" . $request->cardcvv2 . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"txnamount\" id=\"txnamount\" value=\"" . $request->txnamount . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"txncurrencycode\" id=\"txncurrencycode\" value=\"" . $request->txncurrencycode . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"txninstallmentcount\" id=\"txninstallmentcount\" value=\"" . $request->txninstallmentcount . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"orderid\" id=\"orderid\" value=\"" . $request->orderid . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"successurl\" id=\"successurl\" value=\"" . $request->successurl . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"errorurl\" id=\"errorurl\" value=\"" . $request->errorurl . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"secure3dhash\" id=\"secure3dhash\" value=\"" . $request->secure3dhash . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"customeremailaddress\" id=\"customeremailaddress\" value=\"" . $request->customeremailaddress . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"customeripaddress\" id=\"customeripaddress\" value=\"" . $request->customeripaddress . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"txntimestamp\" id=\"txntimestamp\" value=\"" . $request->txntimestamp . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"lang\" id=\"lang\" value=\"" . $request->lang . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"customeripaddress\" id=\"customeripaddress\" value=\"" . $request->customeripaddress . "\"/>";
		$builder .= "<input type=\"hidden\" name=\"refreshtime\" id=\"refreshtime\" value=\"" . $request->refreshtime . "\"/>";

		
		
		$builder .= "<input type=\"submit\" value=\"Öde\" style=\"display:none;\"/>";
		$builder .= "</form>";
		$builder .= "</body>";
		$builder .= "<script>document.getElementById(\"three_d_form\").submit();</script>";
		$builder .= "</html>";
		return $builder;
	}
}
