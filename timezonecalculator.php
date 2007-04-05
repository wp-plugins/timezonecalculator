<?php

/*
Plugin Name: TimeZoneCalculator
Plugin URI: http://www.neotrinity.at/projects/
Description: calculates different times and dates in timezones with respect to daylight saving on basis of utc.
Version: 0.21 (beta)
Author: Bernhard Riedl
Author URI: http://www.neotrinity.at
*/

/*  Copyright 2005-2007  Bernhard Riedl  (email : neo@neotrinity.at)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

add_action('wp_head', 'timezonecalculator_wp_head');

function timezonecalculator_wp_head() {
  echo("<meta name=\"TimeZoneCalculator\" content=\"0.21\" />\n");
}

/*
environment variables
*/

/*
**********************************************
stop editing here unless you know what you do!
**********************************************
/*

/*
this methods prints all timezone entries of the chosen file
*/

function getTimeZonesTime() {

	$before_list=stripslashes(get_option('TimeZones_before_List'));
	$after_list=stripslashes(get_option('TimeZones_after_List'));

	$timeZonesTimeOption=get_option('TimeZones');

	//at minimum one correct entry
	if ($timeZonesTimeOption) {
		$counter=0;

		echo($before_list);

		$timeZonesTime=split("\n", $timeZonesTimeOption);

		foreach ($timeZonesTime as $timeZoneTimeOption) {

			$timeZoneTime=split(";", $timeZoneTimeOption);

			$counter++;

			//data-check ok
			if (checkData($timeZoneTime)) {
				echo (getTimeZoneTime(array($timeZoneTime[0],$timeZoneTime[2]),
							    array($timeZoneTime[1],$timeZoneTime[3]),
							    $timeZoneTime[4],
							    $timeZoneTime[5],$timeZoneTime[6])."\n");
			}

			else {
				getErrorMessage("Could not read line ".$counter."! - Offset, hemisphere or us timezone parameters are not correct. See the examples for hints.");
			}
		}

		echo($after_list);

	}
}

/*
checks if the data matches the defined criteria
*/

function checkData($timeZoneTime) {

	/* hemisphere-options:
		daylight saving 4
     		-  0 ... northern hemisphere
     		-  1 ... southern hemisphere
     		- -1 ... no daylight saving at all, eg. japan
	*/

	$hemisphere=$timeZoneTime[5];
	if ($hemisphere<-1 || $hemisphere>1) {
		return false;
	}

	$usTimeZone=$timeZoneTime[6];
	if ($usTimeZone<-1 || $usTimeZone>1) {
		return false;
	}

	//the offset 4 the timezones can be an integer or somewhat .5 as well
	//thanx 2 Darcy Fiander 4 updating the regex to match the half-hour offsets
	$offset=$timeZoneTime[4];
	if (!ereg("(^[-]?[0-9]{1,2}(\\.5)?$)", $offset)) {
		return false;
	}

	//the maximum offset for a timezone is -12 .. 12
	if ($offset>13 || $offset<-13) {
		return false;
	}

	//all checks correct
	return true;
}

/*
this methods returns the actual timestamp including all relevant data for the chosen timezone for example as list-entry
*/

function getTimeZoneTime($abbrs, $names, $offset, $hemisphere, $ustimezone) {
	$before_tag=stripslashes(get_option('timezones_before_Tag'));
	$after_tag=stripslashes(get_option('timezones_after_Tag'));

	$ret="<abbr title=\"";

	$nowStdDST=isStdDST();
	$nowUSDST=isUSDST();

	//as on servers dst might not be activated, daylightsaving is calculated manually
	$daylightsaving=0;

	//timezone in northern hemisphere
	if ($hemisphere==0) {

		//in standard-daylightsaving zone?
		if ($ustimezone==0) {
			if ($nowStdDST)
				$daylightsaving=1;
		}

		//us-daylightsaving zone
		else {
			if ($nowUSDST)
				$daylightsaving=1;
		}

	}

	//timezone in southern hemisphere
	else if ($hemisphere==1) {

		//in standard-daylightsaving zone?
		if ($ustimezone==0) {
			if (!$nowStdDST)
				$daylightsaving=1;
		}

		//us-daylightsaving zone
		else {
			if (!$nowUSDST)
				$daylightsaving=1;
		}

	}

	$ret=$ret.$names[$daylightsaving]."\">".$abbrs[$daylightsaving]."</abbr>: ";
	$ret=$ret.gmdate('Y-m-d H:i',(time() + 3600 * ($offset + $daylightsaving)));

	return $before_tag.$ret.$after_tag;
}

