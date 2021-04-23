# Paymark payment module for Magento 2.x
This is a Paymark Online EFTPOS payment module for Magento 2. Please read this file carefully and follow all instructions to install and configure the payment module successfully.

Tested on Magento 2.3.x only.

## Installation

To install this module use the following composer command:

`composer require paymark/paymarkoe`

Alternatively download the package and put the files into this folder in your Magento directory: `app/Paymark/PaymarkOE`

Note: If you do not use Composer you will not receive automatic updates.  Please use your GitHub account to subscribe to the payment module repository so you are alerted to updates.

After installing the files please run the following commands to enable the module:

```
#enable the module
php bin/magento module:enable Paymark_PaymarkOE

#run magento setup
php bin/magento setup:upgrade
```

## Configuration

You will need to have an Online EFTPOS account before configuring this module. Visit https://www.paymark.co.nz/products/online-eftpos/ for more info.

Once you have an Online EFTPOS account you need to enter all the domains of your web sites where the Magento plugin will be used into the OpenJS Configuration area under Settings in the Online EFTPOS Portal: https://oe.paymark.co.nz/ (production) or https://oe.demo.paymark.co.nz/ (UAT/Sandbox).  

After the module has been installed go to `Stores > Settings > Configuration > Sales > Payment Methods` in the Magento Admin to find the configuration options.

The configuration options are as follows:

* Title: Title that will appear on the checkout page
* OE Merchant ID: Merchant ID for your Online EFTPOS account (available in the Online EFTPOS Portal http://oe.paymark.co.nz/)
* OE Consumer Key: Consumer Key for your Online EFTPOS account (available in the Online EFTPOS Portal http://oe.paymark.co.nz/)
* OE Consumer Secret: Consumer Secret for your Online EFTPOS account (available in the Online EFTPOS Portal http://oe.paymark.co.nz/)
* Allow Autopay: Flag to enable Autopay during checkout, contact Paymark if you wish to enable Autopay
* UAT: Flag to change the payment URL to connect to the Online EFTPOS Sandbox environment for testing purposes, you will need to use the Merchant ID and Consumer Key/Secret for your Online EFTPOS Sandbox account (https://oe.demo.paymark.co.nz/) when using this setting
* Debug Log: Write logs to paymark.log during the checkout process for debugging purposes

## Autopay Maintenance Callback

This applies if you have Autopay enabled on your account.  Contact Paymark on 0800 PAYMARK to discuss using Autopay.

When using Autopay, it is possible that a customer will delete their Autopay contract within their banking app. When this happens a request is sent to the following URL with the contract ID to be deleted.  When this happens the agreement should also be deleted from the customer vault in Magento.

```
https://yourwebsite.url/paymarkoe/maintenance/callback/
```

This URL will need to be supplied to Paymark when the account is being set up. 
