# iQ Block Country Lite
This is a rewrite of Pascal's iQ Block Country plugin for WordPress. Please don't bother Pascal if you have issues with this plugin. If it doesn't work, you're on your own. I just created this version for myself and I'm sharing it with you for free. Don't expect any support from my side. If there's a bug maybe it gets fixed in the next version. Or fix it yourself. This is free open-source software.

## Description
It allows or disallows visitors from certain countries accessing (parts of) your website.

For instance if you have content that should be restricted to a limited set of countries you can do so. If you want to block rogue countries that cause issues like for instance hack attempts, spamming of your comments etc you can block them as well.

Do you want secure your WordPress Admin backend site to only your country? Entirely possible! You can even block all countries and only allow your ip address.

And even if you block a country you can still allow certain visitors by whitelisting their ip address just like you can allow a country but blacklist ip addresses from that country.

You can show blocked visitors a message which you can style by using CSS or you can redirect them to a page within your WordPress site. Or you can redirect the visitors to an external website.

You can (dis)allow visitors to blog articles, blog categories or pages or all content.

Stop visitors from doing harmful things on your WordPress site or limit the countries that can access your blog. Add an additional layer of security to your WordPress site.

This plugin uses the GeoLite database from Maxmind. It has a 99.5% accuracy so that is pretty good for a free database. If you need higher accuracy you can buy a license from MaxMind directly.

## GDPR information
This plugin stores data about your visitors in your local WordPress database. The number of days this data is stored can be configured on the settings page. You can also disable data logging.

Data which is stored of blocked visitors:

- IP Address
- Date and time of the visit
- URL that was requested
- Country of the IP address
- If the block happened on your backend or your frontend

Data which is stored on non blocked visitors:

- Nothing

##  No tracking
The original plugin had a tracking feature, which could be enabled (disabled by default). Tracking features as well as the Webence API have been completely removed.

## Use of caching plugins
Please note that many of the caching plugins are not compatible with this plugin. The nature of caching is that a dynamically build web page is cached into a static page. If a visitor is blocked this plugin sends header data where it supplies info that the page should not be cached. Many plugins however disregard this info and cache the page or the redirect. Resulting in valid visitors receiving a message that they are blocked. This is not a malfunction of this plugin.

The following caching plugins seem to work: Comet Cache, WP Super Cache.

Plugins that do NOT work: W3 Total Cache, Hyper cache, WPRocket

## Installation
1. Unzip the archive and put the `iq-block-country` folder into your plugins folder (/wp-content/plugins/).
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Set your MaxMind license key in the plugin settings.
4. The GeoLite2 database is downloaded automatically if it does not exist.
4. Go to the settings page and choose which countries you want to ban. Use the ctrl key to select multiple countries.

## How can I get a new version of the GeoIP database?
MaxMind updates the GeoLite database every month. The plugin checks automatically every three months for database updates, which should be sufficient. You also may download the database directly from MaxMind and upload them to your website.

1. Download the GeoIP2 Country database from [MaxMind](https://www.maxmind.com)
2. Unzip the GeoIP2 database and put the GeoLite2-Country.mmdb file into the plugin's db folder, usually /wp-content/iq-block-country/db/GeoLite2-Country.mmdb

Or

* Delete the GeoLite2-Country.mmdb file from the plugin's database folder and reload your website. The plugin will fetch and install the latest version automatically.

Or

* Go to the Tools tab in the plugin's settings and click the Update button to manually trigger an update of the database.
