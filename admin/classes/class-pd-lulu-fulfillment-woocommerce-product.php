<?php
class WC_Product_Lulu extends WC_Product_Simple {
    public function __construct( $product ) {
        parent::__construct( $product );

    }

    public function get_type() {
        return 'lulu4woocommerce'; // so you can use $product = wc_get_product(); $product->get_type()
    }

    public function get_shipping_class() {
        return 'pd_lulu_fulfillment_shipping';
    }
}