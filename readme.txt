=== Temporal Transients ===
Contributors: simonbbrown
Tags: optimization, page speed
Requires at least: 3.9
Tested up to: 4.3.1
Stable tag: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Simple WordPress Plugin to help speed up your WordPress website by setting Transients for some of the more greedy functions

== Description ==

This plugin is designed to speed up your WordPress page loads by storing previously rendered menus and content as transients.

Testing of the plugin revealed that on some sites that server response time was reduced by up to 30%.

This plugin is useful for sites where content editors such as Visual Composer, create a lot of shortcodes and require
significant processing in order to render a page. However to use your theme will need to be updated. See Installation
for instuctions.

== Installation ==

1. Unzip temporal-transients.zip and copy the folder temporal-transients to your plugins directory
2. Activate the plugin through the 'Plugins' menu in WordPress

To use the Content transient:

replace all instances of the_content within your theme with the following code:
`if (function_exists('tt_the_content')) {
    tt_the_content();
} else {
    the_content();
}`

Note: some people may not want to directly update their theme and it would be wise to look at using a child theme to achieve this. A request to WordPress has been made to include a filter that will make this step not required, hopefully it will be included soon!

== Frequently Asked Questions ==

= How can I delete all the transients created by Temporal Transients =

Go to the General Settings page and hit the Purge button in the Temporal Transients section.

= Why didn't you do it for Sidebars? =

WordPress already stores sidebars, but if you would like to see some area added then please contact me to see about getting it added to the plugin

== Changelog ==

= 0.1 =
* Initial Release