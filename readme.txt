=== TimeZoneCalculator ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=neo%40neotrinity%2eat&item_name=neotrinity%2eat&no_shipping=1&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: timezone, daylightsaving, world clock, date, widget, sidebar
Requires at least: 1.5
Tested up to: 2.1.3
Stable tag: trunk

Calculates different times and dates in timezones with respect to daylight saving on basis of utc.

== Description ==

Calculates different times and dates in timezones with respect to daylight saving on basis of utc. timezone-infos are available at [timeanddate.com](http://www.timeanddate.com/library/abbreviations/timezones/) or at [wikipedia.org](http://en.wikipedia.org/wiki/Timezones)

**now fully-optionpage-configurable, and as usual easy to integrate (ships with widget functionality)**

== Installation ==

1. Copy the timezonecalculator directory in your WordPress plugins directory (usually wp-content/plugins).

2. In the WordPress admin console, go to the Plugins tab, and activate the TimeZoneCalculator plugin.

3. Go to the Options/TimeZoneCalculator and configure the timezones the way you like
(all data for one timezone in one row, no spaces between ; separator). Feel free to play around and see the result in the section preview at the bottom of the options page. If you are new to TimeZoneCalculator, it's a good start to load the defaults by clicking the button in the right lower corner.

   syntax 4 the timezones:
  * abbr "standard";
  * name "standard";
  * abbr daylight saving;
  * name daylight saving;
  * time-offset;
  * daylight saving 4;
     -  0 ... northern hemisphere
     -  1 ... southern hemisphere
     - -1 ... no daylight saving at all, eg. japan
  * daylight saving in or like us timezone - The [European Summer Time](http://en.wikipedia.org/wiki/European_Summer_Time) lasts between the last Sunday in March and the last Sunday in October. Due to the Energy Bill (HR6 / Energy Policy Act of 2005 or Public Law 109-58), the [daylight saving for the states](http://en.wikipedia.org/wiki/Time_in_the_United_States) starts on the second Sunday in March and ends on the first Sunday in November.
     -  0 ... no us time zone
     -  1 ... us time zone

  examples
  * CET;Central European Time;CEST;Central European Summer Time;1;0;0
  * EST;Eastern Standard Time;EDT;Eastern Daylight Time;10;1;0
  * NZST;New Zealand Standard Time;NZDT;New Zealand Daylight Time;12;1;0
  * PST;Pacific Standard Time;PDT;Pacific Daylight Time;-8;0;1

  timezone-infos are available at
  * [timeanddate.com](http://www.timeanddate.com/library/abbreviations/timezones/)
  * [wikipedia.org](http://en.wikipedia.org/wiki/Timezones)

4. If you got [widget functionality](http://wordpress.org/extend/plugins/widgets/), just drag and drop TimeZoneCalculator on your dynamic sidebar in the presentation menu and name it appropriate. Otherwise, put this code `<?php getTimeZonesTime(); ?>` into your sidebar menu (sidebar.php) or where you want it to appear.

5. Drink a beer, smoke a cigarette or celebrate in a way you like!

== Frequently Asked Questions ==

= Why is my timezones.txt file from a version minor 0.20 not working anymore? =

As in the section [installation](http://wordpress.org/extend/plugins/timezonecalculator/installation/) described, there are (mainly) two different timeslots for daylightsaving. - To make your old file working, add the last parameter **daylight saving in or like us timezone** and copy the whole content into the timezones textarea in the Options/TimeZoneCalculator tab.

== Screenshots ==

1. The screenshot shows the Options/TimeZoneCalculator Tab in the admin menu.