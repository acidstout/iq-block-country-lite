## Changes
1.2.7.2
* Bugfix: Set proper path to database file.

1.2.7.1
* Bugfix: Undefined variable could occur in some cases.
* Change: Updated download URL of GeoIP2 database.
* New: Added option to set MaxMind license key in order to be able to download GeoIP2 database.
* Info: Based on iQ Block Country 1.2.7. All other changes in that version where not applicable or already fixed.

1.2.6.2
* New: Added option to manually update the GeoIP2 database.
* Change: Typo in San Francisco

1.2.6.1
* New: Added automatic update of GeoIP2 database.
* Change: Added better support to detect if mbstring is available for usage.

1.2.5.1
* New: Added Mediapartners-Google service user-agent.
* New: Added option to unblock feed pages on the frontend configuration tab. Useful to block visitors while still allowing access to the RSS-feed.
* Bugfix: Do no longer log empty URL queries.

1.2.4.2
* Bugfix: Removed static reference to `wp-includes` folder. WordPress uses the `WPINC` constant to specify this folder, and thus the folder name can be overwritten in the `wp-config.php` file.

1.2.4.1
* Change: Improved server IP address detection on Windows IIS machines.

1.2.3.1
* Bugfix: Several bugs have been fixed (e.g. missing HTML close-tags, undefined indexes, redundant clauses and loops, ...). Too much to list them all.
* Change: Code has been cleaned up and formatted properly.
* Change: Removed Webence API. If you use the Webence API this is the wrong plugin for you. In such case please stick to the non-lite version.
* Change: Removed tracking features.
* New: Added automatic downloading of the GeoLit2 database if it doesn't exist. In version 1.1.17 Pascal decided to remove that feature. Now it's back in.

1.2.3
* Change: Changed inverse option to that you have to select between 'Block countries selected below' or 'Block all countries except those selected below' as inverse option caused some confusion.
* Change: Cutoff for long urls on the statistics page.
* New: Added 'inverse' function to the pages selection as well. So you can now select the pages you want to block or select the pages you do not want to have blocked and block all other pages.
* New: Added override function for IP detection.

1.2.2
* Change: Deleted Asia server due to bad performance
* Change: Altered behavior of flushing the buffer
* New: Added MOZ as service.
* New: Added SEMrush as service.
* New: Added SEOkicks as service.
* New: Added EU2 and EU3 servers for GeoIP API
* New: Added support for WPS Hide Login

