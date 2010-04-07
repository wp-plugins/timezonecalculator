<?php

/*
Plugin Name: TimeZoneCalculator
Plugin URI: http://www.neotrinity.at/projects/
Description: Calculates, displays and automatically updates times and dates in different timezones with respect to daylight saving on basis of UTC.
Author: Bernhard Riedl
Version: 1.23
Author URI: http://www.neotrinity.at
*/

/*  Copyright 2005-2009  Bernhard Riedl  (email : neo@neotrinity.at)
    Inspirations & Proof-Reading by Veronika Grascher

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


/*
**********************************************
stop editing here unless you know what you do!
**********************************************
/*

/*
called from init hook
*/

function timezonecalculator_init() {

	/*
	TimeZoneCalculator Constants
	*/

	DEFINE ('TIMEZONECALCULATOR_PLUGINURL', plugins_url('', __FILE__) . '/');

	DEFINE ('TIMEZONECALCULATOR_CURRENTGMDATE', gmdate('U'));

	/* check for ajax-refresh-call */

	if (isset($_POST['timezonecalculator-refresh'])) {
		getTimeZonesTime();
		exit;
	}

	if(get_option('timezones_Use_Ajax_Refresh')=='1') {
		wp_enqueue_script('prototype');
	}

}

/*
adds the javascript code for re-occuring timezone-updates
*/

function timezonecalculator_ajax_refresh() {

	$fieldsPre="timezones_";

	if(get_option($fieldsPre.'Use_Ajax_Refresh')=='1') { 
		$refreshTime = get_option($fieldsPre.'Refresh_Time');

		//regex taken from php.net by mark at codedesigner dot nl
		if (!preg_match('@^[-]?[0-9]+$@',$refreshTime) || $refreshTime<1)
			$refreshTime=30;
	?>

<script type="text/javascript" language="javascript">

	/* <![CDATA[ */

	/*
	TimeZoneCalculator AJAX Refresh
	*/

	var timezones_divclassname='timezonecalculator-refreshable-output';

	function timezones_refresh() {
		var params = 'timezonecalculator-refresh=1';
		new Ajax.Request(
			'<?php echo(get_option('home'). '/'); ?>',
			{
				method: 'post',
				parameters: params,
				onSuccess: timezones_handleReply
			});
	}

	function timezones_handleReply(response) {
		if (200 == response.status){
			var resultText=response.responseText;

			if (resultText.indexOf('<div class="'+timezones_divclassname+'"')>-1) {
				var timezones_blocks=$$('div.'+timezones_divclassname);
				for (var i=0;i<timezones_blocks.length;i++) {
					Element.replace(timezones_blocks[i], resultText);
				}
			}
		}
	}

	Event.observe(window, 'load', function(e){ if ($$('div.'+timezones_divclassname).length>0) new PeriodicalExecuter(timezones_refresh, <?php echo($refreshTime); ?>); });

	/* ]]> */

</script>

	<?php }
}

/*
adds a settings link in the plugin-tab
*/

function timezonecalculator_adminmenu_plugin_actions($links, $file) {
	if ($file == plugin_basename(__FILE__))
		$links[] = "<a href='options-general.php?page=".plugin_basename(__FILE__)."'>" . __('Settings') . "</a>";

	return $links;
}

/*
loads the necessary java-scripts,
which are all included in wordpress
for the admin-page
*/

function timezones_admin_print_scripts() {
	wp_enqueue_script('prototype');
	wp_enqueue_script('scriptaculous-dragdrop');
	wp_enqueue_script('scriptaculous-effects');
	wp_enqueue_script('datepicker', TIMEZONECALCULATOR_PLUGINURL.'date-picker/js/datepicker.js');
}

/*
populates the timezone-selects in the admin-menu
*/

function timezones_load_admin_selects() {

	$timezones_newentry="timezones_newentry_";
	$timezones_calculation="timezones_calculation_";
	$timezones_calculation_pickdate=$timezones_calculation."pickdate_";

?>

<script type="text/javascript" language="javascript">

	/* <![CDATA[ */

	/*
	TimeZoneCalculator TimeZones-Selects
	*/

	var timezones_options='<?php echo(timezonecalculator_makeTimeZonesSelect()); ?>';

	var timezones_newentry_select='<select onkeyup="if(event.keyCode==13) timezones_appendEntry();" name="<?php echo($timezones_newentry); ?>timezone_id" id="<?php echo($timezones_newentry); ?>timezone_id" style="size:1">'+timezones_options+'</select>';

	Event.observe(window, 'load', function(e){ Element.replace('<?php echo($timezones_newentry); ?>timezone_id', timezones_newentry_select); });

	var timezones_calculation_pickdate_select='<select name="<?php echo($timezones_calculation_pickdate); ?>Timezone" id="<?php echo($timezones_calculation_pickdate); ?>Timezone" style="size:1">'+timezones_options+'</select>';

	Event.observe(window, 'load', function(e){ Element.replace('<?php echo($timezones_calculation_pickdate); ?>Timezone_temp', timezones_calculation_pickdate_select); });

	var timezones_calculation_select='<select name="<?php echo($timezones_calculation); ?>Timezone" id="<?php echo($timezones_calculation); ?>Timezone" style="size:1">'+timezones_options+'</select>';

	Event.observe(window, 'load', function(e){ Element.replace('<?php echo($timezones_calculation); ?>Timezone_temp', timezones_calculation_select); });

	/* ]]> */

</script>

<?php }

/*
process the admin_color-array
*/

function timezonecalculator_get_admin_colors() {

	/*
	default colors = fresh
	*/

	$available_admin_colors=array("fresh" => array("#464646", "#6D6D6D", "#F1F1F1", "#DFDFDF"), "classic" => array("#073447", "#21759B", "#EAF3FA", "#BBD8E7") );

	$current_color = get_user_option('admin_color');
	if (strlen($current_color)<1)
		$current_color="fresh";

	/*
	include user-defined color schemes
	*/

	$timezonecalculator_available_admin_colors = apply_filters('timezonecalculator_available_admin_colors', array());

	if (!empty($timezonecalculator_available_admin_colors) && is_array($timezonecalculator_available_admin_colors))
		foreach($timezonecalculator_available_admin_colors as $key => $available_admin_color)
			if (is_array($available_admin_color) && sizeof($available_admin_color)==4)
				if (!array_key_exists($key, $available_admin_colors))
					$available_admin_colors[$key]=$timezonecalculator_available_admin_colors[$key];

	if (!array_key_exists($current_color, $available_admin_colors))
		return $available_admin_colors["fresh"];
	else
		return $available_admin_colors[$current_color];
}

/*
loads the necessary css-styles
for the admin-page
*/

function timezones_admin_head() {
	timezones_load_admin_selects();
		$timezonecalculator_admin_css_colors=timezonecalculator_get_admin_colors();
?>
     <style type="text/css">

	.timezones_wrap ul {
		list-style-type : disc;
		padding: 5px 5px 5px 30px;
	}

      li.timezones_sortablelist {
		background-color: <?php echo $timezonecalculator_admin_css_colors[1]; ?>;
		color: <?php echo $timezonecalculator_admin_css_colors[3]; ?>;
		cursor : move;
		padding: 3px 5px 3px 5px;
      }

      ul.timezones_sortablelist {
		float: left;
		border: 1px <?php echo $timezonecalculator_admin_css_colors[0]; ?> solid;
		list-style-image : none;
		list-style-type : none;
		margin: 10px 20px 20px 0px;
		padding: 10px;
      }

      #timezones_DragandDrop, #timezones_Search {
		float: right;
		cursor : move;
		border: 1px dotted;
		margin: 10px 20px 0px 0px;
		width: 400px;
		padding: 5px;
      }

	img.timezones_arrowbutton {
		vertical-align: bottom;
		cursor: pointer;
		margin-left: 5px;
	}

	img.timezones_sectionbutton {
		cursor: pointer;
	}

	ul.subsubsub.timezones {
		list-style: none;
		margin: 8px 0 5px;
		padding: 0;
		white-space: nowrap;
		float: left;
		float: none;
		display: block;
	}
 
	ul.subsubsub.timezones a {
		line-height: 2;
		padding: .2em;
		text-decoration: none;
	}

	ul.subsubsub.timezones li {
		display: inline;
		margin: 0;
		padding: 0;
		border-left: 1px solid #ccc;
		padding: 0 .5em;
	}

	ul.subsubsub.timezones li:first-child {
		padding-left: 0;
		border-left: none;
	}

      </style>

<link rel='stylesheet' href='<?php echo(TIMEZONECALCULATOR_PLUGINURL); ?>date-picker/css/datepicker.css' type='text/css' />

<?php }

/*
adds some css to format timezonecalculator on the dashboard
*/

function timezones_add_dashboard_widget_css() {

?>

	<style type="text/css">

	.timezonecalculator-refreshable-output {
		font-size:11px;
		line-height:140%;
	}

      </style>

<?php

}

/*
add dashboard widget
*/

function timezonecalculator_add_dashboard_widget() {
	wp_add_dashboard_widget('timezonecalculator_dashboard_widget', 'TimeZoneCalculator', 'getTimeZonesTime');
} 

/*
called from widget_init hook
*/

function widget_timezonecalculator_init() {
	register_widget('WP_Widget_TimeZoneCalculator');
}

/*
adds metainformation - please leave this for stats!
*/

function timezonecalculator_wp_head() {
  echo("<meta name=\"TimeZoneCalculator\" content=\"1.23\" />\n");
}

/*
checks and transforms a time-string and a timezone-string into a unix timestamp
*/

function timezonecalculator_calculateDate($time_string, $timezone_string) {
	$myDate=false;

	//regex taken from php.net by mark at codedesigner dot nl
	if (preg_match('@^[-]?[0-9]+$@',$time_string)) {
		$myDate=$time_string;
	}

	elseif (strlen($time_string)>2) {

		/*
		calculate server offset as
		strtotime uses
		server-timezone-settings
		*/

		$serverOffset=0;

		if (function_exists('date_default_timezone_get')) {
			$serverTimeZone=@date_default_timezone_get();
			$zeroOffset=array('UTC', 'UCT', 'GMT', 'GMT0', 'GMT+0', 'GMT-0', 'Greenwich', 'Universal', 'Zulu');

			if ($serverTimeZone && !empty($serverTimeZone) && !in_array(str_ireplace('etc/', '', $serverTimeZone), $zeroOffset)) {
				$offset=timezonecalculator_calculateUTC(TIMEZONECALCULATOR_CURRENTGMDATE, $serverTimeZone);
				if ($offset!==false) {
					$serverOffset=$offset;
				}
			}
		}

		$parsedDate=strtotime($time_string, TIMEZONECALCULATOR_CURRENTGMDATE)+$serverOffset;
		if ($parsedDate!==false && $parsedDate!=-1) {
			$myDate=$parsedDate;
		}
	}

	/*
	all timestamps between 1930-01-01 and 2038-01-01 are accepted
	*/

	if ($myDate!==false && (int)$myDate>=-1262304000&& (int)$myDate<=2145916800) {
		$offset=timezonecalculator_calculateUTC($myDate, $timezone_string);
		if ($offset!==false) {
			return ($myDate-$offset);
		}
	}

	return false;

}

