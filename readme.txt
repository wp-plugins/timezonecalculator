=== TimeZoneCalculator ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=neo%40neotrinity%2eat&item_name=neotrinity%2eat&no_shipping=1&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: timezone, daylightsaving, world clock, date, widget, sidebar
Requires at least: 1.5
Tested up to: 2.8
Stable tag: trunk

Calculates times and dates in different timezones with respect to daylight saving on basis of UTC.

== Description ==

Calculates times and dates in different timezones with respect to daylight saving on basis of UTC.

* fully optionpage-configurable
* easy to integrate (ships with widget functionality and integrated timezones search function)
* drag and drop admin menu page
* clean uninstall

== Installation ==

1. Copy the `timezonecalculator` directory into your WordPress Plugins directory (usually wp-content/plugins). Hint: With WordPress 2.7 and higher you can conduct this step within your Admin Menu.

2. In the WordPress Admin Menu go to the Plugins tab and activate the TimeZoneCalculator plugin.

3. Navigate to the Settings/TimeZoneCalculator tab and customize the timezones according to your desires.

4. If you got widget functionality just drag and drop TimeZoneCalculator on your dynamic sidebar in the Appearance Menu. Otherwise, put this code `<?php getTimeZonesTime(); ?>` into your sidebar menu (sidebar.php) or where you want the results to be published.

5. Be happy and celebrate! (and maybe you want to add a link to [http://www.neotrinity.at/projects/](http://www.neotrinity.at/projects/))

== Frequently Asked Questions ==

= Why is 'Drag and Drop' not working? Why can't I see the 'Drag and Drop' section? =

This section is based on internal WordPress Javascript-libraries, which means that it will only work with WordPress Version 2.1 or higher. In addition you have to have Javascript enabled in your browser (this is a default setting in a common browser like Firefox). The plugin is still fully functional without these constraints, but you need to customize your timezone entries manually as in older versions of TimeZoneCalculator.

== Screenshots ==

1. This screenshot shows the Settings/TimeZoneCalculator Tab with the Drag and Drop Section in the Admin Menu.

2. This picture presents the Preview Section of the TimeZoneCalculator Tab in the Admin Menu.