# woo-redact Wordpress plugin
A redaction/data-minimization plugin for WordPress/Woocommerce

## About

This project is intended to build a plugin to remove/blank fields containing sensitive and personally identifiable information (PII) from a Wordpress/Woocommerce installation, once that data is no longer needed.

We assume that you have already taken steps towards _data minimization_, meaning you collect as little information as you need.

However, by default, once you collect information like a customer's shipping or billing address, Wordpress and Woocommerce keep it forever. There are options to automatically delete a user after a period of inactivity. But in many cases you need to keep the user account active, such as for example if they have bought a downloadable item or need access to an access-controlled resource. In those cases you need the user account but you don't need some fields within that user account. You may also need to keep order details for tax reasons or customer service reasons. Again, you need the record but not all the fields within that record.

The purpose of this plugin is to remove the fields you no longer need, while keeping the user and order records that you can't discard.

## Why Wordpress/Woocommerce

Because I need it for that platform, but also because Wordpress and Woocommerce are very popular and widely used platforms. If we fix the PII problem there, we fix it for the largest number of people.

## License

This project is released under a GPLv3 license

## Status

There's nothing here yet. Expect to see an intial specification, some milestones and then a minimally viable plugin to start.