/*
calculates UTC for given unix-timestamp and timezone_string
*/

function timezonecalculator_calculateUTC($localTime, $timezone_string) {
	$offset=0;

	//WordPress TimeZones Support
	if ($timezone_string=='Local_WordPress_Time') {
		$timezone_string=get_option('timezone_string');
	}

	$timezone=@timezone_open($timezone_string);

	//if timezone is available, lookup offset
	if (!$timezone)
		return false;

	/*
	inspired from Derick's talk
	http://talks.php.net/show/time-ffm2006/28
	*/

	//lookup array until current transition has been found
	foreach (timezone_transitions_get($timezone) as $tr) {
		if ($tr['ts'] > ($localTime-$offset)) {
			return $offset;
		}

		$offset=$tr['offset'];
	}

	return $offset;
}

/*
this methods echos all timezone entries

please refer to the readme.txt for
further information on the function parameter
*/

function getTimeZonesTime($time_string='', $timezone_string='UTC', $alt_style=false, $alt_before_list='<ul>', $alt_after_list='</ul>', $alt_before_tag='<li>', $alt_after_tag='</li>', $alt_timeformat='Y-m-d H:i', $alt_timezones=array(), $display_name=true, $use_container=true) {
	$currentTimeStamp=false;

	$fieldsPre="timezones_";

	/*
	shall we wrap the output in a container?
	*/

	if ($use_container===true) {
		$before_list_container='<div class="timezonecalculator-refreshable-output">';
		$after_list_container='</div>';
	}
	else {
		$before_list_container='';
		$after_list_container='';
	}

	/*
	use styles from database
	*/

	if ($alt_style===false) {
		$before_list=get_option($fieldsPre.'before_List');
		$after_list=get_option($fieldsPre.'after_List');

		$before_tag=get_option($fieldsPre.'before_Tag');
		$after_tag=get_option($fieldsPre.'after_Tag');

		$timeFormat=get_option($fieldsPre.'Time_Format');
		if (strlen($timeFormat)<1)
			$timeFormat="Y-m-d H:i";
		}

	/*
	use alternative style
	*/

	else {
		$before_list=$alt_before_list;
		$after_list=$alt_after_list;

		$before_tag=$alt_before_tag;
		$after_tag=$alt_after_tag;

		$timeFormat=$alt_timeformat;
	}

	//necessary library is not installed
	if (!timezonecalculator_checkTimeZoneFunction()) {
		timezonecalculator_getErrorMessageBlock('Please make sure that you have a recent version of php including the php timezones library installed!', $before_list_container, $after_list_container, $before_list, $after_list, $before_tag, $after_tag);

		return;
	}

	//parameters given -> check validity and calculate
	if (strlen($time_string)>2) {

		/*
		if a timestamp has been set and the output shall be wrapped
		in a container, we use the non-updateable container
		*/

		if ($use_container===true) {
			$before_list_container='<div class="timezonecalculator-output">';
		}

		//calculate unix timestamp with parameters
		$currentTimeStamp=timezonecalculator_calculateDate($time_string, $timezone_string);

		if ($currentTimeStamp===false) {
			timezonecalculator_getErrorMessageBlock('Either the Time-String or the TimeZone-String is not correct!', $before_list_container, $after_list_container, $before_list, $after_list, $before_tag, $after_tag);

			return;
		}

	}

	/*
	if no parameters are present,
	we use the current unix timestamp in utc instead
	*/

	if ($currentTimeStamp===false) {
		$currentTimeStamp=TIMEZONECALCULATOR_CURRENTGMDATE;
	}

	$timeZonesTimeOption=get_option('TimeZones');

	$errors=array('wrong number of parameters',
		'timezone-id wrong',
		'wrong parameter used for database abbreviations',
		'wrong parameter used for database names',
		'old timezone-format');

	echo($before_list_container);
	echo($before_list);

	//at minimum one entry
	if ($timeZonesTimeOption || sizeof($alt_timezones)>0 ) {
		$counter=0;

		/*
		if no alternative timezones-array is present,
		we use the information stored in the database
		*/

		if (sizeof($alt_timezones)==0)
			$timeZonesTime=explode("\n", $timeZonesTimeOption);
		else
			$timeZonesTime=$alt_timezones;

		foreach ($timeZonesTime as $timeZoneTimeOption) {

			//is there anything to parse in the particular line?
			if (strlen($timeZoneTimeOption)>1) {
				$counter++;

				$timeZoneTime=explode(";", $timeZoneTimeOption);

				$dateTimeZone=timezonecalculator_checkData($timeZoneTime);

				//data-check failed
				if (!is_object($dateTimeZone)) {
					timezonecalculator_getErrorMessage("Sorry, could not read timezones-entry ".$counter.": ".$errors[abs($dateTimeZone)-1], $before_tag, $after_tag);
				}

				//data-check ok
				else {
					echo (getTimeZoneTime($dateTimeZone, 					array($timeZoneTime[1],$timeZoneTime[2]),
					array($timeZoneTime[3],$timeZoneTime[4]),
					trim($timeZoneTime[5]),trim($timeZoneTime[6]),
					$timeFormat, $currentTimeStamp, $before_tag, $after_tag, $display_name));
				}
			}
		}


	}

	else {
		timezonecalculator_getErrorMessage("No Timezones to display yet...", $before_tag, $after_tag);
	}

	echo($after_list);
	echo($after_list_container);
}

/*
checks if the data matches the defined criteria
*/

function timezonecalculator_checkData($timeZoneTime) {
	//first check if the size of the timezones-array match
	if (sizeof($timeZoneTime)!=7)
		return -1;

	//WordPress TimeZones Support
	if ($timeZoneTime[0]=='Local_WordPress_Time') {
		$timeZoneTime[0]=get_option('timezone_string');
	}

	//the timezone_id should contain at least one character
	if (strlen($timeZoneTime[0])<1)
		return -2;

	//are the last two array-parameters 0 or 1?
	$mycheck=false;
	if ($timeZoneTime[5]==1 || $timeZoneTime[5]==0)
		$mycheck=true;
	if (!$mycheck)
		return -3;

	$mycheck=false;
	if ($timeZoneTime[6]==1 || $timeZoneTime[6]==0)
		$mycheck=true;
	if (!$mycheck)
		return -4;

	/*
	previous-version check by the following assumption:
	if array-parameter [4] (offset in older versions) is numeric
	&& the value between -12.5 and 12.5 ==> old version
	throw error
	*/

	//thanx 2 Darcy Fiander 4 updating the regex to match the half-hour offsets which is now used for old-version checks
	if (ereg("(^[-]?[0-9]{1,2}(\\.5)?$)", $timeZoneTime[4]) && $timeZoneTime[4]>=-12.5 && $timeZoneTime[4]<=12.5) {
		return -5;
	}

	//check if timezone_id exists by creating a new instance
	$dateTimeZone=@timezone_open($timeZoneTime[0]);

	if (!$dateTimeZone)
		return -2;
	else return $dateTimeZone;
}

/*
this methods returns the actual timestamp including all relevant data for the chosen timezone for example as list-entry
*/

function getTimeZoneTime($dateTimeZone, $abbrs, $names, $use_db_abbr, $use_db_name, $timeFormat, $currentTimeStamp, $before_tag, $after_tag, $display_name) {
	$daylightsavingArr=timezonecalculator_isDST($dateTimeZone, $currentTimeStamp);
	$daylightsaving=$daylightsavingArr[0];

	//first optional the abbreviation
	$abbr=$daylightsavingArr[1];

	$offset=$daylightsavingArr[2];

	if ($use_db_abbr==0)
		$abbr=$abbrs[$daylightsaving];

	if (strlen($abbr)>0) {
		//and also optional a mouse-over tooltiptext
		$name=str_replace('_', ' ', $dateTimeZone->getName());
		if ($use_db_name==0)
			$name=$names[$daylightsaving];

		if ($display_name===true && strlen($name)>0)
			$ret="<abbr title=\"".htmlspecialchars($name, ENT_QUOTES)."\">".htmlspecialchars($abbr, ENT_QUOTES)."</abbr>";
		else
			$ret=htmlspecialchars($abbr, ENT_QUOTES);

		$ret.=": ";
	}

	//display only the time
	else {
		$ret="";
	}

	$ret.=gmdate($timeFormat, ($currentTimeStamp + $offset));

	return $before_tag.$ret.$after_tag;
}

/*
checks if timezone is within DST
returns boolean isdst and current abbreviation in array
*/

function timezonecalculator_isDST($timezone, $currentTimeStamp) {
	$isDst=0;
	$abbr=$timezone->getName();
	$offset=0;

	/*
	inspired from Derick's talk
	http://talks.php.net/show/time-ffm2006/28
	*/

	//lookup array until current transition has been found
	foreach (timezone_transitions_get($timezone) as $tr) {
		if ($tr['ts'] > $currentTimeStamp)
			break;

		if((bool)$tr['isdst']===true)
			$isDst=1;
		else
			$isDst=0;

		$abbr=$tr['abbr'];
		$offset=$tr['offset'];
	}

	return array($isDst, $abbr, $offset);
}

/*
display errormessage
*/

function timezonecalculator_getErrorMessage($msg, $before_tag, $after_tag) {
	echo($before_tag.$msg.$after_tag);
}

/*
display wrapped errormessage
*/

function timezonecalculator_getErrorMessageBlock($msg, $before_list_container, $after_list_container, $before_list, $after_list, $before_tag, $after_tag) {
		echo($before_list_container.$before_list);
		timezonecalculator_getErrorMessage($msg, $before_tag, $after_tag);
		echo($after_list.$after_list_container);
}

