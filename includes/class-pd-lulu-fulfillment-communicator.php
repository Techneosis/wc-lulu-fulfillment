<?php

/**
 * One Instance at a time
 *
 * This class defines all code necessary authenticate with and communicate to the various Lulu Endpoints.
 *
 * @since      1.1.0
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/includes
 * @author     Brett Parshall <Brettparshall@gmail.com>
 */

if (!defined('ABSPATH')) {
	exit;
}

class PD_Lulu_Fulfillment_Communicator
{

	/**
	 * The sole instance of the class
	 * 
	 * @var PD_Lulu_Fulfillment_Communicator
	 * @since 1.1.0
	 */
	protected static $_instance = null;

	/**
	 * Sole PD_Lulu_Fulfillment_Communicator
	 *
	 * Ensures only one instance of PD_Lulu_Fulfillment_Communicator is loaded or can be loaded.
	 *
	 * @since    1.1.0
	 * @return PD_Lulu_Fulfillment_Communicator Sole Instance
	 */
	public static function instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Whether the sandbox or production Lulu api should be the one to be contacted
	 */
	protected $sandboxed = true;

	/**
	 * Base 64 encoded Key & Secret for use with Lulu's sandbox api
	 * 
	 * @see https://api.sandbox.lulu.com/user-profile/api-keys
	 * @since	1.1.0
	 */
	protected $sandboxApiKey = null;

	/**
	 * Base 64 encoded Key & Secret for use with Lulu's api
	 * 
	 * @see https://api.lulu.com/user-profile/api-keys
	 * @since	1.1.0
	 */
	protected $apiKey = null;

	/**
	 * Base URL for Lulu's sandbox api
	 */
	public static $_baseSandboxApiUrl = 'https://api.sandbox.lulu.com';

	/**
	 * Base URL for Lulu's api
	 */
	public static $_baseApiUrl = 'https://api.lulu.com';

	/**
	 * Named array of Lulu's api endpoints
	 * 
	 * Used by the various communication functions in conjunction with the proper base api url depending on plugin settings.
	 */
	public static $_apiEndpoints = [
		'auth-token' => '/auth/realms/glasstree/protocol/openid-connect/token',
		'print-job-cost-calculations' => '/print-job-cost-calculations/',
		'print-jobs' => '/print-jobs/',
		'print-job-statistics' => '/print-jobs/statistics/',
		'print-job' => '/print-job/{id}/',
		'print-job-costs' => '/print-job/{id}/costs/',
		'print-job-status' => '/print-jobs/{id}/status/',
		'shipping-options' => '/print-shipping-options/'
	];

	/**
	 * Initialize Communicator
	 */
	public function __construct()
	{
		$this->loadSettings();
	}

	/**
	 * Load Settings
	 */
	public function loadSettings()
	{
		$pluginOptions = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$this->sandboxed = isset($pluginOptions['lulu_api']) ? $pluginOptions['lulu_api'] == 'sandbox' : true;

		$sandboxApiKey = isset($pluginOptions['sandbox_api_key']) ? $pluginOptions['sandbox_api_key'] : null;
		$apiKey = isset($pluginOptions['production_api_key']) ? $pluginOptions['production_api_key'] : null;
		$this->apiKey = $this->sandboxed ? $sandboxApiKey : $apiKey;
	}

	function _getEndpoint($endpointName)
	{
		if (!isset(self::$_apiEndpoints[$endpointName])) {
			return false;
		}

		$endpointUrl = $this->sandboxed ? self::$_baseSandboxApiUrl : self::$_baseApiUrl;
		$endpointUrl .= self::$_apiEndpoints[$endpointName];
		return $endpointUrl;
	}

