=== TimeZoneCalculator ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=neo%40neotrinity%2eat&item_name=neotrinity%2eat&no_shipping=1&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: time, date, timezone, calendar, world clock, clock, travel, widget, sidebar, dashboard, ajax, javascript, prototype
Requires at least: 2.8
Tested up to: 3.0
Stable tag: trunk

Calculates, displays and automatically updates times and dates in different timezones with respect to daylight saving on basis of UTC.

== Description ==

Calculates, displays and automatically updates times and dates in different timezones with respect to daylight saving on basis of UTC.

**since version 1.00 with an optional AJAX refresh and the possibility to calculate and display dates in the past or future**

* based on php timezones database (please read the [faq](http://wordpress.org/extend/plugins/timezonecalculator/faq/) for further information)
* fully optionpage-configurable
* easy to integrate (ships with sidebar- and dashboard-widget functionality as well as integrated timezones search function)
* drag and drop admin menu page
* clean uninstall

Please find the version for WordPress

* 2.8 and higher [here](http://downloads.wordpress.org/plugin/timezonecalculator.zip)
* 1.5 to 2.7 [here](http://downloads.wordpress.org/plugin/timezonecalculator.wordpress1.5-2.7.zip)

== Installation ==

1. Copy the `timezonecalculator` directory into your WordPress Plugins directory (usually wp-content/plugins). Hint: With WordPress 2.7 and higher you can conduct this step within your Admin Menu.

2. In the WordPress Admin Menu go to the Plugins tab and activate the TimeZoneCalculator plugin.

3. Navigate to the Settings/TimeZoneCalculator tab and customize the timezones according to your desires.

4. If you got widget functionality just drag and drop TimeZoneCalculator on your dynamic sidebar in the Appearance Menu. Otherwise, put this code `<?php getTimeZonesTime(); ?>` into your sidebar menu (sidebar.php) or where you want the results to be published.

5. Be happy and celebrate! (and maybe you want to add a link to [http://www.neotrinity.at/projects/](http://www.neotrinity.at/projects/))

== Frequently Asked Questions ==

= So, a lot of stuff changed with TimeZoneCalculator 0.90? =

Heck, yeah. - I changed the internal structure of the plugin to use the [php timezones library](http://php.net/manual/en/timezones.php) instead of calculating the timezones myself. Thus, please make sure that you have a recent version of php including the required library installed. In case you don't have a suiting environment, you can still use [version 0.81](http://downloads.wordpress.org/plugin/timezonecalculator.last_version_with_built-in_calculations.zip) which is the last TimeZoneCalculator version that can be used with older php versions. Nevertheless, please note that due to various security reasons there should always be a recent version of php installed. In case of a hosted environment, please contact your provider for further information.

As your environment is now set up properly, enjoy the benefits of TimeZoneCalculator 0.90 :) This new version yields the advantage, that the timezones-entries can be more easily selected and managed: information like abbreviations, offset to UTC and daylight saving can be automatically retrieved and updated. I'm afraid it is not possible to use your old timezone-entries with the new version, but it won't take you more than five minutes to convert them to the new format by manually editing. I'm sorry for any inconvenience caused.

If any timezone information like offset, abbreviations, etc. appears to be wrong, please leave a message for the php guys on [their board](http://php.net/manual/en/timezones.php).

Though timezone abbreviations can be automatically filled out, the corresponding full names (for example Central European Time for CET) are currently not supported within the php library. Nevertheless, you can look up this information in the TimeZoneCalculator Tab in your Admin Menu.

= Why is 'Drag and Drop' not working? Why can't I see the 'Drag and Drop' section? =

This section is based on internal WordPress Javascript-libraries, which means that it will only work with WordPress Version 2.1 or higher. In addition you have to have Javascript enabled in your browser (this is a default setting in a common browser like Firefox). The plugin is still fully functional without these constraints, but you need to customize your timezone entries manually as in older versions of TimeZoneCalculator.

= How can I adopt the color scheme in the TimeZoneCalculator Settings Tab for WordPress 2.5 and higher? =

If you select one of the two default color schemes (`classic = Blue` or `fresh = Gray`) in your Profile Page, TimeZoneCalculator automatically adopts its colors to this scheme.

In case you use a custom color scheme, this cannot be done automatically, because WordPress still doesn't provide any proper functions to find out, which colors of your scheme are used for background, font, etc. - Nevertheless, you can set up your preferred colors manually: Just add the [filter](http://codex.wordpress.org/Function_Reference/add_filter) `timezonecalculator_available_admin_colors` in for example timezonecalculator.php or in your custom-colors-plugin.

Array-Structure:

* 1 -> border-color of drag and drop lists
* 2 -> background-color of drag and drop menu items
* 4 -> text-color of drag and drop menu items

Example:

`function my_timezonecalculator_available_admin_colors($colors=array()) {
	$colors["custom_scheme"] = array("#14568A", "#14568A", "", "#C3DEF1");
	return $colors;
}

add_filter('timezonecalculator_available_admin_colors', 'my_timezonecalculator_available_admin_colors');`

== Further Usage ==

**Attention! - Geeks' stuff ahead! ;)**

With TimeZoneCalculator 1.00 and higher you can also realize a html select which displays for example the airtime of your internet radio station or your broadcasts in your users' local time. Another possibility for the usage of the upcoming function is the integration of date, time and timezone information into your travelling blog.

`function getTimeZonesTime (
	$time_string='',
	$timezone_string='UTC',
	$alt_style=false,
	$alt_before_list='<ul>',
	$alt_after_list='</ul>',
	$alt_before_tag='<li>',
	$alt_after_tag='</li>',
	$alt_timeformat='Y-m-d H:i',
	$alt_timezones=array(),
	$display_name=true,
	$use_container=true
)`

* `$time_string`: any unix timestamp (where `-1262304000 <= $time_string <= 2145916800`) or any English textual datetime description in the range of `1930-01-01` and `2038-01-01` which can be parsed with [php's strtotime function](http://php.net/manual/en/function.strtotime.php); default is set to current UTC (which can also be automatically updated if activated in the Admin Menu)

* `$timezone_string`: you can choose a [php timezone_string](http://php.net/manual/en/timezones.php); otherwise `UTC` will be used

* `$alt_style`: default is set to `false`; can be changed to `true`, if you want to use the following five alternative style attributes instead of the ones from the WordPress database:

 - `$alt_before_list`: default `<ul>`

 - `$alt_after_list`: default `</ul>`

 - `$alt_before_tag`: default `<li>`

 - `$alt_after_tag`: default `</li>`

 - `$alt_timeformat`: default `'Y-m-d H:i'`

* `$alt_timezones`: alternative timezones-array - each array entry has to be a string as described in the Content Section of the Admin Menu; default is the timezones-entries array which can be modified in the Admin Menu

* `$display_name`: if you want to display only the abbreviation (either from the database or manually entered) without tooltiptext, set this to `false`; default setting is `true`

* `$use_container`: if set to `true` (default value) and `$time_string` has not been set (which means that the current UTC timestamp is used) TimeZoneCalculator wraps the output in a html div with the class `timezonecalculator-refreshable-output` - the class `timezonecalculator-output` will be used for all other output; if you set `$use_container` to `false`, no container div will be generated

Example for including a world-clock in your [post-template](http://codex.wordpress.org/Theme_Development) (usually single.php or post.php in wp-content/themes) using [WordPress the_date() function](http://codex.wordpress.org/Template_Tags/the_date):

Find something similar to

`the_date();`

or

`the_time();`

and replace it with

`<?php getTimeZonesTime(the_date('U', '', '', false)); ?>`

This should output your selected timezones, whereas calculations are based on the timestamp of your post instead of using the current UTC.

== Screenshots ==

1. This screenshot shows the Settings/TimeZoneCalculator Tab with the Drag and Drop Section in the Admin Menu.

2. This picture presents the Preview Section of the TimeZoneCalculator Tab in the Admin Menu.