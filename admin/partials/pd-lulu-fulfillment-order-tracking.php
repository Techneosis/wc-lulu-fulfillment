<?php

/**
 * Output the Tracking table on Order completion if there's relevant information from Lulu
 *
 * This file is used when WooCommerce sends an "Order Complete" email.
 *
 * @link       www.peacefuldev.com
 * @since      1.1.0
 *
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/admin/partials
 */
?>

<style>
    #lulu_tracking_div {
        margin-bottom: 40px;
    }

    #lulu_fulfillment_tracking {
        color: #636363;
        border: 1px solid #e5e5e5;
        vertical-align: middle;
        width: 100%;
        border-collapse: collapse;
    }

    #lulu_fulfillment_tracking th, #lulu_fulfillment_tracking td{
        color: #636363;
        border: 1px solid #e5e5e5;
        vertical-align: middle;
        padding: 12px;
        text-align: left;
    }
</style>

<div id="lulu_tracking_div">
    <h2><strong><?= __('Tracking Information', PD_LULU_FULFILLMENT_DOMAIN);?><strong></h2>
    <p><?=__('Our printer has completed your order and your books are on the way.', PD_LULU_FULFILLMENT_DOMAIN)?></p>
    <table id="lulu_fulfillment_tracking">
        <thead>
            <tr>
                <th>Tracking Number</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($luluTracking as $trackingId => $trackingUrl) { ?>
                <tr>
                    <td><?=$trackingId?></td>
                    <td><a href="<?=$trackingUrl?>"><?=__('Track', PD_LULU_FULFILLMENT_DOMAIN);?></a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>