	/**
	 * Authenticate with the Lulu api using the api keys stored in memory
	 */
	public function getAuthenticatedRequestArgs()
	{
		$accessToken = get_transient('PD_Lulu_Fulfillment_access_token');
		$refreshToken = get_transient('PD_Lulu_Fulfillment_refresh_token');

		if(!$accessToken && !$refreshToken) {
			$authArgs = array(
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
					'Authorization' => $this->apiKey
				),
				'body' => array(
					'grant_type' => 'client_credentials'
				)
			);
	
			$authUrl = $this->_getEndpoint('auth-token');
			$authRequest = wp_remote_post($authUrl,  $authArgs);
			$authRequestBody = json_decode($authRequest['body']);
	
			// $accessToken = $authRequestBody->access_token;
			// $expiresIn = $authRequestBody->expires_in;
			// $refreshExpiresIn = $authRequestBody->refresh_expires_in;
			// $refreshToken = $authRequestBody->refresh_token;
			// $tokenType = $authRequestBody->token_type;
			// $notBeforePolicy = $authRequestBody->notefore-policy'];
			// $sessionState = $authRequestBody->session_state;
			// $scope = $authRequestBody->scope;
	
			$accessToken = $authRequestBody->access_token;
			$refreshToken = $authRequestBody->refresh_token;
			// Only use 3/4 the expiration time to avoid awkward timing issues, probably not necessary but just in case
			set_transient('PD_Lulu_Fulfillment_access_token', $authRequestBody->access_token, $authRequestBody->expires_in / 4 * 3);
			set_transient('PD_Lulu_Fulfillment_refresh_token', $authRequestBody->refresh_token, $authRequestBody->refresh_expires_in / 4 * 3);
		}
		else if(!$accessToken && $refreshToken) {
			$authArgs = array(
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
					'Authorization' => $this->apiKey
				),
				'body' => array(
					'grant_type' => 'refresh_token',
					'refresh_token' => $refreshToken
				)
			);
			$authUrl = $this->_getEndpoint('auth-token');
			$authRequest = wp_remote_post($authUrl,  $authArgs);
	
			$authRequestBody = json_decode($authRequest['body']);