/*
checks for necessary php timezone-functions
*/

function timezonecalculator_checkTimeZoneFunction() {
	if (function_exists('timezone_open') &&
		function_exists('timezone_transitions_get') &&
		function_exists('timezone_name_get') &&
		function_exists('timezone_offset_get') &&
		function_exists('timezone_identifiers_list') )

		return true;
	else
		return false;
}

/*
add TimeZoneCalculator to WordPress Option Page
*/

function addTimeZoneCalculatorOptionPage() {
	if (timezonecalculator_checkTimeZoneFunction()) {
        $page=add_options_page('TimeZoneCalculator', 'TimeZoneCalculator', 8, __FILE__, 'createTimeZoneCalculatorOptionPage');
        add_action('admin_print_scripts-'.$page, 'timezones_admin_print_scripts');
        add_action('admin_head-'.$page, 'timezones_admin_head');
	}
	else {
        $page=add_options_page('TimeZoneCalculator', 'TimeZoneCalculator', 8, __FILE__, 'createTimeZoneCalculatorFailurePage');
	}
}

/*
produce toggle button for showing and hiding sections
*/

function timezonecalculator_open_close_section($section, $default) {

	$sectionPost='_Section';

	if ($default==='1') {
		$defaultImage='down';
		$defaultAlt='hide';
	}
	else {
		$defaultImage='right';
		$defaultAlt='show';
	}

	echo("<img id=\"".$section.$sectionPost."_Button\" class=\"timezones_sectionbutton\" onclick=\"timezonecalculator_toggleSectionDiv(this, '".$section."');\" alt=\"".$defaultAlt." Section\" src=\"".TIMEZONECALCULATOR_PLUGINURL."arrow_".$defaultImage."_blue.png\" />&nbsp;");
}

/*
creates section-toogle link
use js to open section automatically,
if closed
*/

function timezonecalculator_get_section_link($section, $allSections, $section_nicename='') {
	if (!array_key_exists($section, $allSections))
		return;

	$fieldsPre="timezones_";
	$sectionPost='_Section';

	$menuitem_onclick='';

	if (strlen($section_nicename)<1)
		$section_nicename=str_replace('_', ' ', $section);

	if ($allSections[$section]=='1')
		$menuitem_onclick=" onclick=\"timezonecalculator_assure_open_section('".$section."');\"";

	return '<a'.$menuitem_onclick.' href="#'.$fieldsPre.$section.'">'.$section_nicename.'</a>';
}

/*
returns the optiongroups and options of all php timezones for including in a html select
*/

function timezonecalculator_makeTimeZonesSelect($selectedzone='') {

	//all continents except etc-group
	$continents = array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific');

	$allTimezones = timezone_identifiers_list();

	$i = 0;
	foreach ( $allTimezones as $zone ) {
		$zoneArr = explode('/',$zone);

		//is timezone in continents array -> exclude etc-timezones
		if ( ! in_array($zoneArr[0], $continents) )
			continue;

		$zonen[$i]['continent'] = isset($zoneArr[0]) ? $zoneArr[0] : '';
		$zonen[$i]['city'] = isset($zoneArr[1]) ? $zoneArr[1] : '';
		$zonen[$i]['subcity'] = isset($zoneArr[2]) ? $zoneArr[2] : '';
		$i++;
	}

	asort($zonen);
	$structure = '<option value="UTC"';

	if ( strlen($selectedzone)==0 )
		$structure .= ' selected="selected"';

	//let's make UTC the default if no timezone has been handed over
	$structure .= ">UTC</option>";

	/*in addition add a local WordPress timezone option if the corresponding setting exists */

	$wordpress_timezone=get_option('timezone_string');
	if(strlen($wordpress_timezone)>0 && @timezone_open($wordpress_timezone)) {
		$structure .= "<option value=\"Local_WordPress_Time\">Local Wordpress Time</option>";
	}

	$selectcontinent='';
	$firstcontinent=true;

	foreach ( $zonen as $zone ) {
		extract($zone);

		//create continent optgroup and close an open one
		if ( ($selectcontinent != $continent) && !empty($city) ) {
			$selectcontinent = $continent;

			if ($firstcontinent) {
				$firstcontinent=false;
			}
			else {
				$structure .= "</optgroup>";
			}

			$structure .= '<optgroup label="'.$continent.'">';
		}

		//if a city name exists, add entry to list
		if ( !empty($city) ) {
			if ( !empty($subcity) ) {
				$city = $city . '/'. $subcity;
			}
			$structure .= "<option".((($continent.'/'.$city)==$selectedzone)?' selected="selected"':'')." value=\"".($continent.'/'.$city)."\">&nbsp;&nbsp;&nbsp;".str_replace('_',' ',$city)."</option>";
		}
	}

	return $structure.= "</optgroup>";
}

/*
displays a Failure Page in the Admin Menu as not all PHP-Timezone-Functions are available
*/

function createTimeZoneCalculatorFailurePage() { ?>
	<div class="wrap"><div class="timezones_wrap">

<h2>Sorry...</h2><br />

Dear fellow User,<br /><br />

As TimeZoneCalculator 0.90 and higher uses the php timezones library, please make sure that you have a recent version of php including the required library installed! Information about your current version is displayed below.<br /><br />

In case you don't have a suiting environment, you can still use <a href="http://downloads.wordpress.org/plugin/timezonecalculator.last_version_with_built-in_calculations.zip" target="_blank" title="version 0.81">version 0.81</a> which is the last TimeZoneCalculator version that can be used with older php versions.<br /><br />

Nevertheless, please note that due to various security reasons there should always be a recent version of php installed. In case of a hosted environment, please contact your provider for further information.<br /><br /><br /><br />

<?php 

/*
taken from http://th2.php.net/phpinfo
*/

ob_start();
phpinfo();
$info = ob_get_contents();
ob_end_clean();

$info = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);
echo($info);
?>

	</div></div>
<?php }

/*
Output JS
*/

function TimeZoneCalculatorOptionPageActionButtons($num) { ?>
	<div id="timezones_actionbuttons_<?php echo($num); ?>" class="submit" style="display:none">
		<input type="button" id="info_update_click<?php echo($num); ?>" name="info_update_click<?php echo($num); ?>" value="<?php echo('Update options') ?>" />
		<input type="button" id="load_default_click<?php echo($num); ?>" name="load_default_click<?php echo($num); ?>" value="<?php echo('Load defaults') ?>" />
	</div>
<?php }

/*
adds a leading zero for minute and hour strings
*/

function timezonecalculator_lz($input) {
	$retVal='';

	if ($input<10)
		$retVal.='0';

	$retVal.=$input;
	return $retVal; 
}

/*
displays the Option Page in the Admin Menu
*/

function createTimeZoneCalculatorOptionPage() {

    $fieldsPre="timezones_";
    $sectionPost="_Section";

    $csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "Time_Format");
    $csstags_defaults=array("<ul>", "</ul>", "<li>", "</li>", "Y-m-d H:i");

    $Use_Ajax_Refresh="Use_Ajax_Refresh";
    $Refresh_Time="Refresh_Time";

    $sections=array('Instructions' => '1', 'Content' => '1', 'CSS_Tags' => '1', 'Administrative_Options' => '1', 'Calculation' => '1');

    /*
    configuration changed => store parameters
    */

    if (isset($_POST['info_update'])) {

        foreach ($csstags as $csstag) {
            update_option($fieldsPre.$csstag, stripslashes($_POST[$fieldsPre.$csstag]));
        }

        update_option('TimeZones', stripslashes($_POST['TimeZones']));

	if (isset($_POST[$fieldsPre.$Use_Ajax_Refresh])) {
	  update_option($fieldsPre.$Use_Ajax_Refresh, '1');
	  update_option($fieldsPre.$Refresh_Time, $_POST[$fieldsPre.$Refresh_Time]);
	}
	else {
	  update_option($fieldsPre.$Use_Ajax_Refresh, '0');
	  update_option($fieldsPre.$Refresh_Time, '');
	}

        foreach ($sections as $key => $section) {
            update_option($fieldsPre.$key.$sectionPost, $_POST[$fieldsPre.$key.$sectionPost.'_Show']);
        }

        ?><div class="updated"><p><strong>
        <?php echo('Configuration changed!<br /><br />Have a look at <a href="#'.$fieldsPre.'Preview">the preview</a>!')?></strong></p></div>
      <?php }

      elseif (isset($_POST['load_default'])) {

        for ($i = 0; $i < sizeof($csstags); $i++) {
            update_option($fieldsPre.$csstags[$i], $csstags_defaults[$i]);
        }

        update_option('TimeZones', 'UTC;;;;;1;1');

        update_option($fieldsPre.$Use_Ajax_Refresh, '0');
        update_option($fieldsPre.$Refresh_Time, '');

        foreach ($sections as $key => $section) {
            update_option($fieldsPre.$key.$sectionPost, $section);
        }

        ?><div class="updated"><p><strong>
        <?php echo('Defaults loaded!')?></strong></p></div>

      <?php }

      elseif (isset($_POST['set_time'])) {

        ?><div class="updated"><p><strong>
        <?php echo('Time for TimeZoneCalculator has been temporarly set!<br /><br />Have a look at <a href="#'.$fieldsPre.'Preview">the preview</a>!')?></strong></p></div>

      <?php }

	/* manual cleanup */

      elseif (isset($_GET['cleanup'])) {

	  timezonecalculator_uninstall();

        ?><div class="updated"><p><strong>
        <?php echo('Settings deleted!')?></strong></p></div>

      <?php }

	foreach($sections as $key => $section) {
		if (get_option($fieldsPre.$key.$sectionPost)!="") $sections[$key] = get_option($fieldsPre.$key.$sectionPost);
	}

	/*
	begin list
	*/

	$listTaken="";
	$before_tag="<li class=\"timezones_sortablelist\" id=";
	$after_tag="</li>";

	/*
	build list
	*/

	$beforeKey="Tags_";

	$timeZonesTimeOption=get_option('TimeZones');

	$counter=0;

	$listTakenListeners="";

	//at minimum one correct entry
	if ($timeZonesTimeOption) {
		$timeZonesTime=explode("\n", $timeZonesTimeOption);

		foreach ($timeZonesTime as $timeZoneTimeOption) {
			if (strlen($timeZoneTimeOption)>1) {
				$timeZoneTime=explode(";", $timeZoneTimeOption);
				$tag='';
				$otherOptions='';

				if (sizeof($timeZoneTime)>0) {
					$tag=$timeZoneTime[0];
					if (strlen($tag)<1)
						$tag='UTC';

					$otherOptions='<input type="hidden" value="';
					if (sizeof($timeZoneTime)==7) {
						$otherOptions.=htmlspecialchars(trim(implode(';',array_slice($timeZoneTime, 1))), ENT_QUOTES);
					}
					else {
						$otherOptions.=';;;;1;1';
					}

					$otherOptions.='" />';

			            $upArrow='<img class="timezones_arrowbutton" src="'.TIMEZONECALCULATOR_PLUGINURL.'arrow_up_blue.png" onclick="timezones_moveElementUp('.$counter.');" alt="move element up" />';
			            $downArrow='<img class="timezones_arrowbutton" style="margin-right:20px;" src="'.TIMEZONECALCULATOR_PLUGINURL.'arrow_down_blue.png" onclick="timezones_moveElementDown('.$counter.');" alt="move element down" />';
					$listTakenListeners.="Event.observe('".$beforeKey.$counter."', 'click', function(e){ timezones_adoptDragandDropEdit('".$counter."') });";
					$listTaken.= $before_tag. "\"".$beforeKey.$counter."\">".$upArrow.$downArrow.$tag.$otherOptions.$after_tag. "\n";
					$counter++;
				}
			}
		}
	}

	/*
	format list
	*/

	$elementHeight=32;

	$sizeListTaken=$counter*$elementHeight;
	if ($counter<=0) $sizeListTaken=$elementHeight;
	$sizeListAvailable=$elementHeight/2;

	$listTaken="<div style=\"cursor:move\" id=\"timezones_listTaken\"><h3>TimeZone Entries</h3><ul class=\"timezones_sortablelist\" id=\"tz_listTaken\" style=\"height:".$sizeListTaken."px;width:320px;\">".$listTaken."</ul></div>";
	$listAvailable="<div style=\"cursor:move\" id=\"timezones_listAvailable\"><h3>Garbage Bin</h3><ul class=\"timezones_sortablelist\" id=\"tz_listAvailable\" style=\"height:".$sizeListAvailable."px;width:320px;\"><li style=\"display:none\"></li></ul></div>";

	/*
	options form
	*/

	?>

	<div class="wrap">
	<ul class="subsubsub timezones">
	<?php
	$allSections=array();

	foreach ($sections as $key => $section) {
		$allSections[$key]='1';

		if ($key=='Instructions')
			$allSections['Drag_and_Drop']='0';
	}

	$allSections['Preview']='0';

	$timezonecalculator_menu='';

	foreach ($allSections as $key => $section)
		$timezonecalculator_menu.='<li>'.timezonecalculator_get_section_link($key, $allSections).'</li>';

	echo($timezonecalculator_menu);
	?>
	</ul>

	<div class="timezones_wrap">

