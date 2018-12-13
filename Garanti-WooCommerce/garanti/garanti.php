<?php
error_reporting(E_ALL);

/*
  Plugin Name: Garanti Bankası Kredi Kartı İle Ödeme
  Plugin URI: http://developer.garanti.com.tr
  Description: Garanti Kredi Kartı ile ödeme
  Version: 1.1
  Author: Garanti adına Codevist BT
  Author URI: http://www.codevist.com
 */

/* Define the database prefix */
global $wpdb;
define("_DB_PREFIX_", $wpdb->prefix);

/* Install Function */
register_activation_hook(__FILE__, 'myplugin_activate');

function myplugin_activate()
{
	global $wpdb;
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'garanti` (
	  `order_id` int(10) unsigned NOT NULL,
	  `customer_id` int(10) unsigned NOT NULL,
	  `garanti_id` varchar(64) NULL,
	  `amount` decimal(10,4) NOT NULL,
	  `amount_paid` decimal(10,4) NOT NULL,
	  `installment` int(2) unsigned NOT NULL DEFAULT 1,
	  `cardholdername` varchar(60) NULL,
	  `cardnumber` varchar(25) NULL,
	  `cardexpdate` varchar(8) NULL,
	  `createddate` datetime NOT NULL,
	  `ipaddress` varchar(16) NULL,
	  `status_code` tinyint(1) DEFAULT 1,
	  `result_code` varchar(60) NULL,
	  `result_message` varchar(256) NULL,
	  `mode` varchar(16) NULL,
	  `shared_payment_url` varchar(256) NULL,
	  KEY `order_id` (`order_id`),
	  KEY `customer_id` (`customer_id`)
	) DEFAULT CHARSET=utf8;';
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
	return dbDelta($sql);
}

/* garanti All Load */
add_action('plugins_loaded', 'init_garanti_gateway_class', 0);