			$accessToken = $authRequestBody->access_token;
			$refreshToken = $authRequestBody->refresh_token;
			set_transient('PD_Lulu_Fulfillment_access_token', $accessToken, $authRequestBody->expires_in / 4 * 3);
			set_transient('PD_Lulu_Fulfillment_refresh_token', $refreshToken, $authRequestBody->refresh_expires_in / 4 * 3);
		}

		return array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $accessToken,
				'Content-Type' => 'application/json'
			),
			'body' => ''
		);
	}

	/**
	 * Create a print job for Lulu to fulfill a customer order.
	 * 
	 * @param WC_Order $order Customer's Order
	 * @return void
	 * 
	 * @since 1.0.0
	 * @version 1.3.0 Use new Cover/Interior PDF meta on products
	 */
	public function createPrintJob($order)
	{
		/* Create Print Job Line Items */
		$luluLineItems = array();
		foreach ($order->get_items() as $lineItem) {
			$lineItemData = $lineItem->get_data();
			if (!$lineItemData['product_id']) {
				continue;
			}

			$product = wc_get_product($lineItemData['product_id']);
			if ($product->is_type('lulu4woocommerce')) {
				$productMeta = $product->get_meta_data();
				$podPackageId = null;
				$coverPdfUrl = null;
				$interiorPdfUrl = null;
				foreach ($productMeta as $meta) {
					$tData = $meta->get_data();
					switch ($tData['key']) {
						case "lulu_pod_package_id":
							$podPackageId = $tData['value'];
							break;
						case "lulu_cover_pdf_attachment_id":
							$coverPdfUrl = wp_get_attachment_url( $tData['value'] );
							break;
						case "lulu_interior_pdf_attachment_id":
							$interiorPdfUrl = wp_get_attachment_url( $tData['value'] );
							break;
					}
				}

				if (!($podPackageId  && $coverPdfUrl && $interiorPdfUrl)) {
					// They're not all set, there's a problem
					continue;
				}
				// Grab data required to be sent to Lulu, add to luluLineItems array
				$luluLineItem = array();
				$luluLineItem['external_id'] = $lineItemData['id'];
				$luluLineItem['quantity'] = $lineItemData['quantity'];
				$luluLineItem['title'] = $product->get_name();

				/* Printable Normalizatation */
				$printableNormalization = array();

				$cover = array();
				$cover['source_url'] = $coverPdfUrl;
				$printableNormalization['cover'] = $cover;

				$interior = array();
				$interior['source_url'] = $interiorPdfUrl;
				$printableNormalization['interior'] = $interior;

				$printableNormalization['pod_package_id'] = $podPackageId;

				$luluLineItem['printable_normalization'] = $printableNormalization;
				/* End Printable Normalization */

				$luluLineItems[] = $luluLineItem;
			} else {
				// This is not a lulu product. Ignore
			}
		}
		/* End Print Job Line Items */

		/* Gather Shipping Address Information */
		$shippingAddress = array();
		$shippingAddress['city'] = $order->get_shipping_city();
		$shippingAddress['country_code'] = $order->get_shipping_country();
		$shippingAddress['state_code'] = $order->get_shipping_state();
		$shippingAddress['name'] = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
		$shippingAddress['phone_number'] = $order->get_billing_phone();
		$shippingAddress['postcode'] = $order->get_shipping_postcode();
		$shippingAddress['street1'] = $order->get_shipping_address_1();
		$shippingAddress['street2'] = $order->get_shipping_address_2();
		/* End Gather Shipping Address Information */

		if (sizeof($luluLineItems)) {
			//Compile lulu request
			$currDateTime = wp_date('Y-m-d');
			$pluginOptions = get_option(PD_LULU_FULFILLMENT_OPTIONS);

			$luluPrintJob['contact_email'] = $pluginOptions['contact_email'];
			$luluPrintJob['external_id'] = $order->get_id();
			$luluPrintJob['line_items'] = $luluLineItems;
			$luluPrintJob['production_delay'] = 120;
			$luluPrintJob['shipping_address'] = $shippingAddress;
			$luluPrintJob['shipping_level'] = "MAIL"; //TODO: Handle Shipping properly

			// Print Job Payload created, now send it away.
			$printArgs = $this->getAuthenticatedRequestArgs();
			$printArgs['body'] = wp_json_encode($luluPrintJob);

			$printUrl = $this->_getEndpoint('print-jobs');
			$printJobRequest = wp_remote_post($printUrl, $printArgs);

			if ($printJobRequest && $printJobRequest['response'] && $printJobRequest['response']['code'] == "201") {
				// Print-Job successfully created, store that information on the order
				$printJobBody = json_decode($printJobRequest['body']);

				$order->update_meta_data('lulu_print_job_id', $printJobBody->id);
				$order->save();
			}
		}
	}

	/**
	 * Retrieve information about a print job by id
	 * @param INT $printJobId Lulu's Unique ID for a print job
	 * @return object
	 * 
	 */
	public function getPrintJobStatus($printJobId)
	{
		$statusArgs = $this->getAuthenticatedRequestArgs();

		$endpointUrl = $this->_getEndpoint('print-job-status');
		$endpointUrl = str_replace('{id}', $printJobId, $endpointUrl);
		$printStatusRequest = wp_remote_get( $endpointUrl, $statusArgs );
		if ($printStatusRequest && $printStatusRequest['response'] && $printStatusRequest['response']['code'] == "200") {
			$printStatusBody = json_decode($printStatusRequest['body']);
			return $printStatusBody;
		}
		return null;
	}

	/**
	 * Retrieve print cost for a package of books
	 * 
	 * @param WooCommerce_Package $package A Package of line items that are shipped together, typically from the cart.
	 * @return PD_Lulu_Fulfillment_Print_Job_Cost_Calculation Cost calculation information from Lulu
	 * 
	 * @since 1.2.0
	 * @version 1.3.0 More Comprehensive returns + error info
	 */
	public function getPrintJobCostCalculation($package) {
		$costArgs = $this->getAuthenticatedRequestArgs();

		$calcRequestBody = array();
		
		$calcRequestBody['line_items'] = array();
		foreach($package['contents'] as $lineItem) {
			$_product = $lineItem['data'];
			if($_product->get_type() == 'lulu4woocommerce') {
				$productMeta = $_product->get_meta_data();
				$podPackageId = null;
				$pageCount = null;
				foreach ($productMeta as $meta) {
					$tData = $meta->get_data();
					switch ($tData['key']) {
						case "lulu_pod_package_id":
							$podPackageId = $tData['value'];
							break;
						case "lulu_page_count":
							$pageCount = $tData['value'];
							break;
					}
				}
				$calcRequestBody['line_items'][] = array(
					'page_count' => $pageCount,
					'pod_package_id' => $podPackageId,
					'quantity' => $lineItem['quantity'],
				);
			}
		}

		$calcRequestBody['shipping_address'] = array(
			'city' => $package['destination']['city'],
			'country_code' => $package['destination']['country'],
			'postcode' => $package['destination']['postcode'],
			'state_code' => $package['destination']['state'],
			'street1' => $package['destination']['address_1'] ? $package['destination']['address_1'] : 'x', // Lulu requires street address to be set but 
            'street2' => $package['destination']['address_2'],
            'phone_number' => '1111111111' // Lulu Requires this field for shipping cost calculations now
		);
		$calcRequestBody['shipping_level'] = 'MAIL';
		$costArgs['body'] = wp_json_encode($calcRequestBody);
	
		$currDateTime = wp_date('Y-m-d');

		$costUrl = $this->_getEndpoint('print-job-cost-calculations');
		$costRequest = wp_remote_post($costUrl, $costArgs);
		return new PD_Lulu_Fulfillment_Print_Job_Cost_Calculation($costRequest);
	}
}