Welcome to the Settings-Page of <a target="_blank" href="http://www.neotrinity.at/projects/">TimeZoneCalculator</a>. This plugin calculates, displays and automatically updates times and dates in different timezones with respect to daylight saving on basis of <abbr title="Coordinated Universal Time">UTC</abbr>.

<h2><?php timezonecalculator_open_close_section($fieldsPre.'Instructions', $sections['Instructions']); ?>Instructions</h2>

	<div id="<?php echo($fieldsPre); ?>Instructions<?php echo($sectionPost); ?>" <?php if ($sections['Instructions']==='0') { ?>style="display:none"<?php } ?>>

     <ul>
        <li>It may be a good start for TimeZoneCalculator first-timers to click on <strong>Load defaults</strong>.</li>
        <li>You can insert new timezones by filling out the form on the right in the <?php echo(timezonecalculator_get_section_link('Drag_and_Drop', $allSections, 'Drag and Drop Layout Section')); ?> and clicking <strong>Insert</strong>. All parameters of TimeZoneCalculator can also be changed in the <?php echo(timezonecalculator_get_section_link('Content', $allSections, 'Content Section')); ?> without the usage of Javascript. Anyway, new entries are only saved after clicking on <strong>Update options</strong>.<br />

Hint: Information about cities and their timezones can be searched below.</li>
	  <li>To customize existing timezones click on the entry you want to change in any list and edit the parameters in the form on the right. After clicking <strong>Edit</strong> the selected timezone's parameters are adopted in its list. The timezones can be re-orderd within a list either by drag and drop or by clicking on the arrows on the particular timezone's left hand side. Don't forget to save all your adjustments by clicking on <strong>Update options</strong>.</li>
        <li>To remove timezones from the list just drag and drop them onto the Garbage Bin and click on <strong>Update options</strong>.</li>
        <li>Style-customizations can be made in the <?php echo(timezonecalculator_get_section_link('CSS_Tags', $allSections, 'CSS-Tags Section')); ?>. (Defaults are automatically populated via the <strong>Load defaults</strong> button)</li>
        <li>You can activate an optional Ajax refresh for automatical updates of your timezones-output in the <?php echo(timezonecalculator_get_section_link('Administrative_Options', $allSections, 'Administrative Options Section')); ?>.</li>
        <li>Before you publish the results you can use the <?php echo(timezonecalculator_get_section_link('Preview', $allSections, 'Preview Section')); ?>.</li>
        <li>Finally, you can publish the previously selected and saved timezones either by adding a <a href="widgets.php">Sidebar Widget</a> or by calling the php function <code>getTimeZonesTime()</code> wherever you like. Moreover you can also display your current timezone-selection as <a href="index.php">Dashboard Widget</a>.</li>
        <li>By temporarily setting date and time in the <?php echo(timezonecalculator_get_section_link('Calculation', $allSections, 'Calculation Section')); ?>, you can check a certain timestamp in different timezones.</li>
        <li>If you decide to uninstall TimeZoneCalculator firstly remove the optionally added <a href="widgets.php">Sidebar Widget</a> or the integrated php function call(s) and secondly disable and delete it in the <a href="plugins.php">Plugins Tab</a>.</li>
</ul>

<?php TimeZoneCalculatorOptionPageActionButtons(1); ?>

</div>

<h2>Support</h2>
        If you like to support the development of this plugin, donations are welcome. <?php echo(convert_smilies(':)')); ?> Maybe you also want to <a href="link-add.php">add a link</a> to <a href="http://www.neotrinity.at/projects/">http://www.neotrinity.at/projects/</a>.<br /><br />

        <form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_xclick" /><input type="hidden" name="business" value="&#110;&#101;&#111;&#64;&#x6E;&#x65;&#x6F;&#x74;&#x72;&#105;&#110;&#x69;&#x74;&#x79;&#x2E;&#x61;t" /><input type="hidden" name="item_name" value="neotrinity.at" /><input type="hidden" name="no_shipping" value="2" /><input type="hidden" name="no_note" value="1" /><input type="hidden" name="currency_code" value="USD" /><input type="hidden" name="tax" value="0" /><input type="hidden" name="bn" value="PP-DonationsBF" /><input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" style="border:0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" /><img alt="if you like to, you can support me" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" /></form><br /><br />

         <a name="<?php echo($fieldsPre); ?>Drag_and_Drop"></a><h2>Drag and Drop Layout</h2>

    <?php
    /*
    show stored timezones
    */
    ?>

     <?php echo($listTaken); ?>

     <?php
	$timezones_newentry="timezones_newentry_";
	$timezones_newentry_label="label";

	$newentryAbbrFields=array("abbr_standard", "abbr_daylightsaving");
	$newentryAbbrFieldsLength=array(10,10);
	$newentryAbbrFieldsMaxLength=array(15,15);

	$newentryNameFields=array("name_standard", "name_daylightsaving");
	$newentryNameFieldsLength=array(30,30);
	$newentryNameFieldsMaxLength=array(50,50);

	/*
	append dragable add/edit panel
	*/

     ?>

     <div id="timezones_DragandDrop">

	<input type="hidden" value="" id="<?php echo($timezones_newentry); ?>idtochange" name="<?php echo($timezones_newentry); ?>_idtochange" />

    <table class="form-table" style="margin-bottom:0">

     <tr>
        <td><label for="<?php echo($timezones_newentry); ?>timezone_id">timezone_id</label></td>
	  <td><select name="<?php echo($timezones_newentry); ?>timezone_id" id="<?php echo($timezones_newentry); ?>timezone_id" disabled="disabled">
	<option value="loading Options..."></option>
</select></td>
     </tr>

     <tr>
        <td><label for="<?php echo($timezones_newentry); ?>use_db_abbreviations">use_db_abbreviations</label></td>
	  <td><input onkeyup="if(event.keyCode==13) timezones_appendEntry();" type="checkbox" onclick="timezones_toggleDBFields(this, timezones_abbr_fields);" checked="checked" name="<?php echo($timezones_newentry); ?>use_db_abbreviations" id="<?php echo($timezones_newentry); ?>use_db_abbreviations" /></td>
     </tr>

        <?php for ($i = 0; $i < sizeof($newentryAbbrFields); $i++) {
        	echo("<tr><td><label for=\"".$timezones_newentry.$newentryAbbrFields[$i]."\">".$newentryAbbrFields[$i]."</label></td>");
        	echo("<td><input disabled=\"disabled\" onkeyup=\"if(event.keyCode==13) timezones_appendEntry();\" name=\"".$timezones_newentry.$newentryAbbrFields[$i]."\" id=\"".$timezones_newentry.$newentryAbbrFields[$i]."\" type=\"text\" size=\"".$newentryAbbrFieldsLength[$i]."\" maxlength=\"".$newentryAbbrFieldsMaxLength[$i]."\" /></td></tr>");
	}
	?>

     <tr>
        <td><label for="<?php echo($timezones_newentry); ?>use_db_names">use_db_names</label></td>
	  <td><input onkeyup="if(event.keyCode==13) timezones_appendEntry();" type="checkbox" onclick="timezones_toggleDBFields(this, timezones_name_fields);" checked="checked" name="<?php echo($timezones_newentry); ?>use_db_names" id="<?php echo($timezones_newentry); ?>use_db_names" /></td>
     </tr>

        <tr><td colspan="2"><input type="button" id="timezones_loadDefaultNames" value="Get Details for selected TimeZone" /></td></tr>

        <?php for ($i = 0; $i < sizeof($newentryNameFields); $i++) {
        	echo("<tr><td><label for=\"".$timezones_newentry.$newentryNameFields[$i]."\">".$newentryNameFields[$i]."</label></td>");
        	echo("<td><input disabled=\"disabled\" onkeyup=\"if(event.keyCode==13) timezones_appendEntry();\" name=\"".$timezones_newentry.$newentryNameFields[$i]."\" id=\"".$timezones_newentry.$newentryNameFields[$i]."\" type=\"text\" size=\"".$newentryNameFieldsLength[$i]."\" maxlength=\"".$newentryNameFieldsMaxLength[$i]."\" /></td></tr>");
	}
	?>

     <tr style="display:none" id="<?php echo($timezones_newentry); ?>SuccessLabel"><td colspan="2" style="font-weight:bold">Successfully adopted!</td>
     </tr>

        <tr>
		<td colspan="2"><input type="button" id="timezones_create" value="Insert" />
		<input type="button" id="timezones_new" value="New" /></td>
	  </tr>

	</table>

     </div>

     <br style="clear:both" />

    <?php
    /*
    provide garbage bin
    */
     ?>

     <?php echo($listAvailable); ?>

     <div id="timezones_Search">

    <table class="form-table" style="margin-bottom:0">

        <tr>
		<td><label for="timezones_search_timeanddate_query">Search for timezones</label></td>
		<td><input type="text" value="" id="timezones_search_timeanddate_query" name="timezones_search_timeanddate_query" size="35" maxlength="40" onkeyup="if(event.keyCode==13) timezones_search_timeanddate_openWindow();"/></td>
        </tr>

        <tr>
      	<td colspan="2"><input type="button" id="timezones_search_timeanddate" name="timezones_search_timeanddate" value="Search" onclick="timezones_search_timeanddate_openWindow();"/></td>
        </tr>

	</table>

     </div>

     <br style="clear:both" /><br />

