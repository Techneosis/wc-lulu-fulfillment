<?php

/**
 * Output the Lulu Printing settings panel for Lulu Book Products
 *
 * @link       www.peacefuldev.com
 * @version    1.2.0 Added Page Count, required for making print job cost requests
 * @since      1.1.0
 *
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/admin/partials
 */
?>

<div id="pd_lulu_printing_panel" class="panel woocommerce_options_panel hidden">
<?PHP

    woocommerce_wp_text_input(
        array(
            'id'        => 'lulu_page_count',
            'label'     => __('Page Count', PD_LULU_FULFILLMENT_DOMAIN),
            'type'      => 'int',
            'custom_attributes' => array(
                'step' => 1,
                'min' => 1,
            ),
            'desc_tip'  => __('Page count of the book to be printed', PD_LULU_FULFILLMENT_DOMAIN)
        )
    );

    woocommerce_wp_select(
        array(
            'id' => 'lulu_trim_sku',
            'label' => __('Trim Size', PD_LULU_FULFILLMENT_DOMAIN),
            'desc_tip' => 'Dimensions of the book (All dimensions listed as WIDTH(inches) x HEIGHT(inches)',
            'options' => array(
                '0425X0687' => 'Pocketbook (4.25" x 6.875")',
                '0550X0850' => 'Digest (5.5" x 8.5")',
                '0583X0827' => 'A5 (5.83" x 8.27")',
                '0600X0900' => 'US Trade (6" x 9")',
                '0614X0921' => 'Royal (6.14" x 9.21")',
                '0663X1025' => 'Comic (6.63" x 10.25")',
                '0750X0750' => 'Small Square (7.5" x 7.5")',
                '0700X1000' => 'Executive (7" x 10")',
                '0744X0968' => 'Crown Quatro (7.44" x 9.68")',
                '0850X0850' => 'Square (8.5" x 8.5")',
                '0827X1169' => 'A4 (8.27" x 11.69")',
                '0850X1100' => 'US Letter (8.5" x 11")',
                '0900X0700' => 'Landscape (9" x 7")',
                '1100X0850' => 'US Letter Landscape (11" x 8.5")',
                '1169X0827' => 'A4 Landscape (11.69" x 8.27")'
            ),
            'class' => '',
            'style' => '',
            'wrapper_class' => '',
            'name' => 'lulu_trim_sku',
            'custom_attributes' => array(),
            'description' => '',
        )
    );

    woocommerce_wp_select(
        array(
            'id' => 'lulu_print_sku',
            'label' => __('Print Type', PD_LULU_FULFILLMENT_DOMAIN),
            'desc_tip' => 'Printing quality for this title',
            'options' => array(
                'PRE' => 'Premium',
                'STD' => 'Standard'
            ),
            'class' => '',
            'style' => '',
            'wrapper_class' => '',
            'name' => 'lulu_print_sku',
            'custom_attributes' => array(),
            'description' => '',
        )
    );

    woocommerce_wp_select(
        array(
            'id' => 'lulu_color_sku',
            'label' => __('Color Type', PD_LULU_FULFILLMENT_DOMAIN),
            'desc_tip' => 'Color printing for this title',
            'options' => array(
                'BW' => 'Black and White',
                'FC' => 'Full Color'
            ),
            'class' => '',
            'style' => '',
            'wrapper_class' => '',
            'name' => 'lulu_color_sku',
            'custom_attributes' => array(),
            'description' => '',
        )
    );

    woocommerce_wp_select(
        array(
            'id' => 'lulu_paper_sku',
            'label' => __('Paper Type', PD_LULU_FULFILLMENT_DOMAIN),
            'desc_tip' => 'The paper type for this title',
            'options' => array(
                '060UW444' => '60# Uncoated White',
                '060UC444' => '60# Uncoated Cream',
                '070CW460' => '70# Coated White',
                '080CW444' => '80# Coated White',
                '100CW200' => '100# Coated White'
            ),
            'class' => '',
            'style' => '',
            'wrapper_class' => '',
            'name' => 'lulu_paper_sku',
            'custom_attributes' => array(),
            'description' => '',
        )
    );

    woocommerce_wp_select(
        array(
            'id' => 'lulu_bind_sku',
            'label' => __('Binding Type', PD_LULU_FULFILLMENT_DOMAIN),
            'desc_tip' => 'The binding style for this title',
            'options' => array(
                'PB' => 'Perfect Bound (Standard Paperback)',
                'CO' => 'Coil Cound',
                'SS' => 'Saddle Stitch',
                'CW' => 'Case Wrap',
                'LW' => 'Linen Wrap',
                'WO' => 'Wire O'
            ),
            'class' => '',
            'style' => '',
            'wrapper_class' => '',
            'name' => 'lulu_bind_sku',
            'custom_attributes' => array(),
            'description' => '',
        )
    );

    woocommerce_wp_select(
        array(
            'id' => 'lulu_finish_sku',
            'label' => __('Finish Type', PD_LULU_FULFILLMENT_DOMAIN),
            'desc_tip' => 'The cover finish for this title',
            'options' => array(
                'G' => 'Gloss',
                'M' => 'Matte'
            ),
            'class' => '',
            'style' => '',
            'wrapper_class' => '',
            'name' => 'lulu_finish_sku',
            'custom_attributes' => array(),
            'description' => '',
        )
    );

    woocommerce_wp_select(
        array(
            'id' => 'lulu_linen_sku',
            'label' => __('Cover Option', PD_LULU_FULFILLMENT_DOMAIN),
            'desc_tip' => 'The cover option for this title',
            'options' => array(
                'X' => 'N/A',
                'R' => 'Red Linen',
                'N' => 'Navy Linen',
                'B' => 'Black Linen',
                'G' => 'Gray Linen',
                'T' => 'Tan Linen',
                'F' => 'Forest Linen',
                'I' => 'Interior Cover Print'
            ),
            'class' => '',
            'style' => '',
            'wrapper_class' => '',
            'name' => 'lulu_linen_sku',
            'custom_attributes' => array(),
            'description' => '',
        )
    );

    woocommerce_wp_select(
        array(
            'id' => 'lulu_foil_sku',
            'label' => __('Foil Type', PD_LULU_FULFILLMENT_DOMAIN),
            'desc_tip' => 'The foil type for this title',
            'options' => array(
                'X' => 'N/A',
                'G' => 'Gold',
                'B' => 'Black',
                'W' => 'White'
            ),
            'class' => '',
            'style' => '',
            'wrapper_class' => '',
            'name' => 'lulu_foil_sku',
            'custom_attributes' => array(),
            'description' => '',
        )
    );
?>
</div>