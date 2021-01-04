# woo-redact Wordpress plugin
A redaction/data-minimization plugin for WordPress/Woocommerce

## About

This plugin provides fine-grain control over the deletion of customer data from a Wordpress/Woocommerce shop. Additionally, the plugin allows the removal of specified customer shipping and billing fields that are backed up in the user's account, without removing the user account itself.

By default, Woocommerce offers GDPR user account deletion. But Woocommerce can either delete a user account *or* leave it as-is with all the personal information (such as the shipping/billing address) backed up.

With this plugin you can remove these "backups" but keep the user account. This is especially important for shops that sell both virtual/downloadable products and physical products because the user account must be kept (to continue offering downloads) but the shipping information must be deleted.

## Why Wordpress/Woocommerce

Because I need it for that platform, but also because Wordpress and Woocommerce are very popular and widely used platforms. If we fix the PII problem there, we fix it for the largest number of people.

## License

This project is released under a GPLv3 license.

## Status

The plugin has been tested on Wordpress 5.6 and Woocommerce 4.8.0.

## Install & Setup

It is best to do this on a sandbox/staging copy of your site first and see what it does. Some of the actions only happen daily, so you will need to wait to see the results

* Download the ZIP file from this repository (green "Code" button, "Download ZIP")
* Upload as a plugin on Wordpress (Plugins/Add New/Upload)
* Activate Plugin
* Setup in Woocommerce/Settings/Accounts & Privacy
* Enable the additional option ("Remove saved address under customers' account daily") if you want
* Fine tune Woocommerce's "Personal data retention" time delays (see the "?" help to understand what each setting does - some delete accounts!)
* Fine tune the field selection ("Precise control for Data Removal")- check the fields you want removed, un-check the ones you want kept