<?php TimeZoneCalculatorOptionPageActionButtons(2); ?>

       <form action="options-general.php?page=<?php echo(plugin_basename(__FILE__)); ?>" method="post">

          <a name="<?php echo($fieldsPre); ?>Content"></a><h2><?php timezonecalculator_open_close_section($fieldsPre.'Content', $sections['Content']); ?>Content</h2>

	<div id="<?php echo($fieldsPre); ?>Content<?php echo($sectionPost); ?>" <?php if ($sections['Content']==='0') { ?>style="display:none"<?php } ?>>

        This static customizing section forms the mirror of the <?php echo(timezonecalculator_get_section_link('Drag_and_Drop', $allSections, 'Drag and Drop Layout Section')); ?>. Changes to positions which you make here are only reflected in the <?php echo(timezonecalculator_get_section_link('Drag_and_Drop', $allSections, 'Drag and Drop Layout Section')); ?> after pressing <strong>Update options</strong>.<br/><br/>

   		<h3>Syntax</h3>
		<ul>
			<li>timezone_id;</li>
			<li>abbr_standard;</li>
			<li>abbr_daylightsaving;</li>
			<li>name_standard;</li>
			<li>name_daylightsaving;</li>
			<li>use_db_abbreviations;<ul>
			  	<li>0 ... use user-input as abbreviations</li>
				<li>1 ... use abbreviations from php database</li>
			</ul></li>
			<li>use_db_names<ul>
			  	<li>0 ... use user-input as names</li>
				<li>1 ... use names from php database (currently the timezone_id)</li>
			</ul></li>
		</ul>

		<h3>Infos</h3>
		<ul>
			<li><a target="_blank" href="http://php.net/manual/en/timezones.php">php.net</a></li>
			<li><a target="_blank" href="http://www.timeanddate.com/library/abbreviations/timezones/">timeanddate.com</a></li>
			<li><a target="_blank" href="http://en.wikipedia.org/wiki/Timezones">wikipedia.org</a></li>
		</ul>

		<h3>Examples</h3>
		<ul>
	    		<li>Asia/Bangkok;;;;;1;1</li>
	    		<li>America/New_York;EST;EWT;New York, NY, US;New York, NY, US;0;0</li>
	    		<li>Europe/Vienna;;;sleep longer in winter;get up earlier to enjoy the sun;1;0</li>
	    	</ul>

<a name="<?php echo($fieldsPre); ?>TimeZones"></a>
    <table class="form-table">

     		<tr><td><label for="TimeZones">TimeZones</label></td>
          	<td><textarea name="TimeZones" id="TimeZones" cols="90" rows="5"><?php echo(htmlspecialchars(get_option('TimeZones'))); ?></textarea></td>
		</tr>

	</table>

	<?php TimeZoneCalculatorOptionPageActionButtons(3); ?>

	</div><br /><br />


          <a name="<?php echo($fieldsPre); ?>CSS_Tags"></a><h2><?php timezonecalculator_open_close_section($fieldsPre.'CSS_Tags', $sections['CSS_Tags']); ?>CSS-Tags</h2>

	<div id="<?php echo($fieldsPre); ?>CSS_Tags<?php echo($sectionPost); ?>" <?php if ($sections['CSS_Tags']==='0') { ?>style="display:none"<?php } ?>>

In this section you can customize the layout of <?php echo(timezonecalculator_get_section_link("Preview", $allSections, "TimeZoneCalculator's output")); ?> after saving your changes by clicking on <strong>Update options</strong>. The structure of the available fields is as follows:<br /><br />

[before_List]<br />
&nbsp;&nbsp;&nbsp;&nbsp;[before_Tag]<strong>[TIMEZONE 1]</strong>[after_Tag]<br />
&nbsp;&nbsp;&nbsp;&nbsp;...<br />
&nbsp;&nbsp;&nbsp;&nbsp;[before_Tag]<strong>[TIMEZONE n]</strong>[after_Tag]<br />
[after_List]<br /><br />

    <table class="form-table">

		<?php

     		foreach ($csstags as $csstag) {
          		echo("<tr>");
            	echo("<td><label for=\"".$fieldsPre.$csstag."\">");
            	echo($csstag);
            	echo("</label></td>");
              	echo("<td><input type=\"text\" size=\"30\" maxlength=\"50\" name=\"".$fieldsPre.$csstag."\" id=\"".$fieldsPre.$csstag."\" value=\"".htmlspecialchars(get_option($fieldsPre.$csstag), ENT_QUOTES)."\" /></td>");
       	   	echo("</tr>");
	      } ?>

	</table><br /><br />

	  You can customize the Time_Format by using standard PHP syntax. default: yyyy-mm-dd hh:mm which in PHP looks like Y-m-d H:i<br/><br/>
        For details please refer to the WordPress <a target="_blank" href="http://codex.wordpress.org/Formatting_Date_and_Time">
	  Documentation on date and time formatting</a>.<br /><br />

 Moreover you can add style attributes for the container <code>div</code>-element by modifying both the class <code>timezonecalculator-output</code> for fixed timestamps and the class <code>timezonecalculator-refreshable-output</code> for current and therefore updateable outputs in your <a href="themes.php">Theme</a>, e.g. with the WordPress <a href="theme-editor.php">Theme-Editor</a>.<br /><br />

<strong>Syntax</strong><br /><br />
<code>.timezonecalculator-output { yourstyle }</code><br />
<code>.timezonecalculator-refreshable-output { yourstyle }</code>

 	  <?php TimeZoneCalculatorOptionPageActionButtons(4); ?>
	  </div>
        <br/><br/>

          <a name="<?php echo($fieldsPre); ?>Administrative_Options"></a><h2><?php timezonecalculator_open_close_section($fieldsPre.'Administrative_Options', $sections['Administrative_Options']); ?>Administrative Options</h2>

	<div id="<?php echo($fieldsPre); ?>Administrative_Options<?php echo($sectionPost); ?>" <?php if ($sections['Administrative_Options']==='0') { ?>style="display:none"<?php } ?>>

In this section you can enable and customize the Ajax-Refresh of TimeZoneCalculator. After activating Use_Ajax_Refresh you can specify the seconds for the update interval.<br /><br />

As all timezone-information is retrieved from the server on every refresh, a Refresh_Time of one second is mostly not realizable for the average server out there. Moreover, please remember that every update causes bandwith usage for your readers and your host.
    <table class="form-table">
     <tr>
        <td><label for ="<?php echo($fieldsPre.$Use_Ajax_Refresh); ?>"><?php echo($Use_Ajax_Refresh.'') ?></label></td>
            <td><input type="checkbox" onclick="timezones_toggleAjaxRefreshFields(this, '<?php echo($Refresh_Time); ?>');" name="<?php echo($fieldsPre.$Use_Ajax_Refresh); ?>" id="<?php echo($fieldsPre.$Use_Ajax_Refresh); ?>" <?php if(get_option($fieldsPre.$Use_Ajax_Refresh)==1) echo('checked="checked"'); ?> /></td>
      </tr>

     <tr>
        <td><label for="<?php echo($fieldsPre.$Refresh_Time); ?>"><?php echo($Refresh_Time.' (in seconds)') ?></label></td>
            <td><input type="text" onblur="timezones_checkNumeric(this,1,3600,'','','',true);" size="8" maxlength="8" name="<?php echo($fieldsPre.$Refresh_Time); ?>" <?php if(get_option($fieldsPre.$Use_Ajax_Refresh)!=1) echo('disabled="disabled"'); ?> id="<?php echo($fieldsPre.$Refresh_Time); ?>" value="<?php echo get_option($fieldsPre.$Refresh_Time); ?>" /></td>
      </tr>
    </table>

<?php TimeZoneCalculatorOptionPageActionButtons(5); ?>

</div><br /><br />

          <a name="<?php echo($fieldsPre); ?>Calculation"></a><h2><?php timezonecalculator_open_close_section($fieldsPre.'Calculation', $sections['Calculation']); ?>Calculation</h2>

	<div id="<?php echo($fieldsPre); ?>Calculation<?php echo($sectionPost); ?>" <?php if ($sections['Calculation']==='0') { ?>style="display:none"<?php } ?>>

In this section you can specify a certain timestamp, which will be displayed in the timezones you have chosen in the <?php echo(timezonecalculator_get_section_link('Drag_and_Drop', $allSections, 'Drag and Drop Layout Section')); ?> or in the <?php echo(timezonecalculator_get_section_link('Content', $allSections, 'Content Section')); ?>.<br /><br />

