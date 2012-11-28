=== flexmls&reg; IDX Plugin ===
Tags: flexmls, IDX, MLS search
Contributors: flexmls
Requires at least: 3.0.0
Tested up to: 3.3.1
Stable tag: 3.1.7

Add flexmls IDX listings, market statistics, IDX searches, and a contact form on your web site.

== Description ==

*Requirement: To activate the flexmls IDX plugin, you must already be approved by your local MLS and FBS for flexmls IDX. Currently, FBS only provides IDX services to agents and brokers who are members of an MLS that uses flexmls Web.  Accordingly, if you are not a member of an MLS organization that uses flexmls Web, this plugin won't be useful to you.*

The flexmls IDX plugin includes six widgets you can add to your side bar, pages and posts.  The widgets are:

1. IDX Slideshow;
1. IDX Search;
1. Market Statistics;
1. 1-Click Location Searches;
1. 1-Click Custom Searches;
1. Contact Form.

*Live data from the MLS:* The IDX and market statistics widgets are all updated with IDX listing and statistics information live from the flexmls Web system used by your local MLS.

Examples and documentation for the widgets can be found [here](http://www.flexmls.com/wpdemo/).


== Installation ==

The easiest way to install the plugin is to use the Add New Plugins function within WordPress and search for flexmls IDX.

Otherwise:

1. Upload the unzipped `flexmls-connect` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to **Settings > flexmls&reg; IDX** in WordPress and enter your API key information
1. Add widgets to your sidebar, pages or posts using the tools provided.


== Screenshots ==

1. Add widgets to pages or posts using the included short-code creator.
2. Add graphs to your market analyses using the market stats widget.
3. Add widgets to your sidebar to introduce visitors to your IDX search.
4. Sample IDX slideshow and market stats widget.
5. Sample sidebar design.

== Frequently Asked Questions ==

= Do I need to be approved for flexmls IDX by my local MLS and FBS in order to use this plugin? =
Yes, this plugin requires an API key that is only available to approved members.

= How do I obtain API credentials? =
Please call FBS Broker Agent Services at 800-437-4232, ext. 108, or email <a href='mailto:idx@flexmls.com'>idx@flexmls.com</a> to purchase a key to activate your plugin.


== Changelog ==

= 3.1.7 =

* Added support for WordPress timezones in Listing Detail footer

= 3.1.6 =

* Added MLS# under the address on the listing details page

= 3.1.5 =

* Reverting some meta data changes for greater listing data support

= 3.1.4 =

* Suppressing some additional fields from summary section of details page that should not be shown

= 3.1.3 =

* Changed to use API meta data for labels and values

= 3.1.2 =

* Added new setting to allow multiple summary list widgets on a page if needed.  Not recommended for normal use

= 3.1.1 =

* Add MLS Area (minor) to location searching

= 3.1 =

* API db improvments
* Maintain last search criteria in search widget
* Schedule a Showing
* Allow address and subdivision location searching

= 3.0.11 =

* Fixed error when searching without a location input box

= 3.0.10 =

* Fixed error where site url confusion on www/non-www

= 3.0.9 =

* Fixed error when commas and dollar signs are entered and get 0 results

= 3.0.8 =

* Fixed trim error on listing detail

= 3.0.7 =

* Fields moved around api return, presentation fix


= 3.0.6 =

* Fix for boolean "main" custom fields as details
* Fix for bath fields
* Add the ability to force the display of the listing agent on the WordPress plugin detail page
* Fix title override from bleeding out
* Fix links and widgets from being broken when wordpress is referenced from different folder


= 3.0.5 =

* Fix for some broken image paths for IE users
* Fix for photo film strip for IE users
* Added some cache garbage collection to help keep the transient cache clean


= 3.0.4 =

* Changes so list prices with decimals display correctly without rounding
* Fix for listing price searches when using the IDX frame results
* Fix for the lead generation widget to properly send data and identify notification preference
* Changed assumed default for listing sorts in listing summary, slideshow and results widgets (assumes "price, high to low" if none given)
* Small fix to the Settings screen to help eliminate PHP notices in certain environments


= 3.0.3 =

* Improvements to speed up the plugin and API requests
* Fix to IDX search results widget to allow PostalCode and other location search fields as criteria
* Fix to photos when using multiple listing detail shortcodes


= 3.0.2 =

* Fix to IDX Search widget so submit button honors selected color in IE
* Small changes to IDX Slideshow widget to help longer lines fit without wrapping
* Fix to IDX search results widget to allow ListingId to be used as criteria


= 3.0.1 =

* Fix for issue with PHP error reporting levels and the new IDX Search widget features
* Flagged third party plugin as having conflicts due to Javascript errors


= 3.0.0 =

* Newly architected PHP API client for better speed, efficiency and features
* New option in IDX Slideshow to limit shown listings by agent if the site uses an office API key
* Lots of changes to IDX Search widget to support skins and new options
* New widget to integrate a summary list of listings (or search results) directly into a WP page
* New widget to integrate a detailed property report directly into a WP page
* New shortcodes for the 2 new integrated widgets
* Expanded and better organized settings screen under Settings > flexmls IDX
* Ability to turn on display of Listing Office for more control over IDX compliance
* Added the ability to rename property type labels shown to users (from Settings > flexmls IDX > Behavior)
* Fixed some issues with the recent change to IDX link pagination


= 2.4.2 =

* Fix for slideshow's missing/broken pagination


= 2.4.1 =

* Changes needed to support the IDX Links service's pagination update


= 2.4 =

* More minor updates to the IDX slideshow widget to better integrate with Facebook pages
* Fix to slideshow for broader IE compatibility
* Improved internal API session management to speed up requests and reduce session collisions (and associated errors)
* Plugin now correctly handles browser pre-fetching for those that support it
* Improved JSON parsing code.  Now automatically uses native PHP functions if available for much faster JSON parsing


= 2.3.3 =

* Minor update to the IDX slideshow widget to better support Facebook page integration


= 2.3.2 =

* Small Colorbox changes for the photo viewer for better viewing on Facebook pages
* Fixed a bug with Property Type conditions on My/Office/Company slideshow widgets


= 2.3 =

* Added notification option for site owners when a new lead is created
* Fixed some issues with magic_quotes and how quoted data is submitted to the plugin and API
* Removed the ability for site owners to disable API caching.  Cache can still be cleared manually from the Settings page


= 2.2 =

* Added Area and Subdivision to location search where enabled
* Upgraded Flot and Excanvas for better IE9 compatibility


= 2.1 =

* Added protection to block directory viewing on misconfigured servers
* Added a backup parsing method for URLs when $_GET isn't available
* CSS enhancements to better cope with theme conflicts
* Fixed market statistics graph compatibility issue with IE9
* Fixed an issue with the default handling of idx_frame settings


= 2.0 =

* Restricted portions of the address are now ignored
* Add new Neighborhood Page feature
* Changes to photo loading on the IDX Slideshow widget
* Enhanced IDX Slideshow to include additional, optional fields to show
* Enhanced IDX Slideshow to make additional calls to API for more slides
* Enhanced IDX Slideshow to allow sorting
* IDX Slideshow widget now uses the selected Saved Search link as part of the criteria for pulling listings
* New "My Office" and "My Company" filters for IDX Slideshow
* Calls to the flexmls API now include the version of the plugin being used
* CSS and Javascript changes to better integrate with themes and other plugins
* Fixed issue with IDX logo being too large in some cases


= 0.9.6 =

* Fixes an issue with the lead generation widget throwing an error in certain environments


= 0.9.5 =

* Contains fixes for some CSS styling conflicts with certain themes
* Fixes an issue where the location search selection isn't saved correctly under certain circumstances
* Fixes for element and widget positioning


= 0.9.3 =

* Contains fix for broken links to Javascript and styles


= 0.9.2 =

* Small fix to correct the displayed IDX logo


= 0.9.0 =

* This is a beta version.



== Upgrade Notice ==


= 0.9.0 =
This is a beta version.
