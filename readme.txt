=== TimeZoneCalculator ===
Contributors: neoxx
Donate link: http://www.neotrinity.at/projects/
Tags: timezone, daylightsaving, world clock
Requires at least: 1.5
Tested up to: 2.1
Stable tag: trunk

calculates different times and dates in timezones with respect to daylight saving on basis of utc.

== Description ==

calculates different times and dates in timezones with respect to daylight saving on basis of utc. timezone-infos are available at [timeanddate.com](http://www.timeanddate.com/library/abbreviations/timezones/) or at [wikipedia.org](http://en.wikipedia.org/wiki/Timezones)

== Installation ==

1. Put both the timezonecalculator.php and timezones.txt files in your WordPress plugins directory (usually wp-content/plugins).

2. In the WordPress admin console, go to the Plugins tab, and activate the TimeZoneCalculator plugin.

3. Put this code into your sidebar menu (sidebar.php) or where you want it to appear:
`<?php getTimeZonesTime(); ?>`

4. Edit the timezones.txt file the way you like
(all data for one timezone in one row, no spaces between ; separator)

  You can use the link in the plugin menu. Editing should work if you have the write rights for this file in the plugins directory. If the link doesn't work you can still use the files tab which you can find in the manage tab or upload the file via ftp client after changing on your local system.

  syntax 4 the timezones.txt file:
  * abbr "standard";
  * name "standard";
  * abbr daylight saving;
  * name daylight saving;
  * time-offset;
  * daylight saving inverse 4 hemisphere
     -  0 ... timezone in same timezone as server
     -  1 ... timezone not in server timezone
     - -1 ... no daylight saving at all, eg. japan

  examples (if the server is in the northern hemisphere)
  * CET;Central European Time;CEST;Central European Summer Time;1;0
  * EST;Eastern Standard Time;EDT;Eastern Daylight Time;10;1

  timezone-infos are available at
  * [timeanddate.com](http://www.timeanddate.com/library/abbreviations/timezones/)
  * [wikipedia.org](http://en.wikipedia.org/wiki/Timezones)

5. Drink a beer, smoke a cigarette or celebrate in a way you like!