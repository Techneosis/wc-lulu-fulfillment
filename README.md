# Peaceful Lulu Fulfillment

**Contributors:** Brett \
**Donate link:** www.techneosis.com \
**Tags:** lulu, dropshipping, print-on-demand, woo, woocommerce, publishing \
**Requires at least:** 3.0.1 \
**Tested up to:** 5.7 \
**Stable tag:** trunk \
**License:** GPLv2 or later \
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

Provide automatic dropshipping for self published books using WooCommerce and Lulu Press Inc.'s print-on-demand services.

## Description

**Integrate your WooCommerce store front with Lulu Press Inc.'s print-on-demand services!**

* Have Lulu automatically print and ship copies of your books to your customers once WooCommerce marks the order as Processing (i.e. payment received)
* Mark the orders as complete automatically once Lulu ships the books to your customer

## Installation

**Requirements**
* Use of this plugin requires a Lulu Developer account and credentials. Sign up free here: [Lulu Developer Site](https://developers.lulu.com/).
* This plugin builds on top of and requires the WooCommerce plugin. Check it out here: [WooCommerce Plugin Page](https://wordpress.org/plugins/woocommerce/).

**Initial Setup**

1. Upload 'wc-lulu-fulfillment.zip' to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Navigate to the 'Lulu Fulfillment' link added to the side bar of your WordPress admin area
1. Select Sandbox or Production Lulu Environment (Orders sent to the Lulu sandbox will never be charged or fulfilled, perfect for testing the integration)
1. Enter the relevent API keys from your Lulu Developer account, as well as a contact email.
    * The contact email is the email Lulu will contact if there is a problem fulfilling an order.
1. Enable Automatic order completion if desired.


**Selling your first book**
1. Create a new WooCommerce Product
1. Select "Lulu Book" as the product type
1. Upload your cover and internal PDF files for the book somewhere and paste their links in the relevant fields
1. Select the options for the binding information you'd like the book printed with

## Frequently Asked Questions

### ?

Never been asked a question.


## Changelog

### 1.4.1 
* Small fix for Print Job Cost Calculation endpoint change
    * Bogus default phone number

### 1.4.0

* Run an order at print cost from the admin edit order page!
    1. Updates Lulu line items with printing cost
    1. Adds shipping costs from Lulu
    1. Adds fulfillment fees from Lulu

### 1.3.0

* Upload Cover and Interior PDFs directly on the product page now - no more copy-pasting links!
* See Lulu production status of an order from the "Orders" list
* When a Lulu book product is updated:
    1. Verify binding options are printable by Lulu
    1. Calculate print cost
    * Lulu requires an address to calculate print cost. Defaults to store address set in WooCommerce settings, can be overridden in options

### 1.2.0

* Shipping Option! Enable Lulu Shipping Method in the plugin settings
    * Lulu fulfilled items will be packaged together and user will be charged Lulu's shipping price directly
    * Options for Package name and shipping fee label, call it whatever you like!

### 1.1.0

* Orders containing Lulu products are checked hourly until completed, Updated with current Lulu fulfillment status. Possible statuses are:
    1. UNPAID
    1. PAID
    1. PRODUCTION_READY
    1. IN_PRODUCTION
    1. SHIPPED
* Configuration option to automatically mark orders containing Lulu products complete if:
    1. The order only contains products fulfilled by Lulu
    1. The order has been shipped by Lulu

### 1.0

* Initial Version. Create Lulu Book products in your WooCommerce store and have them automatically printed and shipped to your customers once payment is received.

## Upgrade Notice

### 1.4.0

Admins can make orders at print cost.

### 1.3.0

Simpler Interior & Content PDF attaching! Print-cost Estimations! and more!

### 1.2.0

Lulu Shipping Method! Option to charge the customer what Lulu charges you for shipment.

### 1.1.0

Automatically mark orders completed! Check Lulu's fulfillment status hourly via cron!

### 1.0

Upgrade? No upgrade. Initial Version.

