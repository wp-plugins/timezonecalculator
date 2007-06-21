=== TimeZoneCalculator ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=neo%40neotrinity%2eat&item_name=neotrinity%2eat&no_shipping=1&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: timezone, daylightsaving, world clock, date, widget, sidebar
Requires at least: 1.5
Tested up to: 2.2.1
Stable tag: trunk

Calculates different times and dates in timezones with respect to daylight saving on basis of utc.

== Description ==

Calculates different times and dates in timezones with respect to daylight saving on basis of utc. timezone-infos are available at [timeanddate.com](http://www.timeanddate.com/library/abbreviations/timezones/) or at [wikipedia.org](http://en.wikipedia.org/wiki/Timezones)

**fully-optionpage-configurable, and as usual easy to integrate (ships with widget functionality)**

**Now with Drag and Drop admin menu page**

== Installation ==

1. Copy the timezonecalculator directory in your WordPress plugins directory (usually wp-content/plugins).

2. In the WordPress admin console, go to the Plugins tab, and activate the TimeZoneCalculator plugin.

3. Go to the Options/TimeZoneCalculator and configure the timezones the way you like by using the AJAX functionality in the Drag and Drop section or customize in the static section (all data for one timezone in one row, no spaces between ; separator). Feel free to play around and see the result in the section preview at the bottom of the options page. If you are new to TimeZoneCalculator, it's a good start to load the defaults by clicking the button in the right top or lower corner.

  timezone-infos are available at
  * [timeanddate.com](http://www.timeanddate.com/library/abbreviations/timezones/)
  * [wikipedia.org](http://en.wikipedia.org/wiki/Timezones)

4. If you got [widget functionality](http://wordpress.org/extend/plugins/widgets/), just drag and drop TimeZoneCalculator on your dynamic sidebar in the presentation menu and name it appropriate. Otherwise, put this code `<?php getTimeZonesTime(); ?>` into your sidebar menu (sidebar.php) or where you want it to appear.

5. Drink a beer, smoke a cigarette or celebrate in a way you like! (and maybe you want to add a link to [http://www.neotrinity.at/projects/](http://www.neotrinity.at/projects/))

== Frequently Asked Questions ==

= Why is my timezones.txt file from a version minor 0.20 not working anymore? =

As in the section [installation](http://wordpress.org/extend/plugins/timezonecalculator/installation/) described, there are (mainly) two different timeslots for daylightsaving. - To make your old file working, add the last parameter **daylight saving in or like us timezone** and copy the whole content into the timezones textarea in the Options/TimeZoneCalculator tab.

= Why is 'Drag and Drop' not working? Why can't I see the 'Drag and Drop' section? =

This section is based on internal wordpress javascript-libraries, which means that it will only work with Wordpress versions >= 2.1 and you have to have javascript enabled in your browser (this is a default setting in a common browser like Firefox)! The plugin is still fully functionable without these constraints, but you need to add your timezone entries 'per hand', as in older versions of TimeZoneCalculator.

== Screenshots ==

1. The first screenshot shows the Options/TimeZoneCalculator Tab with the Drag and Drop section in the admin menu.

2. The second screenshot shows the css tags and the preview section of the Options/TimeZoneCalculator Tab in the admin menu.