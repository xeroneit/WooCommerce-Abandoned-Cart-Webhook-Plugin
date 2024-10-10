Plugin Name: Abandoned Cart Webhook Plugin

Contributors: BotSailor
Tags: woocommerce, abandoned cart, webhook, cart recovery, automation, WhatsApp integration
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 1.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Description:

This plugin is actually build for recover WooCommerce Abandoned Cart by WhatsApp message for Botsailor. https://botsailor.com/blog/send-woocommerce-abandoned-cart-recovery-message-to-whatsapp-by-botsailor

The WooCommerce Abandoned Cart Webhook Plugin allows WooCommerce store owners to capture and send abandoned cart data, including customer information, to a specified webhook URL when a cart is abandoned. This plugin integrates with external CRM systems, email marketing platforms, or notification systems for automated cart recovery. It also integrates seamlessly with  BotSailor (https://botsailor.com/) to send WhatsApp notifications, helping to recover lost sales and boost conversions.

Features:

    Capture customer and cart details when a cart is abandoned.
    Send cart data to a specified webhook URL for further processing.
    Seamless integration with BotSailor to send WhatsApp messages to customers.
    Customizable webhook URL configuration.
    Tracks cart abandonment every 5 minutes via cron jobs.

Installation:

    Download the plugin from the repository.
    Upload the plugin to your WooCommerce store:
        Go to your WordPress Admin Dashboard > Plugins > Add New > Upload Plugin.
        Choose the .zip file and click "Install Now".
    Activate the plugin once installed.

How to Configure:

    After activation, go to Settings > Abandoned Cart Webhook in your WordPress dashboard.
    Set the webhook URL where cart data will be sent when a cart is abandoned. This URL will receive the data in JSON format.
    Save the changes to start using the plugin.

Integration with BotSailor:

BotSailor allows you to send WhatsApp messages when a cart is abandoned. Follow these steps to integrate:

    Sign up or log into BotSailor.
    Create a new webhook workflow in BotSailor and obtain the webhook URL.
    Paste the BotSailor webhook URL into the plugin’s settings.
    Design your WhatsApp message and activate the workflow in BotSailor.

Frequently Asked Questions:
Q: How does the plugin track abandoned carts?

The plugin uses a cron job that runs every 5 minutes to check for abandoned carts. A cart is considered abandoned if no action is taken within 1 hour of adding products.
Q: Can I integrate this plugin with other platforms besides BotSailor?

Yes, you can use any platform that accepts webhook data in JSON format. Just configure the webhook URL in the plugin’s settings.
Q: Does this plugin work with custom WooCommerce themes?

Yes, the plugin is designed to be compatible with most custom WooCommerce themes.
Changelog:
Version 1.0

    Initial release with webhook integration and BotSailor support.

License:

This plugin is licensed under the GPLv2 or later. You may modify and distribute it under the same license. License URI