/**
 * Scaffolding class for Lulu print-job cost calculation returns
 * 
 * @since 1.3.0
 */
class PD_Lulu_Fulfillment_Print_Job_Cost_Calculation {
	public $is_success;
	public $errors;

	public $line_item_costs;
	public $shipping_cost;
	public $total_tax;
	public $total_cost_excl_tax;
	public $total_cost_incl_tax;
	public $total_discount_amount;
	public $currency;
	public $fees;
	
	public function __construct($request)
	{
		if(is_wp_error($request)) {
			$this->is_success = false;
			$this->errors = $request->errors;
		}
		else if (!$request['response']) {
			$this->is_success = false;
			$this->errors = array(
				'Error' => 'No Response Received from Lulu'
			);
		}
		else {
			$body = json_decode($request['body']);
			switch($request['response']['code']) {
				case '401':
				case '403':
					$this->is_success = false;
					$this->errors = array(
						$request['response']['code'] => $body->detail
					);
					break;
				case '400':
					$this->is_success = false;
                    $this->errors = $body;
					break;
				case '201':
					$this->is_success = true;

					$this->line_item_costs = array();
					foreach($body->line_item_costs as $line_item_cost) {
						$this->line_item_costs[] = new PD_Lulu_Fulfillment_Print_Job_Cost_Line_Item($line_item_cost);
					}
					
					$this->fees = array();
					foreach($body->fees as $fee){
						$this->fees[] = new PD_Lulu_Fulfillment_Print_Job_Fee($fee);
					}

					$this->shipping_cost = new PD_Lulu_Fulfillment_Print_Job_Cost_Shipping($body->shipping_cost);
					$this->total_tax = $body->total_tax;
					$this->total_cost_excl_tax = $body->total_cost_excl_tax;
					$this->total_cost_incl_tax = $body->total_cost_incl_tax;
					$this->total_discount_amount = $body->total_discount_amount;
					$this->currency = $body->currency;
			}
		}
	}
}

/**
 * Scaffolding class for Lulu print-job cost-calculation line items
 * 
 * @since 1.3.0
 */
class PD_Lulu_Fulfillment_Print_Job_Cost_Line_Item {
	public $cost_excl_discounts;
	public $total_tax;
	public $tax_rate;
	public $quantity;
	public $total_cost_excl_tax;
	public $total_cost_excl_discounts;
	public $total_cost_incl_tax;
	public $discounts;
	public $unit_tier_cost;

	public function __construct($stdObj) {
		$this->cost_excl_discounts = $stdObj->cost_excl_discounts;
		$this->total_tax = $stdObj->total_tax;
		$this->tax_rate = $stdObj->tax_rate;
		$this->quantity = $stdObj->quantity;
		$this->total_cost_excl_tax = $stdObj->total_cost_excl_tax;
		$this->total_cost_excl_discounts = $stdObj->total_cost_excl_discounts;
		$this->total_cost_incl_tax = $stdObj->total_cost_incl_tax;
		$this->discounts = $stdObj->discounts;
		$this->unit_tier_cost = $stdObj->unit_tier_cost;
	}
}

/**
 * Scaffolding class for Lulu print-job cost calculation shipping items
 * 
 * @since 1.3.0
 */
class PD_Lulu_Fulfillment_Print_Job_Cost_Shipping {
	public $total_cost_excl_tax;
	public $total_cost_incl_tax;
	public $total_tax;
	public $tax_rate;

	public function __construct($stdObj) {
		$this->total_cost_excl_tax = $stdObj->total_cost_excl_tax;
		$this->total_cost_incl_tax = $stdObj->total_cost_incl_tax;
		$this->total_tax = $stdObj->total_tax;
		$this->tax_rate = $stdObj->tax_rate;
	}
}

class PD_Lulu_Fulfillment_Print_Job_Fee {
	public $currency;
	public $fee_type;
	public $sku;
	public $tax_rate;
	public $total_cost_excl_tax;
	public $total_cost_incl_tax;
	public $total_tax;

	public function __construct($stdObj) {
		$this->currency = $stdObj->currency;
		$this->fee_type = $stdObj->fee_type;
		$this->sku = $stdObj->sku;
		$this->tax_rate = $stdObj->tax_rate;
		$this->total_cost_excl_tax = $stdObj->total_cost_excl_tax;
		$this->total_cost_incl_tax = $stdObj->total_cost_incl_tax;
		$this->total_tax = $stdObj->total_tax;
	}
}
