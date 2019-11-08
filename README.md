# Paymark payment module for Magento 2.x
This is a Paymark Online EFTPOS payment module for Magento 2. 

Tested on Magento 2.3.x only.

## Installation

To install this module use the following composer command:

`composer require onfire/paymarkoe`

Alternatively download the package and put the files into this folder in your Magento directory: `app/Onfire/PaymarkOE`

After installing the files please run the following commands to enable the module:

```
#enable the module
php bin/magento module:enable Onfire_PaymarkOE

#run magento setup
php bin/magento setup:upgrade
```

## Config

You will need to register for Online EFTPOS before configuring this module. Visit www.paymark.co.nz for more info.

After the module has been installed go to `Stores > Settings > Configuration > Sales > Payment Methods` in the Magento Admin to find the configuration options.

The configuration options are as follows:

* Title: Title that will appear on the checkout page
* OE Merhcant ID: Merchant ID for your Online Eftpos account (supplied by Paymark)
* OE Consumer Key: Consumer Key for your Online Eftpos account (supplied by Paymark)
* OE Consumer Secret: Consumer Secret for your Online Eftpos account (supplied by Paymark)
* Allow Autopay: Flag to enable Autopay during checkout
* UAT: Flag to alternate UAT environment - this changes the payment URL
* Debug Log: Write logs to paymark.log during the checkout process for debugging purposes

Online Eftpos only supports doing a full Authorise and Capture (no Authorise only).

## Autopay Maintenance Callback

When using Autopay, it is possible that a customer will delete their Autopay contract within thier banking app. When this happens a request is sent to the following URL with the contract ID to be deleted, so as that the agreement can also be deleted from the customer vault in magento.

```
https://yourwebsite.url/paymarkoe/maintenance/callback/
```

This will need to be supplied to Paymark when the account is being setup. 