/*
checks if gmt is within European DST
European DST (since 1996) last Sunday in March to last Sunday in October
created by Matthew Waygood (www.waygoodstuff.co.uk)
modified by Bernhard Riedl (www.neotrinity.at)
*/

function isStdDST() {
	// UTC time
	$timestamp = mktime(gmdate("H, i, s, m, d, Y"));
	$this_year=gmdate("Y", $timestamp);

	// last sunday in march at 1am UTC
	$last_day_of_march=gmmktime(1,0,0,3,31,$this_year);
	$last_sunday_of_march=strtotime("-".gmdate("w", $last_day_of_march)." day", $last_day_of_march);
   
	// last sunday in october at 1am UTC
	$last_day_of_october=gmmktime(1,0,0,10,31,$this_year);
	$last_sunday_of_october=strtotime("-".gmdate("w", $last_day_of_october)." day", $last_day_of_october);

	if( ($timestamp > $last_sunday_of_march) && ($timestamp < $last_sunday_of_october) )
		return true;
	else
		return false;
}

/*
checks if gmt is within US DST
US & Canadian DST second Sunday in March to first Sunday in November
created by Matthew Waygood (www.waygoodstuff.co.uk)
modified by Bernhard Riedl (www.neotrinity.at)
*/

function isUSDST() {
	// UTC time
	$timestamp = mktime(gmdate("H, i, s, m, d, Y"));
	$this_year=gmdate("Y", $timestamp);

	// second sunday in march at 1am UTC
	$last_day_of_february=gmmktime(1,0,0,2,(28+date("L", $timestamp)),$this_year);
	$last_sunday_of_february=strtotime("-".gmdate("w", $last_day_of_february)." day", $last_day_of_february);
	$second_sunday_of_march=mktime(
		gmdate("H", $last_sunday_of_february),
		gmdate("i", $last_sunday_of_february),
		gmdate("s", $last_sunday_of_february),
		gmdate("m", $last_sunday_of_february),
		gmdate("d", $last_sunday_of_february)+14,
		gmdate("Y", $last_sunday_of_february)
	);
   
	// first sunday in november at 1am UTC
	$last_day_of_october=gmmktime(1,0,0,10,31,$this_year);
	$last_sunday_of_october=strtotime("-".gmdate("w", $last_day_of_october)." day", $last_day_of_october);
	$first_sunday_of_november=mktime(
		gmdate("H", $last_sunday_of_october),
		gmdate("i", $last_sunday_of_october),
		gmdate("s", $last_sunday_of_october),
		gmdate("m", $last_sunday_of_october),
		gmdate("d", $last_sunday_of_october)+7,
		gmdate("Y", $last_sunday_of_october)
	);

	if( ($timestamp > $second_sunday_of_march) && ($timestamp < $first_sunday_of_november) )
		return true;
	else
		return false;
}

/*
display errormessage
*/

function getErrorMessage($msg) {
	$before_tag=stripslashes(get_option('timezones_before_Tag'));
	$after_tag=stripslashes(get_option('timezones_after_Tag'));
	echo($before_tag."Sorry! ".$msg.$after_tag);
}

/*
add GeneralStats to WordPress Option Page
*/