Please note, that your selected time will only be visible in your Admin Menu and will not influence any time(zone)-settings on your server or in your php configuration! This section is meant to be your personal calculator (e.g. for checking flight schedules or finding online friends in other timezones) and will not change the timezone information on your blog.<br /><br />

Chosing your date can be done by either picking it on the calender or entering it in <a target="_blank" href="http://www.w3.org/QA/Tips/iso-date">ISO format (yyyy-mm-dd)</a> as well as <a target="_blank" href="http://en.wikipedia.org/wiki/Calendar_date#mm.2Fdd.2Fyy_or_mm.2Fdd.2Fyyyy_.28month.2C_day.2C_year.29">American date format (mm/dd/yyyy)</a>.

    <table class="form-table">

<?php
	$timezones_calculation="timezones_calculation_";
	$timezones_calculation_pickdate=$timezones_calculation."pickdate_";
?>

	<tr><td><label for="<?php echo($timezones_calculation_pickdate.'Date'); ?>">Date</label></td>
	<td><input type="text" name="<?php echo($timezones_calculation_pickdate.'Date'); ?>" id="<?php echo($timezones_calculation_pickdate.'Date'); ?>" size="20" maxlength="20" /> at 

	<script type="text/javascript" language="javascript">

	/* <![CDATA[ */

	/* DatePicker v5.3 by frequency-decoder.com
	http://www.frequency-decoder.com/2009/09/09/unobtrusive-date-picker-widget-v5
	*/

	/*
	set date-formats for datepicker to
	+ yyyy-mm-dd (added by format)
	+ mm/dd/yyyy
	*/

	var opts = {
		formElements:{"<?php echo($timezones_calculation_pickdate.'Date'); ?>":"Y-ds-m-ds-d"},
		highlightDays:[0,0,0,0,0,1,1],
		fillGrid:true,
		rangeLow:"19300101",
		rangeHigh:"20371231",
		constrainSelection:false,
		dateFormats:{"<?php echo($timezones_calculation_pickdate.'Date'); ?>":["m-sl-d-sl-Y"]}
	};

	datePickerController.createDatePicker(opts);

	/* ]]> */

	</script>

<?php 

/*
make hour select
*/

echo('<select name="'.$timezones_calculation_pickdate.'Hour" id="'.$timezones_calculation_pickdate.'Hour">');

for ($i=0;$i<24;$i++) {
	$hour_string=timezonecalculator_lz($i).' (';

	if ($i==0)
		$hour_string.='12 am';
	elseif ($i==12)
		$hour_string.='12 pm';
	elseif ($i<12)
		$hour_string.=timezonecalculator_lz($i).' am';
	else
		$hour_string.=timezonecalculator_lz($i-12).' pm';

	$hour_string.=')';

	echo('<option value="'.timezonecalculator_lz($i).'">'.$hour_string.'</option>');
}

echo('</select> : ');

/*
make minute select
*/

echo('<select name="'.$timezones_calculation_pickdate.'Minute" id="'.$timezones_calculation_pickdate.'Minute">');

for ($i=0;$i<60;$i=$i+5) {
	$minute=timezonecalculator_lz($i);

	echo('<option value="'.$minute.'">'.$minute.'</option>');
}

echo('</select> in ');

?>
	  <select name="<?php echo($timezones_calculation_pickdate); ?>Timezone_temp" id="<?php echo($timezones_calculation_pickdate); ?>Timezone_temp" style="display:none" disabled="disabled">
	<option value="loading Options..."></option>
</select></td>
		</tr>

	</table><br /><br />

As an alternative you can also enter a <a target="_blank" href="http://www.onlineconversion.com/unix_time.htm">unix timestamp</a> or <a target="_blank" href="http://th2.php.net/manual/en/function.strtotime.php">any English textual datetime description</a> (like tomorrow 3 pm or 2009-04-23 16:00).<br /><br />

Moreover, you can specify the original timezone of your selected date and time. After clicking on <strong>set time</strong> you can find the calculated output in the <?php echo(timezonecalculator_get_section_link('Preview', $allSections, 'Preview Section')); ?>.

    <table class="form-table">

     		<tr><td><label for="<?php echo($timezones_calculation); ?>DateTime">Date and Time String</label></td>
          	<td><input type="text" name="<?php echo($timezones_calculation); ?>DateTime" id="<?php echo($timezones_calculation); ?>DateTime" size="30" maxlength="50" />

in

	  <select name="<?php echo($timezones_calculation); ?>Timezone_temp" id="<?php echo($timezones_calculation); ?>Timezone_temp" style="display:none" disabled="disabled">
	<option value="loading Options..."></option>
</select></td>
		</tr>

	</table>

    <div class="submit">
      <input type="submit" name="set_time" id="set_time" value="<?php echo('set time') ?>" />
    </div>

	  </div>
        <br/><br/>

        <a name="<?php echo($fieldsPre); ?>Preview"></a><h2>Preview</h2>

You can publish this output either by adding a <a href="widgets.php">Sidebar Widget</a> or by calling the php function <code>getTimeZonesTime()</code> (optionally with several parameters as described in the <a target="_blank" href="http://wordpress.org/extend/plugins/timezonecalculator/other_notes/">Other Notes</a>) wherever you like.<br /><br />

<?php 

$myTime='';
$myTimeZone='';

/*
if the time has been set in the backend
*/

if (isset($_POST['set_time'])) {
	if (isset($_POST[$timezones_calculation_pickdate.'Date']) && strlen($_POST[$timezones_calculation_pickdate.'Date'])==10 ) {
		$myTime=$_POST[$timezones_calculation_pickdate.'Date'].' '.$_POST[$timezones_calculation_pickdate.'Hour'].':'.$_POST[$timezones_calculation_pickdate.'Minute'];
		$myTimeZone=$_POST[$timezones_calculation_pickdate.'Timezone'];
	}
	elseif (isset($_POST[$timezones_calculation.'DateTime']) && strlen($_POST[$timezones_calculation.'DateTime'])>2 ) {
		$myTime=$_POST[$timezones_calculation.'DateTime'];
		$myTimeZone=$_POST[$timezones_calculation.'Timezone'];
	}
}

if (strlen($myTimeZone)==0)
	getTimeZonesTime($myTime);
else
	getTimeZonesTime($myTime, $myTimeZone);