1.2.1
* Change: Adjusted loading chosen library (Credits to Uzzal)
* Change: Display error when only the legacy GeoIP database exists and not the new GeoIP2 version
* New: Added Link Checker (https://validator.w3.org/checklink) as service.
* New: Added Dead Link Checker as a service.
* New: Added Broken Link Check as a service.
* New: Added Pingdom as a service

1.2.0
* New: Added support for GeoIP2 country database
* New: Added Pinterest as service

1.1.51
* New: Added new GeoIP API server in Florida
* New: Added new GeoIP API server in Asia

1.1.50
* Bugfix: Fix for SQL error in rare conditions
* Change: Added some more work for the upcoming GeoIP2 support.
* New: Added AppleBot, Feedburner and Alexa to the services you can allow

1.1.49
* Change: Changed when the buffer is flushed (if selected) (Thanks to Nextendweb)
* Change: Changed cleanup on debug logging table.

1.1.48
* Bugfix: Fixed small bug

1.1.47
* Change: You can now also enter IP Ranges in the black & whitelist in CIDR format.
* Change: Altered logging clean up a little bit

1.1.46
* Bugfix: Added extra aiwop checking due to a notice error.
* Change: Renamed Search Engines tab to Services tab as more non-search engines are added to the list.
* New: Added Feedly to services.
* New: Added Google Feed to services.
* New: Changes are made for supporting the new GeoIP2 database format of MaxMind.

1.1.45
* Bugfix: (un)blocking individual pages and categories did not work anymore.

1.1.44
* Change: Removed Asia API Key server.
* Change: Small change when frontend blocking is fired up.
* Change: Adds server ip address (the IP address where your website is hosted) to the frontend whitelist so if you block the country your website is hosted it can still access wp-cron for instance.

1.1.43
* Change: Altered address for Asia API Key server

1.1.42
* Bugfix: Temp fix for some people who had issues being blocked from the backend.

1.1.41
* Change: Removed unnecessary code.
* New: New GeoIP API location added at the west coast of the United States
* New: Limit the number of days the logging is kept between 7 days and 90 days.
* New: Disable host lookup on the logging tab. In some circumstances this may speed up the logging tab.

1.1.40
* Bugfix: Fix for bug in not blocking/allowing post types.
* Change: Changed support option from forum to mail.
* New: Moved GeoIP API to secure https
* New: Logging DB optimization (Thanks to Arjen Lentz)

1.1.38
* Bugfix: Only shows warning of incompatible caching plugin if frontend blocking is on.
* Change: Better error handling 

1.1.37
* Change: Small adjustment to prevent wp_mail declaration as much as possible.

1.1.36
* Bugfix: Smashed bug on backend

1.1.35
* Change: Added WPRocket to list of caching plugins that are not compatible with iQ Block Country (thanks to Mike Reed for supplying the info)
* Change: Only displays warning about incompatible caching plugins in case frontend blocking is selected.
* Change: Fixed small security issue with downloading the statistics as CSV file (Thanks to Benjamin Pick for reporting)
* New: Added Baidu to Search Engines list
* New: Added Google Site Verification to the search engines list
* New: Added Google Search Console to the search engines list
* New: You can now also block individual post tags

1.1.33
* Bugfix: Bug smashed on tag page

1.1.32
* Bugfix: Bug smashed on tag page

1.1.31 
* Change: Small changes in GeoIP API calls
* Change: Small changes
* Change: Moved some of the urls to https, more to follow.
* New: Added option to block / unblock tag pages.
* New: A warning is displayed for known caching plugins that ignore the no caching headers.

1.1.30
* Change: Added new GeoIP API location for Asia-Pacific region.
* Change: Added some missing country icons.

1.1.29 
* Change: Small changes in GeoIP API calls
* New: Added database information to tools tab.
* New: Added support for rename wp-login plugin

1.1.28
* Bugfix: Altered mysql_get_client_info check as in some setups this gave a fatal error.
* New: Added Wordpress Jetpack as search engine. You can allow Jetpack to communicate with your site if you have Jetpack installed.
* New: Added option to allow admin-ajax.php visits if you use backend blocking.

1.1.27
* Bugfix: Fixed small bug

1.1.26
* Change: Updated chosen library to latest version.
* Change: Added a (de)select all countries to the backend en frontend country list.
* Change: Changed order of how the plugin detects the ip address.
* Change: Added detection of more header info that can contain the proper ip address
* Change: Added download urls on database is too old message.
* New: Added support forum to the site.
* New: xmlrpc.php is now handled the same way as other backend pages.

1.1.25
* Bugfix: Altered checking for Simple Security Firewall

1.1.24
* Change: Various small changes
* New: Added support for Lockdown WordPress Admin
* New: Added support for WordPress Security Firewall (Simple Security Firewall)

1.1.23
* Bugfix: Fixed bug if cURL was not present in PHP version
* New: When local GeoIP database present it checks if database is not older than 3 months and alerts users in a non-intrusive way.

1.1.22
* Bugfix: Category bug squashed
* Change: Altered text-domain
* New: Added export of all logging data to csv. This exports max of 1 month of blocked visitors from frontend & backend.

1.1.21
* Change: Minor improvements
* Bugfix: Fixed an error if you lookup an ip on the tools tab while using the inverse function it sometimes would not display correctly if a country was blocked or not.
* New: Added check to detect closest location for GeoIP API users
* New: Added support for All in one WP Security Change Login URL. If you changed your login URL iQ Block Country will detect this setting and use it with your backend block settings.

1.1.20
* New: Added Google Ads to search engines
* New: Added Redirect URL (Basic code supplied by Stefan)
* New: Added inverse selection on frontend. (Basic code supplied by Stefan)
* New: Added inverse selection on backend.
* New: Validated input on the tools tab.

1.1.19
* Bugfix: Check if MaxMind databases actually exist.
* New: Added option to select if you want to block your search page.
* New: Block post types
* New: Unzip MaxMind database(s) if gzip file is found.
* New: When (re)activating the plugin it now adds the IP address of the person activating the plugin to the backend whitelist if the whitelist is currently empty.

1.1.18
* Change: Changed working directory for the GeoIP database to /wp-content/uploads

1.1.17
* Change: Due to a conflict of the license where Wordpress is released under and the license the MaxMind databases are released under I was forced to remove all auto downloads of the GeoIP databases. You now have to manually download the databases and upload them yourself.
* New: Added Webence GeoIP API lookup. See https://geoip.webence.nl/ for more information about this API.

1.1.16
* New: Accessibility option. You can now choose if you want the country default selectbox or an normal selectbox.
* New: New button to empty the logging database..
* New: You can now set the option to not log the ip addresses to the database. This does not influence the blocking process only the logging process. This can be handy if the laws in your country do not permit you to log this information or if you choose not to log this information

1.1.15
* Bugfix: You can now set an option to buffer the output of the iQ Block Country plugin. If you use for instance NextGen Gallery you should not set this option as it will break uploading pictures to your gallery.
* Bugfix: Last time GeoIP databases were downloaded was wrong.
* Bugfix: If you configured auto-update of the GeoIP databases the tools tab showed that you did not configure auto update.
* Added check for HTTP_X_TM_REMOTE_ADDR to get real ip address of T-Mobile users.
* Added Twitter, Bitly, Cliqz and TinEye to the search engines list.
* New: No longer blocks category pages of categories you have not blocked.
* Bugfix: Added check if HTTP_USER_AGENT is set.

1.1.14
* Bugfix: The plugin did not recognise the login page when installed to a subdirectory.
* New: You can configure if it auto updates the GeoIP Database. Upon request of those people who have the paid database of MaxMind.
* Added Facebook and MSN to list of search engines.
* Changed the version of the geoip.inc file to the version of https://github.com/daigo75/geoip-api-php

1.1.13
* Bugfix on setting defaults when they values already existed.
* You can now allow search engines access to your country even if they come from countries that you want to block.

1.1.12
* Bugfix on the backend blacklist / whitelist

1.1.11
* Added select box on how many rows to display on the logging tab
* Redirect blocked users to a specific page instead of displaying the block message.
* Added blacklist and whitelist of IP addresses to the backend.
* Adjusted some text
* Minor bugfixes

1.1.10
* Small fixes
* WP 3.9 compatability issue fixed

1.1.9
* Bugfix release due to faulty v1.1.8 release. My Apologies.

1.1.8
* Smashed a bug where the homepage was unprotected due to missing check.

1.1.7
* Added Russian (ru_RU) translation by Maxim
* Added Serbo-Croatian (sr_RU) translation by Borisa Djuraskovic (Webostinghub)
* Changed the logging table a bit.

1.1.6
* Added to ban categories. This works the same way as blocking pages (By request of FVCS)
* Changed the admin page layout. Added tabs for frontend and backend blocking to make it look less cluttered
* Added optional tracking to the plugin. This is an experiment to see if building a database of IP addresses that try to login to the backend is viable.
* Upon first activation the plugin now fills the backend block list with all countries except the country that is currently used to activate.
* Added IP checking in header HTTP_CLIENT_IP and HTTP_X_REAL_IP

1.1.5
* Statistics required wp-config.php in a specific place bug smashed.

1.1.4
* Added import/export function.
* Minor bugs solved

1.1.3
* Fixed error that when using the option to block individual pages all visitors would be blocked. (Thanks to apostlepoe for reporting)

1.1.2
* Fixed localization error. (Thanks to Lisa for reporting)

1.1.1
* You can now choose to block individual pages. Leaving other pages open for visitors from blocked countries. You can for instance use this feature to block countries from visiting specific pages due to content rights etc.
* Source now supports localization. Included is the English and Dutch language. I’d be happy to include other translations if anyone can supply those to me.

1.1
* Added statistics to the plugin.
* You can view the last 15 hosts that were blocked including the url they visited.
* You can view the top 15 of countries that were blocked in the past 30 days.
* You can view the top 15 of hosts that were blocked in the past 30 days.
* You can view the top URL’s that were most blocked in the past 30 days.