function init_garanti_gateway_class()
{
	if (!class_exists('WC_Payment_Gateway'))
		return;

	class garanti extends WC_Payment_Gateway
	{
	
		function __construct()
		{
			$this->id = "garanti";
			$this->method_title = "Garanti Kredi Kartı İle Ödeme";
			$this->method_description = "Kredi kartı ile peşin ve taksitli ödeme";
			$this->title = 'Kredi kartı ile Ödeme';
			$this->icon = null;
			$this->has_fields = true;
			$this->supports = array('default_credit_card_form');
			$this->init_form_fields();
			$this->init_settings();
			$this->version = "V0.1";
			
			foreach ($this->settings as $setting_key => $value)
				$this->$setting_key = $value;
			//Register the style
			add_action('admin_enqueue_scripts', array($this, 'register_admin_styles'));
			add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
			add_action('woocommerce_thankyou_' . $this->id, array($this, 'receipt_page'));
			if (is_admin()) {
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			}

			// GarantiTokenSettings
			$garanti_settings = get_option("woocommerce_garanti_settings");
		
            $this->mode=$garanti_settings["mode"];
			$this->baseUrl=$garanti_settings["baseUrl"];
			$this->Password=$garanti_settings["Password"];
			$this->terminalId=$garanti_settings["terminalId"];
			$this->merchantId=$garanti_settings["merchantId"];
			$this->provUserId=$garanti_settings["provUserId"];
			$this->userId=$garanti_settings["userId"];
            $this->secure3dsecuritylevel=$garanti_settings["secure3dsecuritylevel"];
			$this->payment_page=$garanti_settings["payment_page"];
			}

		public function register_admin_styles()
		{
			wp_register_style('admin', plugins_url('css/admin.css', __FILE__));
			wp_enqueue_style('admin');
		}


		public function admin_options()
		{
			
			echo '<h1>Garanti Ödeme Ayarları</h1><hr/>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';

			include(dirname(__FILE__).'/includes/footer.php');
		}

		/* 	Admin Panel Fields */

		public function init_form_fields()
		{
			$this->form_fields = array(
				'enabled' => array(
					'title' => 'Eklenti Aktif',
					'label' => 'Eklenti Aktif Mi?',
					'type' => 'checkbox',
					'default' => 'yes',
				),
				'userId' => array(
					'title' => 'Garanti User Id',
					'type' => 'text',
					'desc_tip' => 'Garanti tarafından atanan üye iş yeri numarası',
				),
				'provUserId' => array(
					'title' => 'Garanti ProvUserId',
					'type' => 'text',
					'desc_tip' => 'Garanti tarafından atanan ProvUserId',
				),
				'merchantId' => array(
					'title' => 'Garanti Terminal MerchantId',
					'type' => 'text',
					'desc_tip' => 'Garanti tarafından atanan Terminal MerchantId',
				),
				'terminalId' => array(
					'title' => 'Garanti Terminal Id',
					'type' => 'text',
					'desc_tip' => 'Garanti tarafından atanan Terminal Id',
				),
				'Password' => array(
					'title' => 'Garanti Password',
					'type' => 'text',
					'desc_tip' => 'Garanti tarafından atanan Password',
				),
				'baseUrl' => array(
					'title' => 'Garanti baseUrl',
					'type' => 'text',
					'desc_tip' => 'Garanti tarafından atanan baseUrl',
				),
				'mode' => array(
					'title' => 'Çalışma Ortamı',
					'type' => 'select',
					'desc_tip' => 'Ortam',
					'default' => 'form',
					'options' => array(
						'Test' => 'Test',
						'Prod' => 'Prod',
					),
					
					),
					'installment' => array(
					'title' => 'Taksit',
					'label' => 'Taksitli İşlem Aktif Mi? <br><strong>(Sadece Üye İş Yeri Ödeme Sayfası yönteminde çalışmaktadır.)</strong>',
					'type' => 'checkbox',
					'desc_tip' => 'Taksitli ödemeye izin verecekmisiniz?',
					'default' => 'yes',
				),
				'secure3dsecuritylevel' => array(
					'title' => '3D Güvenlik Seviyesi',
					'type' => 'select',
					'desc_tip' => 'Güvenlik Seviyesi',
					'default' => '3D_OOS_PAY',
					'options' => array(
						'3D_OOS_PAY' => '3D_OOS_PAY',
						'3D_OOS_FULL' => '3D_OOS_FULL',
						'3D_OOS_HALF' => '3D_OOS_HALF',
					),
					
				),
				'payment_page' => array(
					'title' => 'Ödeme Yöntemi',
					'type' => 'select',
					'desc_tip' => 'Ödeme Yönteminiz?',
					'default' => 'form',
					'options' => array(
						'shared3d' => 'Ortak Ödeme Sayfası (3DS li) ',
						'shared' => 'Ortak Ödeme Sayfası (3DS siz)',
						'form' => 'Üye İş Yeri Ödeme Sayfası (3DS siz)',
						'form3d' => 'Üye İş Yeri Ödeme Sayfası (3DS li)',
					),
					
				),
			);
		}

// End init_form_fields()

		public function process_payment($order_id)
		{
			global $woocommerce;
			$order = new WC_Order($order_id);
			if (version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=')) {
      
                $checkout_payment_url = $order->get_checkout_payment_url(true);
            } else {
         
                $checkout_payment_url = get_permalink(get_option('woocommerce_pay_page_id'));
            }
			

            return array(
                'result' => 'success',
                'redirect' => $checkout_payment_url,
            );
			
		}

//END process_payment



		public function credit_card_form($args = array(), $fields = array())
		{
			?>
			<p>Ödemenizi tüm kredi kartları ile yapabilirsiniz. </p>
			
		
			<?php
		}

		public function createSecret($key)
		{
			return sha1('garanti' . $key);
		}


		function pay($order_id)
		{
			
			global $woocommerce;
			get_currentuserinfo();
			$order = new WC_Order($order_id);
			require_once( plugin_dir_path(__FILE__) . 'includes/restHttpCaller.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/helper.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/Sale3DOOSPayRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/SaleOOSPayRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/Settings3D.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/Settings.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/SalesRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/VPOSRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/Sale3DSecureRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/Secure3DSuccessRequest.php');
		

			$user_meta = get_user_meta(get_current_user_id());
			$installment = (int) $_POST['garanti-installment-count']; 
				
			$amount = $order->order_total;
			$user_id = get_current_user_id();
		

			$garanti_settings = get_option("woocommerce_garanti_settings"); 
			
			$this->mode=$garanti_settings["mode"];
			$this->baseUrl=$garanti_settings["baseUrl"];
			$this->Password=$garanti_settings["Password"];
			$this->terminalId=$garanti_settings["terminalId"];
			$this->merchantId=$garanti_settings["merchantId"];
			$this->provUserId=$garanti_settings["provUserId"];
			$this->userId=$garanti_settings["userId"];
			$this->secure3dsecuritylevel=$garanti_settings["secure3dsecuritylevel"];
			$this->payment_page=$garanti_settings["payment_page"];
			
		
			$expire_date = explode('/', $_POST['garanti-card-expiry']);

				$record = array(
					'order_id' => $order_id,
					'customer_id' => $user_id,
					'garanti_id' => $order_id,
					'amount' => $amount,
					'amount_paid' => $amount,
					'installment' => $installment,
					'cardholdername' => $_POST['garanti-card-name'],
					'cardexpdate' => str_replace(' ', '', $expire_date[0]) . str_replace(' ', '', $expire_date[1]),
					'cardnumber' => substr($_POST['garanti-card-number'], 0, 6) . 'XXXXXXXX' . substr($_POST['garanti-card-number'], -2),
					'createddate' =>date("Y-m-d h:i:s"), 
					'ipaddress' =>  helper::get_client_ip(),
					'status_code' => 1, //default başarısız
					'result_code' => '', 
					'result_message' => '',
					'payment_page' =>  $this->payment_page,
					'shared_payment_url' => 'null',
					'formrefretnumber'=>'null'
				);

		
			if ($this->payment_page == 'form')
			{
			
			
			
			
				$settings=new Settings();
				
				$request = new SalesRequest();
				$request->Version = $this->version;
				$request->Mode = $this->mode;
				
				$settings->Version=$this->version;
				$settings->Mode=$this->version;
				$settings->BaseUrl=$this->baseUrl;
				$settings->Password=$this->Password;
				
				$request->Customer = new Customer();
				$request->Customer->EmailAddr="fatih@codevist.com";
				$request->Customer->IPAddress="127.0.0.1";
				
				$request->Card = new Card();
				$request->Card->CVV2=$_POST['garanti-card-cvc'];
				$request->Card->ExpireDate=str_replace(' ', '', $expire_date[0]) . str_replace(' ', '', $expire_date[1]);
				$request->Card->Number=str_replace(' ', '', $_POST['garanti-card-number']);
				
				$request->Order = new Order();
				$request->Order->OrderID=str_replace('-', '',helper::GUID());
				$request->Order->Description="";
				
				$request->Terminal= new Terminal();
				$request->Terminal->ProvUserID=$this->provUserId;
				$request->Terminal->UserID=$this->userId;
				$request->Terminal->ID=$this->terminalId;
				$request->Terminal->MerchantID=$this->merchantId;
				
				$request->Transaction = new Transaction();
				$request->Transaction->Amount=$amount* 100;
				$request->Transaction->Type="sales";
				$request->CurrencyCode="949";
				$request->MotoInd="N";
				
				$request->Hash=helper::ComputeHash($request,$settings);
			
			
				$record['shared_payment_url']='null';
				try {
				$response = SalesRequest::execute($request,$settings);

					
				} catch (Exception $e) {
					$record['result_code'] = 'ERROR';
					$record['result_message'] = $e->getMessage();
					$record['status_code'] = 1;
					return $record;
				}

				$sxml = new SimpleXMLElement($response);
				$record['status_code'] = $sxml->Transaction[0]->Response->Message;
				$record['result_code'] = $sxml->Transaction[0]->Response->ReasonCode;
				$record['result_message'] = helper::turkishreplace( $sxml->Transaction[0]->Response->ErrorMsg);
				$record['cardnumber'] = $sxml->Transaction[0]->CardNumberMasked;
				
				return $record;

			}
			elseif ($this->payment_page =='shared3d') //shared 3d ortak ödeme sayfası 3d 
			{
					
			    $settings3D=new Settings3D();
				$settings3D->mode=$this->mode;
				$settings3D->apiversion=$this->version;
				$settings3D->BaseUrl=$this->baseUrl;
				$settings3D->Password=$this->Password;
				
				
				$request = new Sale3DOOSPayRequest();
				$request->apiversion = $this->version;
				$request->mode = $this->mode;
			
			
				$request->terminalid=$this->terminalId;
				$request->terminaluserid=$this->userId;
				$request->terminalprovuserid =$this->provUserId;
				$request->terminalmerchantid = $this->merchantId;
			
			
			
				$request->successurl = $order->get_checkout_payment_url(true);
				$request->errorurl = $order->get_checkout_payment_url(true);
				$request->customeremailaddress = "fatih@codevist.com";
			
			if(helper::get_client_ip()=='::1')
			{
				$request->customeripaddress = "127.0.0.1";
				
			}
			else
			{
				$request->customeripaddress = helper::get_client_ip();
			}
				
				$request->secure3dsecuritylevel =$this->secure3dsecuritylevel;
				$request->orderid = str_replace('-', '',helper::GUID());
				$request->txnamount = $amount* 100;
				$request->txntype = "sales";
				$request->txninstallmentcount = "";
				$request->txncurrencycode = "949";
				$request->storekey = "12345678";
				$request->txntimestamp = date("d-m-Y H:i:s");
				$request->lang = "tr";
				$request->refreshtime = "10";
				$request->companyname = "deneme";

				
				$request->secure3dhash=Sale3DOOSPayRequest::Compute3DHash($request,$settings3D);
				
				
				
				try {
				$response = Sale3DOOSPayRequest::execute($request,$settings3D);
				print $response;

				} catch (Exception $e) {
			
					$record['result_code'] = 'ERROR';
					$record['result_message'] = $e->getMessage();
					$record['status_code'] = 1;
					return $record;
				}
	 $sxml = new SimpleXMLElement($response);
				$record['status_code'] = $sxml->Transaction[0]->Response->Message;
				$record['result_code'] = $sxml->Transaction[0]->Response->ReasonCode;
				$record['result_message'] = helper::turkishreplace( $sxml->Transaction[0]->Response->ErrorMsg);
				$record['cardnumber'] = $sxml->Transaction[0]->CardNumberMasked;
	 
	 

				return $record;

			}
			
			elseif ($this->payment_page =='form3d') //shared 3d ortak ödeme sayfası 3d 
			{
					
			    $settings3D=new Settings3D();
				$settings3D->mode=$this->mode;
				$settings3D->apiversion=$this->version;
				$settings3D->BaseUrl=$this->baseUrl;
				$settings3D->Password=$this->Password;
				
				$request = new Sale3DSecureRequest();
				$request->apiversion = $this->version;
				$request->mode = $this->mode;
			
			
				$request->terminalid=$this->terminalId;
				$request->terminaluserid=$this->userId;
				$request->terminalprovuserid =$this->provUserId;
				$request->terminalmerchantid = $this->merchantId;
			
			
			
				$request->successurl = $order->get_checkout_payment_url(true);
				$request->errorurl = $order->get_checkout_payment_url(true);
				$request->customeremailaddress = "fatih@codevist.com";
			
				if(helper::get_client_ip()=='::1')
				{
					$request->customeripaddress = "127.0.0.1";
					
				}
				else
				{
					$request->customeripaddress = helper::get_client_ip();
				}
				
				$request->secure3dsecuritylevel ="3D";
				$request->orderid = str_replace('-', '',helper::GUID());
				$request->txnamount = $amount* 100;
				$request->txntype = "sales";
				$request->txninstallmentcount = "";
				$request->txncurrencycode = "949";
				$request->storekey = "12345678";
				$request->txntimestamp = date("d-m-Y H:i:s");
				$request->cardnumber = str_replace(' ', '', $_POST['garanti-card-number']);
				$request->cardexpiredatemonth = str_replace(' ', '', $expire_date[0]);
				$request->cardexpiredateyear = str_replace(' ', '', $expire_date[1]);
				$request->cardcvv2 = $_POST['garanti-card-cvc'];
				$request->lang = "tr";
				$request->refreshtime = "10";
				
				
				
				$request->secure3dhash=Sale3DSecureRequest::Compute3DHash($request,$settings3D);
				
				
				
				try {
				$response = Sale3DSecureRequest::execute($request,$settings3D);
				print($response);
				
				} catch (Exception $e) {
			
					$record['result_code'] = 'ERROR';
					$record['result_message'] = $e->getMessage();
					$record['status_code'] = 1;
					return $record;
				}
	 
	 $sxml = new SimpleXMLElement($response);
				$record['status_code'] = $sxml->Transaction[0]->Response->Message;
				$record['result_code'] = $sxml->Transaction[0]->Response->ReasonCode;
				$record['result_message'] = helper::turkishreplace( $sxml->Transaction[0]->Response->ErrorMsg);
				$record['cardnumber'] = $sxml->Transaction[0]->CardNumberMasked;
	 

				 return $record;

			}
			
			
			else
			{ //shared
		
		
		
		
		        $settings3D=new Settings3D();
				$settings3D->mode=$this->mode;
				$settings3D->apiversion=$this->version;
				$settings3D->BaseUrl=$this->baseUrl;
				$settings3D->Password=$this->Password;
				
				
				$request = new SaleOOSPayRequest();
				$request->apiversion = $this->version;
				$request->mode = $this->mode;
			
			
				$request->terminalid=$this->terminalId;
				$request->terminaluserid=$this->userId;
				$request->terminalprovuserid =$this->provUserId;
				$request->terminalmerchantid = $this->merchantId;
			
			
			
				$request->successurl = $order->get_checkout_payment_url(true);
				$request->errorurl = $order->get_checkout_payment_url(true);
				$request->customeremailaddress = "fatih@codevist.com";
			
			if(helper::get_client_ip()=='::1')
			{
				$request->customeripaddress = "127.0.0.1";
				
			}
			else
			{
				$request->customeripaddress = helper::get_client_ip();
			}
		
		$request->secure3dsecuritylevel = "OOS_PAY";
		$request->orderid = str_replace('-', '',helper::GUID());
		$request->txnamount = $amount* 100;
		$request->txntype = "sales";
		$request->txninstallmentcount = "";
		$request->txncurrencycode = "949";
		$request->storekey = "12345678";
		$request->txntimestamp = date("d-m-Y H:i:s");
		$request->lang = "tr";
		$request->refreshtime = "10";
		$request->companyname = "deneme";
		$request->secure3dhash=SaleOOSPayRequest::Compute3DHash($request,$settings3D);
		
		
		
				
	

				try {
				
					$response = SaleOOSPayRequest::execute($request,$settings3D);
					print $response;

				} catch (Exception $e) {
			
					$record['result_code'] = 'ERROR';
					$record['result_message'] = $e->getMessage();
					$record['status_code'] = 1;
					return $record;
				}
	$sxml = new SimpleXMLElement($response);
				$record['status_code'] = $sxml->Transaction[0]->Response->Message;
				$record['result_code'] = $sxml->Transaction[0]->Response->ReasonCode;
				$record['result_message'] = helper::turkishreplace( $sxml->Transaction[0]->Response->ErrorMsg);
				$record['cardnumber'] = $sxml->Transaction[0]->CardNumberMasked;
			
				
				return $record;
			}
			
			
			
		}
		
		function receipt_page($orderid)
		{
			global $woocommerce;
			$error_message = false;
			$order = new WC_Order($orderid);
			$cc_form_key = $this->createSecret($orderid);
			$status = $order->get_status();
			require_once( plugin_dir_path(__FILE__) . 'includes/helper.php');
			if($status != 'pending')
			{
				return 'ok';
			}
			if(isset($_POST['cc_form_key']) AND $_POST['cc_form_key'] == $this->createSecret($orderid)) { //form ile direk ödeme
				
				$record = $this->pay($orderid);//form post edildiğinde 
								
				$this->saveRecord($record);
				
				if($record['status_code'] == 'Approved' ) {//Başarılı işlem
			
					$order->update_status('processing', __('Processing Garanti payment', 'woocommerce'));
					$order->add_order_note('Ödeme Garanti ile tamamlandı. İşlem no: #' . $record['garanti_id'] . ' Tutar ' . $record['amount'] . ' Taksit: ' . $record['installment'] . ' Ödenen:' . $record['amount_paid']);
					$order->payment_complete();
					$woocommerce->cart->empty_cart(); 
					wp_redirect($this->get_return_url()); 
					exit;
					$error_message = false;
				}
				else { //Başarısız işlem
					$order->update_status('pending', 'Ödeme Doğrulanamadı, tekrar deneyiniz. ', 'woocommerce');
					$error_message = 'Ödeme başarısız oldu: Bankanızın cevabı: ('. $record['result_message'] . ') ' . $record['result_message'];
				}				
			}	
			elseif (isset($_POST['clientid']) && isset($_POST['companyname'])) { //Ortak ödemeden gelirse. 
				$record = $this->getRecordByOrderId($_POST['orderid']);
				if(!empty($_POST['mdstatus']))
				{
					$record['status_code'] = $_POST['mdstatus'];
				}
				else
				{
				$record['status_code'] = '';
				}
				if(!empty($_POST['mderrormessage']))
				{
					$record['result_code'] = $_POST['mderrormessage'];
				}
				else
				{
				$record['result_code'] = '';
				}
				$record['result_message'] =$_POST['response'];		
				$record['amount']=$_POST['txnamount'] / 100;
				$this->saveRecord($record);	
					if($record['result_message'] == 'Approved' ) {//Başarılı işlem
					$hash=$_POST["hash"];
					$hashParamsVal="";
					$storeKey= "12345678";
					$hashParams=$_POST["hashparams"];
					$valid=false;
			//Validasyon 
				if (!empty($hashParams)) 
				{
		   $result= explode(":",$hashParams);
		  
			foreach ($result as $key) 
			{
				if(!empty($key))
				$hashParamsVal .= $_POST[$key];
				
			
			}
			$hashParamsVal .= $storeKey;
			
			$valid=helper::Validate3DReturn($hashParamsVal,$hash);
	   }

   if($valid==true){
					$order->update_status('processing', __('Processing Garanti payment', 'woocommerce'));
					$order->add_order_note('Ödeme Garanti posu ile tamamlandı. İşlem no: #' . $record['garanti_id'] . ' Tutar ' . $record['amount'] . ' Taksit: ' . $record['installment'] . ' Ödenen:' . $record['amount_paid']);
					$order->payment_complete();
					$woocommerce->cart->empty_cart(); 
					 
					wp_redirect($this->get_return_url());
					$this->saveRecord($record);
					exit;
					$error_message = false;
   }
   else
	   $error_message = 'Ödeme başarısız oldu: Validasyon Hatalı';
				}
				else { //Başarısız işlem
					$order->update_status('pending', 'Ödeme Doğrulanamadı, tekrar deneyiniz. ', 'woocommerce');
					$error_message = 'Ödeme başarısız oldu!';
				}
			}
			
			
			
			elseif(isset($_POST['clientid']) && !isset($_POST['companyname'])){//3dform
				 
				$hash=$_POST["hash"];
				$hashParamsVal="";
				$storeKey= "12345678";
				$hashParams=$_POST["hashparams"];
				$valid=false;
				//Validasyon 
	   if (!empty($hashParams))
	   {
		   $result= explode(":",$hashParams);
		  
			foreach ($result as $key) 
			{
				if(!empty($key))
				$hashParamsVal .= $_POST[$key];
				
			
			}
			$hashParamsVal .= $storeKey;
			
			$valid=helper::Validate3DReturn($hashParamsVal,$hash);
	   }
	if($valid==true)
	{
		
	   
		 if(($_POST["mdstatus"]=="1") or ($_POST["mdstatus"]=="2") || ($_POST["mdstatus"]=="3") || ($_POST["mdstatus"]=="4"))
			{
			
			require_once( plugin_dir_path(__FILE__) . 'includes/restHttpCaller.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/helper.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/Sale3DOOSPayRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/SaleOOSPayRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/Settings3D.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/Settings.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/SalesRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/VPOSRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/Sale3DSecureRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/Secure3DSuccessRequest.php');

		

			$request=new Secure3DSuccessRequest();
			$settings=new Settings(); //Garanti tarafından sağlanan bilgiler ile değiştirilmelidir.
			$settings->Version="V0.1";
			$settings->Mode="Test";
			$settings->BaseUrl="https://sanalposprovtest.garanti.com.tr/VPServlet";
			$settings->Password="123qweASD/";
			
			$request->Mode=$_POST["mode"];
			$request->Version=$_POST["apiversion"];
		
			$request->Terminal= new Terminal();

			$request->Terminal->ID=$_POST["clientid"];
			$request->Terminal->MerchantID=$_POST["terminalmerchantid"];
			$request->Terminal->ProvUserID=$_POST["terminalprovuserid"];
			$request->Terminal->UserID=$_POST["terminaluserid"];
			
				
			$request->Card = new Card();

			$request->Card->CVV2="";
			$request->Card->ExpireDate="";
			$request->Card->Number="";
			
			$request->Customer = new Customer();

			$request->Customer->EmailAddr=$_POST["customeremailaddress"];
			$request->Customer->IPAddress=$_POST["customeripaddress"];
			
			$request->AuthenticationCode=$_POST["cavv"];
			$request->Md=$_POST["md"];
			$request->SecurityLevel=$_POST["eci"];
			$request->TxnID=$_POST["xid"];
			
			$request->Order = new Order();

			$request->Order->OrderID=$_POST["orderid"];
			$request->Order->Description="";
			
			$request->Transaction = new Transaction();

			$request->Transaction->Amount=$_POST["txnamount"];
			$request->Transaction->Type=$_POST["txntype"];
			$request->CurrencyCode=$_POST["txncurrencycode"];
			$request->InstallmentCnt=$_POST["txninstallmentcount"];
			$request->MotoInd="N";
			$request->CardholderPresentCode=13;
			
				$request->Hash=helper::ComputeHash($request,$settings);

				 $response = Secure3DSuccessRequest::execute($request,$settings);
				 
		 $sxml = new SimpleXMLElement( $response);
			
				 
				 $statusOk =$sxml->Transaction[0]->Response->Message;
				$result_message = helper::turkishreplace( $sxml->Transaction[0]->Response->ErrorMsg);
				$cardnumber = $sxml->Transaction[0]->CardNumberMasked;
				$formrefretnumber =$sxml->Transaction[0]->RetrefNum;
				echo $statusOk;
				
				 if($statusOk=='Approved')
				 {
				$order->update_status('processing', __('Processing Garanti payment', 'woocommerce'));
					$order->add_order_note('Ödeme Garanti ile tamamlandı. İşlem no: #' . $record['garanti_id'] . ' Tutar ' . $record['amount'] . ' Taksit: ' . $record['installment'] . ' Ödenen:' . $record['amount_paid']);
					$order->payment_complete();
					$woocommerce->cart->empty_cart(); 
					wp_redirect($this->get_return_url()); 
					exit;
					$error_message = false;
				}
				else { //Başarısız işlem
					$order->update_status('pending', 'Ödeme Doğrulanamadı, tekrar deneyiniz. ', 'woocommerce');
					$error_message = 'Ödeme başarısız oldu: Bankanızın cevabı: ('. $result_message . ') ';
				}
			}else { //Başarısız işlem
					$order->update_status('pending', 'Ödeme Doğrulanamadı, tekrar deneyiniz. ', 'woocommerce');
					$error_message = 'Ödeme başarısız oldu!' ;
				}
	}else { //Başarısız işlem
					$order->update_status('pending', 'Ödeme Doğrulanamadı, tekrar deneyiniz. ', 'woocommerce');
					$error_message = 'Ödeme başarısız oldu: Validasyon Hatalı:';
				}
			}
	
			include(dirname(__FILE__).'/payform.php');
		}
	
		private function addRecord($record)
		{
	
			global $wpdb;
			$wpdb->insert($wpdb->prefix . 'garanti', $record);
		}


		private function updateRecordByOrderId($record)
		{
			global $wpdb;
			$wpdb->update($wpdb->prefix . 'garanti', $record, array('order_id' => (int) $record['order_id']));
		}
		public function saveRecord($record)
		{		
			if (isset($record['order_id'])
					AND $record['order_id']
					AND $this->getRecordByOrderId($record['order_id']))
				return $this->updateRecordByOrderId($record);
			return $this->addRecord($record);
		}

		public function getRecordByOrderId($order_id)
		{
			global $wpdb;
			return $wpdb->get_row('SELECT * FROM `' . $wpdb->prefix . 'garanti` WHERE `order_id` = ' . (int) $order_id, ARRAY_A);
		}
	}

	//END Class garanti

	function garanti($methods)
	{
		$methods[] = 'garanti';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'garanti');


}