?>

    <div class="submit">
      <input type="submit" name="info_update" id="info_update" value="<?php echo('Update options') ?>" />
      <input type="submit" name="load_default" id="load_default" value="<?php echo('Load defaults') ?>" />
    </div>

    <?php

	  foreach($sections as $key => $section) {
		echo("<input type=\"hidden\" id=\"".$fieldsPre.$key.$sectionPost."_Show\" name=\"".$fieldsPre.$key.$sectionPost."_Show\" value=\"".$section."\" />");
	  }

    ?>

    </form>
    </div></div>

      <script type="text/javascript" language="javascript">

      /* <![CDATA[ */

	 function timezones_search_timeanddate_openWindow() {
		window.open('http://www.timeanddate.com/search/results.html?query='+document.getElementById('timezones_search_timeanddate_query').value,'timeanddate','width=600,height=400,top=200,left=200,toolbar=yes,location=yes,directories=np,status=yes,menubar=no,scrollbars=yes,copyhistory=no,resizable=yes');
	 }

	 var timezones_abbr_fields = ["abbr_standard", "abbr_daylightsaving"];
	 var timezones_name_fields = ["name_standard", "name_daylightsaving"];

	 /*
	 converts a boolean to 0 (false) or 1 (true)
	 */

	 function timezones_convertBoolean2Int(bol) {
		if (bol==true) return 1;
		else return 0;
	 }

	 /*
	 checks if a field is null or empty
	 */

	 function timezones_IsEmpty(aTextField) {
		if ((aTextField.value.length==0) ||
		(aTextField.value==null)) {
			return true;
   		}
		else {
			return false;
		}
	 }

	/*
	original source from Nannette Thacker
	taken from http://www.shiningstar.net/
	*/
	
	function timezones_checkNumeric(objName,minval,maxval,comma,period,hyphen,message) {
		var numberfield = objName;

		if (timezones_chkNumeric(objName,minval,maxval,comma,period,hyphen,message) == false) {
			objName.value='';
			return false;
		}

		else {
			return true;
		}
	}

	// only allow 0-9 be entered, plus any values passed
	// (can be in any order, and don't have to be comma, period, or hyphen)
	// if all numbers allow commas, periods, hyphens or whatever,
	// just hard code it here and take out the passed parameters

	function timezones_chkNumeric(objName,minval,maxval,comma,period,hyphen,message) {

		var checkOK = "0123456789" + comma + period + hyphen;
		var checkStr = objName;
		var allValid = true;
		var decPoints = 0;
		var allNum = "";

		for (i = 0;  i < checkStr.value.length;  i++) {
			ch = checkStr.value.charAt(i);

			for (j = 0;  j < checkOK.length;  j++)
			if (ch == checkOK.charAt(j))
			break;

			if (j == checkOK.length) {
				allValid = false;
				break;
			}

			if (ch != ",")
				allNum += ch;
		}

		if (!allValid) {	
			if (message==true) {
				alertsay = "Please enter only these values \""
				alertsay = alertsay + checkOK + "\" in the \"" + checkStr.name + "\" field."
				alert(alertsay);
			}

			return (false);
		}

		// set the minimum and maximum
		var chkVal = allNum;
		var prsVal = parseInt(allNum);

		if (minval != "" && maxval != "") if (!(prsVal >= minval && prsVal <= maxval)) {
			if (message==true) {
				alertsay = "Please enter a value greater than or "
				alertsay = alertsay + "equal to \"" + minval + "\" and less than or "
				alertsay = alertsay + "equal to \"" + maxval + "\" in the \"" + checkStr.name + "\" field."
				alert(alertsay);
			}
			return (false);
		}
	}

	/*
	selects a certain option-value in a html select
	*/

	function timezones_SelectValue(aSelect, aValue) {
		for (var i=0; i<aSelect.length; i++) {
			if (aSelect[i].value == aValue) {
				aSelect[i].selected = true;
			}
		}
	}

	/*
	moves an element in a drag and drop list one position up
	modified by Nikk Folts, http://www.nikkfolts.com/
	*/

	function timezones_moveElementUpforList(list, row) {
		return timezones_moveRow(list, row, 1);
	}

	/*
	moves an element in a drag and drop list one position down
	modified by Nikk Folts, http://www.nikkfolts.com/
	*/

	function timezones_moveElementDownforList(list, row) {
		return timezones_moveRow(list, row, -1);
	}

	/*
	moves an element in a drag and drop list one position
	modified by Nikk Folts, http://www.nikkfolts.com/
	*/

	function timezones_moveRow(list, row, dir) {
		var sequence=Sortable.sequence(list);
		var found=false;

		//move only, if there is more than one element in the list
		if (sequence.length>1) for (var j=0; j<sequence.length; j++) {

			//element found
			if (sequence[j]==row) {
				found=true;

				var i = j - dir;
				if (i >= 0 && i <= sequence.length) {
					var temp=sequence[i];
					sequence[i]=row;
					sequence[j]=temp;
					break;
				}
			}
		}

		Sortable.setSequence(list, sequence);
		return found;
	}

	/*
	handles moving up for both lists
	*/

	function timezones_moveElementUp(key) {
		if (timezones_moveElementUpforList('tz_listTaken', key)==false)
			timezones_moveElementUpforList('tz_listAvailable', key);

		timezones_updateDragandDropLists();
	}

	/*
	handles moving down for both lists
	*/

	function timezones_moveElementDown(key) {
		if (timezones_moveElementDownforList('tz_listTaken', key)==false)
			timezones_moveElementDownforList('tz_listAvailable', key);

		timezones_updateDragandDropLists();
	}

    /*
    create drag and drop lists
    */

    Sortable.create("tz_listTaken", {
	dropOnEmpty:true,
	containment:["tz_listTaken","tz_listAvailable"],
	constraint:false,
	onUpdate:function(){ timezones_updateDragandDropLists(); }
	});

    Sortable.create("tz_listAvailable", {
	dropOnEmpty:true,
	containment:["tz_listTaken","tz_listAvailable"],
	constraint:false
	});

      /*
      drag and drop lists update function
      updates timezones textbox
      */

	function timezones_updateDragandDropLists() {

	/*
	get current timezones order
	*/

	var sequence=Sortable.sequence('tz_listTaken');
	if (sequence.length>0) {
		var list = escape(sequence);
		var sorted_ids = unescape(list).split(',');
	}

	else {
		var sorted_ids = [-1];
	}

	/*
	set new entries
	*/

	document.getElementById('TimeZones').value='';

	for (var i = 0; i < sorted_ids.length; i++) {
		if (sorted_ids[i]!=-1) {
			var timeZoneFromElement=(document.getElementById('Tags_'+sorted_ids[i]).childNodes[2].nodeValue).split('\n');
			var oldValue=document.getElementById('TimeZones').value;
			document.getElementById('TimeZones').value=oldValue+timeZoneFromElement[0]+';'+(document.getElementById('Tags_'+sorted_ids[i]).childNodes[3].value)+"\n";
		}

	}

	/*
	dynamically set new list heights
	*/

      var elementHeight=32;

	var listTakenLength=sorted_ids.length*elementHeight;
	if (listTakenLength<=0) listTakenLength=elementHeight;
	document.getElementById('tz_listTaken').style.height = (listTakenLength)+'px';

	list = escape(Sortable.sequence('tz_listAvailable'));
	sorted_ids = unescape(list).split(',');

	listTakenLength=sorted_ids.length*elementHeight;
	if (listTakenLength<=0) listTakenLength=elementHeight;
	document.getElementById('tz_listAvailable').style.height = (listTakenLength)+'px';

	}

	/*
	enables/disables the associated fields of a checkbox input
	*/

	function timezones_toggleAjaxRefreshFields(element, field) {
		var timezones_newentry="timezones_";
		var isChecked=element.checked;

		if (isChecked) {
			document.getElementById(timezones_newentry+field).value='30';
			document.getElementById(timezones_newentry+field).disabled=null;
		}
		else {
			document.getElementById(timezones_newentry+field).value='';
			document.getElementById(timezones_newentry+field).disabled='disabled';
		}
	}

	/*
	enables/disables the associated fields of a checkbox input
	*/

	function timezones_toggleDBFields(element, fields) {
		var timezones_newentry="timezones_newentry_";
		var isChecked=element.checked;

		for (var i = 0; i < fields.length; i++) {
			if (isChecked) {
				document.getElementById(timezones_newentry+fields[i]).value='';
				document.getElementById(timezones_newentry+fields[i]).disabled='disabled';
			}
			else {
				document.getElementById(timezones_newentry+fields[i]).disabled=null;
			}

		}
	}

	/*
	load selected field in edit panel
	populate timezone attributes
	*/

	function timezones_adoptDragandDropEdit (key) {
		var timezones_newentry="timezones_newentry_";

		document.getElementById(timezones_newentry+'SuccessLabel').style.display='none';
		document.getElementById(timezones_newentry+'idtochange').value=key;
		document.getElementById('timezones_create').value='Edit';

		var timeZoneFromElement=document.getElementById('Tags_'+key).childNodes[2].nodeValue+';'+document.getElementById('Tags_'+key).childNodes[3].value;
		var timeZoneFromElementAttributes=timeZoneFromElement.split(';');

		/*
		set values of edit fields
		*/

		document.getElementById(timezones_newentry+'timezone_id').selectedIndex=0;

		timezones_SelectValue(document.getElementById(timezones_newentry+'timezone_id'),timeZoneFromElementAttributes[0]);

		if (timeZoneFromElementAttributes[5]==0) {
			document.getElementById(timezones_newentry+'use_db_abbreviations').checked='';
			document.getElementById(timezones_newentry+timezones_abbr_fields[0]).value=timeZoneFromElementAttributes[1];
			document.getElementById(timezones_newentry+timezones_abbr_fields[1]).value=timeZoneFromElementAttributes[2];
		}
		else {
			document.getElementById(timezones_newentry+'use_db_abbreviations').checked='checked';
		}

		timezones_toggleDBFields(document.getElementById(timezones_newentry+'use_db_abbreviations'), timezones_abbr_fields);

		if (timeZoneFromElementAttributes[6]==0) {
			document.getElementById(timezones_newentry+'use_db_names').checked='';
			document.getElementById(timezones_newentry+timezones_name_fields[0]).value=timeZoneFromElementAttributes[3];
			document.getElementById(timezones_newentry+timezones_name_fields[1]).value=timeZoneFromElementAttributes[4];
		}
		else {
			document.getElementById(timezones_newentry+'use_db_names').checked='checked';
		}

		timezones_toggleDBFields(document.getElementById(timezones_newentry+'use_db_names'), timezones_name_fields);

		document.getElementById(timezones_newentry+'timezone_id').focus();
	}

	 /*
	 append created entry into textarea or
	 apply changes of currently selected timezone
	 */

	 function timezones_appendEntry() {

		var timezones_newentry="timezones_newentry_";
		var idtochange=document.getElementById(timezones_newentry+'idtochange').value;

		document.getElementById(timezones_newentry+'SuccessLabel').style.display='none';

		var errormsg="";
		//check for ; in fields as we don't wont to break the timezones-syntax
		//check for " in new entry fields as we don't wont to break the html-syntax
		for (var i=0; i<timezones_abbr_fields.length; i++) {
			if (document.getElementById(timezones_newentry+timezones_abbr_fields[i]).value.indexOf(';')>-1)
				errormsg+="\n - Semicolons are not allowed in Field "+timezones_abbr_fields[i];
			if ((idtochange.length==0) && (document.getElementById(timezones_newentry+timezones_abbr_fields[i]).value.indexOf('"')>-1))
				errormsg+="\n - Double Quotes are not allowed in Field "+timezones_abbr_fields[i]+" for new entries - Please add without them and edit afterwards";
		}

		for (var i=0; i<timezones_name_fields.length; i++) {
			if ( document.getElementById(timezones_newentry+timezones_name_fields[i]).value.indexOf(';')>-1)
				errormsg+="\n - Semicolons are not allowed in Field "+timezones_name_fields[i];
			if ((idtochange.length==0) && (document.getElementById(timezones_newentry+timezones_name_fields[i]).value.indexOf('"')>-1))
				errormsg+="\n - Double Quotes are not allowed in Field "+timezones_name_fields[i]+" for new entries - Please add without them and edit afterwards";
		}

		//check for empty std abbreviation field
		if (timezones_IsEmpty(document.getElementById(timezones_newentry+'abbr_standard')) &&
		    !timezones_IsEmpty(document.getElementById(timezones_newentry+'name_standard')) &&
		    !(document.getElementById(timezones_newentry+'use_db_abbreviations').checked) ) {
			errormsg+="\n - name_standard will not be displayed, because abbr_standard is empty";
		}

		//check for empty dst abbreviation field
		if (timezones_IsEmpty(document.getElementById(timezones_newentry+'abbr_daylightsaving')) &&
		    !timezones_IsEmpty(document.getElementById(timezones_newentry+'name_daylightsaving')) &&
		    !(document.getElementById(timezones_newentry+'use_db_abbreviations').checked)) {
			errormsg+="\n - name_daylightsaving will not be displayed, because abbr_daylightsaving is empty";
		}

		if (errormsg.length==0) {		

			var timezones_newentry_timezone_id=document.getElementById(timezones_newentry+'timezone_id').options[ document.getElementById(timezones_newentry+'timezone_id').selectedIndex ].value;
			var timezones_newentry_abbr_standard= document.getElementById(timezones_newentry+'abbr_standard').value;
			var timezones_newentry_name_standard= document.getElementById(timezones_newentry+'name_standard').value;
			var timezones_newentry_abbr_daylightsaving= document.getElementById(timezones_newentry+'abbr_daylightsaving').value;
			var timezones_newentry_name_daylightsaving= document.getElementById(timezones_newentry+'name_daylightsaving').value;
			var timezones_newentry_use_db_abbreviations= timezones_convertBoolean2Int(document.getElementById(timezones_newentry+'use_db_abbreviations').checked);
			var timezones_newentry_use_db_names= timezones_convertBoolean2Int(document.getElementById(timezones_newentry+'use_db_names').checked);

			var ret=timezones_newentry_abbr_standard+";"+
				timezones_newentry_abbr_daylightsaving+";"+
				timezones_newentry_name_standard+";"+
				timezones_newentry_name_daylightsaving+";"+
				timezones_newentry_use_db_abbreviations+";"+
				timezones_newentry_use_db_names;

			/*
			change timezone attributes
			*/

			if (idtochange.length>0) {
				document.getElementById('Tags_'+idtochange).childNodes[2].nodeValue=timezones_newentry_timezone_id;
				document.getElementById('Tags_'+idtochange).childNodes[3].value=ret;
				timezones_updateDragandDropLists();
				new Effect.Highlight(document.getElementById('Tags_'+idtochange),{startcolor:'#30df8b'});
			}

			/*
			insert new timezone
			*/

			else {
				var nextTagID=0;

				/*
				if timezones are available, get max tag id of both lists
				*/

				if (Sortable.sequence('tz_listTaken').length>0 || Sortable.sequence('tz_listAvailable').length>0) {
					var listTaken = escape(Sortable.sequence('tz_listTaken'));
					var listTaken_sorted_ids = unescape(listTaken).split(',');

					var listAvailable = escape(Sortable.sequence('tz_listAvailable'));
					var listAvailable_sorted_ids = unescape(listAvailable).split(',');

					var lastTagID=0;

					/*
					get max tag id from listTaken
					*/

					for (var j = 0; j < listTaken_sorted_ids.length; j++) {
						if (listTaken_sorted_ids[j].length>0)
							lastTagID=Math.max(lastTagID, parseInt(listTaken_sorted_ids[j]));
					}

					/*
					get max tag id from listAvailable
					*/

					for (var j = 0; j < listAvailable_sorted_ids.length; j++) {
						if (listAvailable_sorted_ids[j].length>0)
							lastTagID=Math.max(lastTagID, parseInt(listAvailable_sorted_ids[j]));
					}

					nextTagID=parseInt(lastTagID)+1;
				}

				/*
				insert new timezone into drag and drop list
				*/

				var plugin_url = '<?php echo(TIMEZONECALCULATOR_PLUGINURL); ?>';
		            var upArrow='<img class="timezones_arrowbutton" src="'+plugin_url+'arrow_up_blue.png" onclick="timezones_moveElementUp('+nextTagID+');" alt="move element up" />';
		            var downArrow='<img class="timezones_arrowbutton" style="margin-right:20px;" src="'+plugin_url+'arrow_down_blue.png" onclick="timezones_moveElementDown('+nextTagID+');" alt="move element down" />';

				var options='<input type="hidden" value="'+ret+'" />';

				var newElement='<li class="timezones_sortablelist" id="Tags_'+nextTagID+'">'+upArrow+downArrow+timezones_newentry_timezone_id+options+'</li>';
				new Insertion.Bottom('tz_listTaken',newElement);

				Event.observe('Tags_'+nextTagID, 'click', function(e){ timezones_adoptDragandDropEdit(nextTagID) });

				/*
				reinitialize drag and drop lists
				*/

			      Sortable.create("tz_listTaken", {
					dropOnEmpty:true,
					containment:["tz_listTaken","tz_listAvailable"],
					constraint:false,
					onUpdate:function(){ timezones_updateDragandDropLists(); }
				});

			      Sortable.create("tz_listAvailable", {
					dropOnEmpty:true,
					containment:["tz_listTaken","tz_listAvailable"],
					constraint:false
				});

				timezones_updateDragandDropLists();
				new Effect.Highlight(document.getElementById('Tags_'+nextTagID),{startcolor:'#30df8b'});

			}

			new Effect.Highlight(document.getElementById('timezones_DragandDrop'),{startcolor:'#30df8b'});
			new Effect.Appear(document.getElementById(timezones_newentry+'SuccessLabel'));
		}

		else {
			new Effect.Highlight(document.getElementById('timezones_DragandDrop'),{startcolor:'#FF0000'});
			alert('The following error(s) occured:'+errormsg);
		}

	 }

	 /*
	 reset new entry form
	 */

	 function timezones_resetNewEntryForm() {
		var timezones_newentry="timezones_newentry_";
		document.getElementById(timezones_newentry+'timezone_id').selectedIndex=0;
		document.getElementById(timezones_newentry+'idtochange').value='';
		document.getElementById('timezones_create').value='Insert';

		document.getElementById(timezones_newentry+'SuccessLabel').style.display='none';

		document.getElementById(timezones_newentry+'use_db_abbreviations').checked='checked';

		timezones_toggleDBFields(document.getElementById(timezones_newentry+'use_db_abbreviations'), timezones_abbr_fields);

		document.getElementById(timezones_newentry+'use_db_names').checked='checked';

		timezones_toggleDBFields(document.getElementById(timezones_newentry+'use_db_names'), timezones_name_fields);
	 }

	 /*
	 populate default timezone names
	 */

	 function timezones_loadDefaultNames() {
		var timezones_newentry="timezones_newentry_";

		document.getElementById(timezones_newentry+'use_db_names').checked='';

		timezones_toggleDBFields(document.getElementById(timezones_newentry+'use_db_names'), timezones_name_fields);

		document.getElementById(timezones_newentry+'SuccessLabel').style.display='none';

		TimeZoneName=document.getElementById(timezones_newentry+'timezone_id').options[ document.getElementById(timezones_newentry+'timezone_id').selectedIndex ].value.replace(/_/g, ' ');

		for (var i = 0; i<timezones_name_fields.length; i++) {
		document.getElementById(timezones_newentry+timezones_name_fields[i]).value=TimeZoneName;
		}

	 }

	/*
	assures, that the section is opened, if clicked
	*/

	function timezonecalculator_assure_open_section(section) {
		if ($('<?php echo($fieldsPre."'+section+'".$sectionPost); ?>_Show').value=='0')
			timezonecalculator_toggleSectionDiv($('<?php echo($fieldsPre."'+section+'".$sectionPost); ?>_Button'), '<?php echo($fieldsPre."'+section+'"); ?>');
	}

	/*
	toggles a section (div and img)
	*/

	function timezonecalculator_toggleSectionDiv(src_element, div_id) {
		timezonecalculator_toggle_div_and_image(src_element, div_id+'<?php echo($sectionPost) ?>', 'blind', '<?php echo(TIMEZONECALCULATOR_PLUGINURL) ?>arrow_right_blue.png', '<?php echo(TIMEZONECALCULATOR_PLUGINURL) ?>arrow_down_blue.png');
	}

	/*
	toggles a div together with an image
	inspired by pnomolos
	http://godbit.com/forum/viewtopic.php?id=1111
	*/

	function timezonecalculator_toggle_div_and_image(src_element, div_id, effect, first_img, second_img){
		Effect.toggle(div_id, effect, {afterFinish:function(){

			if (src_element.src.match(first_img)) {
				src_element.src = second_img;
				src_element.alt = 'hide section';
				document.getElementById(div_id+'_Show').value =  '1';
			}

			else {
				src_element.src = first_img;
				src_element.alt = 'show section';
				document.getElementById(div_id+'_Show').value =  '0';
			}

		}});

		return true;
	}

	 new Draggable('timezones_listTaken');
	 new Draggable('timezones_listAvailable');
	 new Draggable('timezones_DragandDrop');
	 new Draggable('timezones_Search');

       Event.observe('timezones_create', 'click', function(e){ timezones_appendEntry(); });
       Event.observe('timezones_new', 'click', function(e){ timezones_resetNewEntryForm(); });
       Event.observe('timezones_loadDefaultNames', 'click', function(e){ timezones_loadDefaultNames(); });

	 for (var i=1;i<6;i++) {
	       Event.observe('info_update_click'+i, 'click', function(e){ document.getElementById('info_update').click(); });
       	 Event.observe('load_default_click'+i, 'click', function(e){ document.getElementById('load_default').click(); });
		 new Effect.Appear(document.getElementById('timezones_actionbuttons_'+i), {duration:0, from:1, to:1});
	 }

       <?php echo($listTakenListeners); ?>

       /* ]]> */

       </script>

	 <?php }

