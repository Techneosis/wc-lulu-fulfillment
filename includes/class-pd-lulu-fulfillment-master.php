<?php

/**
 * Handle Settings, Updates, Anything Global from this class.
 *
 * @link       www.peacefuldev.com
 * @since      1.3.0
 *
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run plugin's global functionality
 *
 * @since      1.3.0
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/includes
 * @author     Brett Parshall <brett@peacefuldev.com>
 */
class PD_Lulu_Fulfillment_Master {
	
	/**
	 * The sole instance of the class
	 * 
	 * @var PD_Lulu_Fulfillment_Master
	 * @since 1.3.0
	 */
	protected static $_instance = null;

	protected static $db_updates = array(
		'1.3.0' => array(
			'lulu_update_130_product_pdf_urls'
		)
	);

	/**
	 * Sole PD_Lulu_Fulfillment_Master
	 *
	 * Ensures only one instance of PD_Lulu_Fulfillment_Master is loaded or can be loaded.
	 *
	 * @since    1.3.0
	 * @return PD_Lulu_Fulfillment_Master Sole Instance
	 */
	public static function instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Retrieve front-end friendly Lulu print-job status.
	 * 
	 * @param $status Fulfillment status from Lulu API
	 * @return string Localized front-end status
	 * @since 1.3.0
	 */
	public function getFulfillmentStatus($status){
		if(!$status) {
			return __('N/A', PD_LULU_FULFILLMENT_DOMAIN);
		}
		
		$ret = '';
		switch($status) {
			case 'CREATED':
				$ret = __('Created', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'REJECTED':
				$ret = __('Rejected', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'UNPAID':
				$ret = __('Unpaid', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'PAYMENT_IN_PROGRESS':
				$ret = __('Payment In Progress', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'PRODUCTION_DELAYED':
				$ret = __('Production Delayed', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'PRODUCTION_READY':
				$ret = __('Production Ready', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'IN_PRODUCTION':
				$ret = __('In Production', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'SHIPPED':
				$ret = __('Shipped', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'ERROR':
				$ret = __('Error', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'CANCELED':
				$ret = __('Canceled', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			default:
				$ret = __('Unknown', PD_LULU_FULFILLMENT_DOMAIN);
				break;
		}

		return $ret;
	}

	/**
	 * Retrieve front-end friendly Lulu print-job status description.
	 * 
	 * @param $status Fulfillment status from Lulu API
	 * @return string Localized front-end status description
	 * @since 1.3.0
	 */
	public function getFulfillmentStatusDescription($status) {
		if(!$status) {
			return __('No Print-Job', PD_LULU_FULFILLMENT_DOMAIN);
		}
		$ret = '';
		switch($status) {
			case 'CREATED':
				$ret = __('Print-Job created.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'Rejected':
				$ret = __('Print-Job rejected before production could begin.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'UNPAID':
				$ret = __('Print-Job can be paid.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'PAYMENT_IN_PROGRESS':
				$ret = __('Payment is in progress.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'PRODUCTION_DELAYED':
				$ret = __('Print-Job is paid and will move to production after the mandatory production delay.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'PRODUCTION_READY':
				$ret = __('Production delay has ended and the Print-Job will move to "in production" shortly.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'IN_PRODUCTION': 
				$ret = __('Print-Job submitted to printer.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'SHIPPED':
				$ret = __('Print-Job is fully shipped.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'ERROR':
				$ret = __('Error encountered during print-job production.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			case 'CANCELED':
				$ret = __('Print-Job canceled prior to production.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
			default:
				$ret = __('Print-Job status is unknown.', PD_LULU_FULFILLMENT_DOMAIN);
				break;
		}
		
		return $ret;
	} 
}
