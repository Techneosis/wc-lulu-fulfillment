<?php
if (!defined('WPINC')) {
    die('No script kiddies, please!');
}

/**
 * Defines the Lulu Shipping Method, which charges the customer the same as whatever Lulu is charging for the shipping.
 * 
 * @since 1.2.0
 */
class WC_Shipping_Lulu extends WC_Shipping_Method
{

	/**
	 * Cost passed to [fee] shortcode.
	 *
	 * @var string Cost.
	 */
	protected $fee_cost = '';

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping method instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
        $this->id                   = 'pd_lulu_shipping';
        $this->instance_id          = absint( $instance_id);
        $this->method_title         = __('Lulu Shipping', PD_LULU_FULFILLMENT_DOMAIN);
        $this->method_description   = __('Shipping Costs Provided By Lulu', PD_LULU_FULFILLMENT_DOMAIN);
        $this->supports             = array(
            // 'settings',
			// 'shipping-zones',
			// 'instance-settings',
			// 'instance-settings-modal',
        );
        $this->init();
	}

	/**
	 * Init user set variables.
	 */
	public function init() {
		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$title = isset($options['shipping_package_label']) && $options['shipping_package_label'] ? $options['shipping_package_label'] : __('Print On Demand Shipping', PD_LULU_FULFILLMENT_DOMAIN);
        $this->title = $title;
        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';

        $this->init_form_fields();
        $this->init_settings();
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
	}

    function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable', PD_LULU_FULFILLMENT_DOMAIN),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', PD_LULU_FULFILLMENT_DOMAIN),
                'type' => 'text',
                'default' => __('Lulu Shipping', PD_LULU_FULFILLMENT_DOMAIN)
            ),
        );
    }

	/**
	 * Evaluate a cost from a sum/string.
	 *
	 * @param  string $sum Sum of shipping.
	 * @param  array  $args Args, must contain `cost` and `qty` keys. Having `array()` as default is for back compat reasons.
	 * @return string
	 */
	protected function evaluate_cost( $sum, $args = array() ) {
		// Add warning for subclasses.
		if ( ! is_array( $args ) || ! array_key_exists( 'qty', $args ) || ! array_key_exists( 'cost', $args ) ) {
			wc_doing_it_wrong( __FUNCTION__, '$args must contain `cost` and `qty` keys.', '4.0.1' );
		}

		include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';

		// Allow 3rd parties to process shipping cost arguments.
		$args           = apply_filters( 'woocommerce_evaluate_shipping_cost_args', $args, $sum, $this );
		$locale         = localeconv();
		$decimals       = array( wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',' );
		$this->fee_cost = $args['cost'];

		// Expand shortcodes.
		add_shortcode( 'fee', array( $this, 'fee' ) );

		$sum = do_shortcode(
			str_replace(
				array(
					'[qty]',
					'[cost]',
				),
				array(
					$args['qty'],
					$args['cost'],
				),
				$sum
			)
		);

		remove_shortcode( 'fee', array( $this, 'fee' ) );

		// Remove whitespace from string.
		$sum = preg_replace( '/\s+/', '', $sum );

		// Remove locale from string.
		$sum = str_replace( $decimals, '.', $sum );

		// Trim invalid start/end characters.
		$sum = rtrim( ltrim( $sum, "\t\n\r\0\x0B+*/" ), "\t\n\r\0\x0B+-*/" );

		// Do the math.
		return $sum ? WC_Eval_Math::evaluate( $sum ) : 0;
	}

	/**
	 * Work out fee (shortcode).
	 *
	 * @param  array $atts Attributes.
	 * @return string
	 */
	public function fee( $atts ) {
		$atts = shortcode_atts(
			array(
				'percent' => '',
				'min_fee' => '',
				'max_fee' => '',
			),
			$atts,
			'fee'
		);

		$calculated_fee = 0;

		if ( $atts['percent'] ) {
			$calculated_fee = $this->fee_cost * ( floatval( $atts['percent'] ) / 100 );
		}

		if ( $atts['min_fee'] && $calculated_fee < $atts['min_fee'] ) {
			$calculated_fee = $atts['min_fee'];
		}

		if ( $atts['max_fee'] && $calculated_fee > $atts['max_fee'] ) {
			$calculated_fee = $atts['max_fee'];
		}

		return $calculated_fee;
	}

	/**
	 * Contact the Lulu API to calculate shipping costs for the Print on Demand books.
	 *
	 * @param array $package Package of items from cart.
	 */
	public function calculate_shipping( $package = array() ) {
		$communicator = PD_Lulu_Fulfillment_Communicator::instance();

		$response = $communicator->getPrintJobCostCalculation($package);
		if($response->is_success) {
			$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);

			$label = isset($options['shipping_fee_label']) && $options['shipping_fee_label'] ? $options['shipping_fee_label'] : __('Standard', PD_LULU_FULFILLMENT_DOMAIN);
			$shippingCost = $response->shipping_cost;
			$this->add_rate( array(
				'id' => $this->get_rate_id(),
				'label' => $label,
				'cost' => $shippingCost->total_cost_incl_tax,
				'package' => $package
			));
		}
	}

	/**
	 * Get items in package.
	 *
	 * @param  array $package Package of items from cart.
	 * @return int
	 */
	public function get_package_item_qty( $package ) {
		$total_quantity = 0;
		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['quantity'] > 0 && $values['data']->needs_shipping() ) {
				$total_quantity += $values['quantity'];
			}
		}
		return $total_quantity;
	}

	/**
	 * Finds and returns shipping classes and the products with said class.
	 *
	 * @param mixed $package Package of items from cart.
	 * @return array
	 */
	public function find_shipping_classes( $package ) {
		$found_shipping_classes = array();

		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['data']->needs_shipping() ) {
				$found_class = $values['data']->get_shipping_class();

				if ( ! isset( $found_shipping_classes[ $found_class ] ) ) {
					$found_shipping_classes[ $found_class ] = array();
				}

				$found_shipping_classes[ $found_class ][ $item_id ] = $values;
			}
		}

		return $found_shipping_classes;
	}


    /** ORIG */

    public function is_available($package)
    {
        foreach ( $package['contents'] as $item_id => $values ) {
            $_product = $values['data'];
            $isLuluBook = 'lulu4woocommerce' == $_product->get_type();
            $needsShipping = $_product->needs_shipping();
            
            if(!$isLuluBook && $needsShipping){
              return false;
            }
        }
        return true;
    }
}