add_action('init', 'timezonecalculator_init');
add_action('widgets_init', 'widget_timezonecalculator_init');

add_action('wp_head', 'timezonecalculator_ajax_refresh');
add_action('wp_head', 'timezonecalculator_wp_head');

add_action('admin_footer', 'timezonecalculator_ajax_refresh');
add_action('admin_head', 'timezonecalculator_wp_head');
add_action('admin_menu', 'addTimeZoneCalculatorOptionPage');

add_action('wp_dashboard_setup', 'timezonecalculator_add_dashboard_widget' );

add_action('admin_head-index.php', 'timezones_add_dashboard_widget_css');

add_filter('plugin_action_links', 'timezonecalculator_adminmenu_plugin_actions', 10, 2);

register_uninstall_hook( __FILE__, 'timezonecalculator_uninstall' );

/*
widget class
*/

class WP_Widget_TimeZoneCalculator extends WP_Widget {

	/*
	constructor
	*/

	function WP_Widget_TimeZoneCalculator() {
		$widget_ops = array('classname' => 'widget_timezonecalculator', 'description' => 'Calculates, displays and automatically updates times and dates in different timezones with respect to daylight saving on basis of UTC.');
		$this->WP_Widget('timezonecalculator', 'TimeZoneCalculator', $widget_ops);
	}

	/*
	produces the widget-output
	*/

	function widget($args, $instance) {
		extract($args);

		$title = empty($instance['title']) ? '&nbsp;' : apply_filters('widget_title', $instance['title']);

		echo $before_widget;
		echo $before_title . $title . $after_title;
		getTimeZonesTime();
	    	echo $after_widget;
	}

	/*
	the backend-form with widget-title and settings-link
	*/

	function form($instance) {
		$title = attribute_escape($instance['title']);
		?>

		<p><label for="<?php echo $this->get_field_id('title'); ?>">
		<?php _e('Title:'); ?>

		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

		<p><a href="options-general.php?page=<?php echo(plugin_basename(__FILE__)); ?>"><?php _e('Settings') ?></a></p>

		<?php
	}

	/*
	saves an updated title
	*/

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

}

/*
database cleanup on uninstall
*/

function timezonecalculator_uninstall() {
	delete_option('widget_timezonecalculator');

	$fieldsPre="timezones_";
	$sectionPost="_Section";

	$csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "Time_Format");

	$sections=array('Instructions' => '1', 'Content' => '1', 'CSS_Tags' => '1', 'Administrative_Options' => '1', 'Calculation' => '1');

	foreach ($csstags as $csstag) {
		delete_option($fieldsPre.$csstag);
	}

	delete_option("TimeZones");

	delete_option($fieldsPre.'Use_Ajax_Refresh');
	delete_option($fieldsPre.'Refresh_Time');

	foreach ($sections as $key => $section) {
		delete_option($fieldsPre.$key.$sectionPost);
	}
}

?>