function addTimeZoneCalculatorOptionPage() {
    if (function_exists('add_options_page')) {
        add_options_page('TimeZoneCalculator', 'TimeZoneCalculator', 8, __FILE__, 'createTimeZoneCalculatorOptionPage');
    }
}

/*
Option Page
*/

function createTimeZoneCalculatorOptionPage() {

    $csstags=array("TimeZones_before_List", "TimeZones_after_List", "TimeZones_before_Tag", "TimeZones_after_Tag");

    /*
    configuration changed => store parameters
    */

    if (isset($_POST['info_update'])) {

        foreach ($csstags as $csstag) {
            update_option($csstag, $_POST[$csstag]);
        }

        update_option('TimeZones', $_POST['TimeZones']);

        ?><div class="updated"><p><strong>
        <?php _e('Configuration changed!')?></strong></p></div>
     <?php }?>

     <?php
     /*
     options form
     */
    ?>

     <div class="wrap">
       <form method="post">
         <h2>Content</h2>

     		<fieldset>

   		<em>Syntax</em>
		<ul>
			<li>abbr "standard";</li>
			<li>name "standard";</li>
			<li>abbr daylight saving;</li>
			<li>name daylight saving;</li>
			<li>time-offset;</li>

			<li>daylight saving 4;<ul>
			  	<li>0 ... northern hemisphere</li>
				<li>1 ... southern hemisphere</li>
				<li>-1 ... no daylight saving at all, eg. japan</li>
			</ul></li>

			<li>daylight saving in or like us timezone - The <a target="_blank" href="http://en.wikipedia.org/wiki/European_Summer_Time">European Summer Time</a> lasts between the last Sunday in March and the last Sunday in October. Due to the Energy Bill (HR6 / Energy Policy Act of 2005 or Public Law 109-58), the <a target="_blank" href="http://en.wikipedia.org/wiki/Time_in_the_United_States">daylight saving for the states</a> starts on the second Sunday in March and ends on the first Sunday in November.<ul>
				<li>0 ... no us time zone</li>
				<li>1 ... us time zone</li>
			</ul></li>
		</ul>

		<em>Infos</em>
		<ul>
			<li><a target="_blank" href="http://www.timeanddate.com/library/abbreviations/timezones/">timeanddate.com</a></li>
			<li><a target="_blank" href="http://en.wikipedia.org/wiki/Timezones">wikipedia.org</a></li>
		</ul>

		<em>Examples</em>
		<ul>
	    		<li>CET;Central European Time;CEST;Central European Summer Time;1;0;0</li>
	    		<li>EST;Eastern Standard Time;EDT;Eastern Daylight Time;10;1;0</li>
	    		<li>NZST;New Zealand Standard Time;NZDT;New Zealand Daylight Time;12;1;0</li>
	    		<li>PST;Pacific Standard Time;PDT;Pacific Daylight Time;-8;0;1</li>
	    	</ul>

     		</fieldset>

     		<fieldset>
          	<legend>TimeZones</legend>
          	<textarea name="TimeZones" cols="100" rows="5"><?php echo(get_option('TimeZones')); ?></textarea>
     		</fieldset>

        <h2>CSS-Tags</h2>

		<?php

     		foreach ($csstags as $csstag) {
          		echo("<fieldset>");
            	echo("<legend>");
            	_e($csstag);
            	echo("</legend>");
              	echo("<input type=\"text\" size=\"30\" name=\"".$csstag."\" value=\"".htmlspecialchars(stripslashes(get_option($csstag)))."\" />");
       	   	echo("</fieldset>");
	      } ?>

        <h2>Preview (call getTimeZonesTime(); wherever you like!)</h2>
		<?php getTimeZonesTime(); ?>

    <div class="submit">
      <input type="submit" name="info_update" value="<?php _e('Update options') ?>" /></div>
    </form>
    </div>

<?php
}

add_action('admin_menu', 'addTimeZoneCalculatorOptionPage');

?>