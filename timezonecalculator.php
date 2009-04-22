<?php

/*
Plugin Name: TimeZoneCalculator
Plugin URI: http://www.neotrinity.at/projects/
Description: Calculates times and dates in different timezones with respect to daylight saving on basis of UTC.
Author: Bernhard Riedl
Version: 0.90
Author URI: http://www.neotrinity.at
*/

/*  Copyright 2005-2009  Bernhard Riedl  (email : neo@neotrinity.at)

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
	DEFINE ('TIMEZONECALCULATOR_PLUGINURL', get_settings('siteurl'). '/'. str_replace( ABSPATH, '', dirname(__FILE__) . '/'));

	if (!isset($_GET['time']))
		DEFINE ('TIMEZONECALCULATOR_CURRENTGMDATE', gmdate('U'));
	else
		DEFINE ('TIMEZONECALCULATOR_CURRENTGMDATE', $_GET['time']);

	add_action('wp_head', 'timezonecalculator_wp_head');
	add_action('admin_menu', 'addTimeZoneCalculatorOptionPage');
	add_filter('plugin_action_links', 'timezonecalculator_adminmenu_plugin_actions', 10, 2);
}

function timezonecalculator_adminmenu_plugin_actions($links, $file) {
	if ($file == 'timezonecalculator/timezonecalculator.php')
		$links[] = "<a href='options-general.php?page=timezonecalculator/timezonecalculator.php'>Settings</a>";
	return $links;
}

/*
loads the necessary java-scripts,
which are all included in wordpress >= 2.1
for the admin-page
*/

function timezones_admin_print_scripts() {
	global $wp_version;
	if (version_compare($wp_version, "2.1", ">="))
		wp_enqueue_script('scriptaculous-dragdrop');
		wp_enqueue_script('scriptaculous-effects');
}

/*
loads the necessary css-styles
for the admin-page
*/

function timezones_admin_head() {
	global $wp_version;

	$current_wp_admin_css_colors=array();

	/*
	check if wordpress_admin_themes are available
	*/

	if (version_compare($wp_version, "2.5", ">=")) {
		global $_wp_admin_css_colors;

		$current_color = get_user_option('admin_color');
		if ( empty($current_color) )
			$current_color = 'fresh';

		$current_wp_admin_css_colors=$_wp_admin_css_colors[$current_color]->colors;

	}

	/*
	if themes are not available, use default colors
	*/

	if (sizeof($current_wp_admin_css_colors)<4) {
		$current_wp_admin_css_colors=array("#14568a", "#14568a", "", "#c3def1");
	}

?>
     <style type="text/css">

	.timezones_wrap ul {
		list-style-type : disc;
		padding: 5px 5px 5px 30px;
	}

<?php
	if (version_compare($wp_version, "2.1", ">=")) {
?>

      li.timezones_sortablelist {
		background-color: <?php echo $current_wp_admin_css_colors[1]; ?>;
		color: <?php echo $current_wp_admin_css_colors[3]; ?>;
		cursor : move;
		padding: 3px 5px 3px 5px;
      }

      ul.timezones_sortablelist {
		float: left;
		border: 1px <?php echo $current_wp_admin_css_colors[0]; ?> solid;
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

	#timezones_DragandDrop_Edit_Label {
		background-color: <?php echo $current_wp_admin_css_colors[1]; ?>;
		color: <?php echo $current_wp_admin_css_colors[3]; ?>;
	}

	#timezones_DragandDrop_Edit_Message {
		color: <?php echo $current_wp_admin_css_colors[0]; ?>;
	}

	img.timezones_arrowbutton {
		vertical-align: bottom;
		cursor: pointer;
		margin-left: 5px;
	}

	img.timezones_sectionbutton {
		cursor: pointer;
	}

<?php } ?>
      </style>

<?php }

/*
called from widget_init hook
*/

function widget_timezonecalculator_init() {
	register_sidebar_widget(array('TimeZoneCalculator', 'widgets'), 'widget_timezonecalculator');
	register_widget_control(array('TimeZoneCalculator', 'widgets'), 'widget_timezonecalculator_control', 300, 100);
}

/*
adds metainformation - please leave this for stats!
*/

function timezonecalculator_wp_head() {
  echo("<meta name=\"TimeZoneCalculator\" content=\"0.90\" />\n");
}

/*
widget functions
*/

function widget_timezonecalculator($args) {
	extract($args);

	$options = get_option('widget_timezonecalculator');
	$title = $options['title'];

	echo $before_widget;
	echo $before_title . htmlentities($title) . $after_title;
	getTimeZonesTime();
    	echo $after_widget;
}

/*
widget control
*/

function widget_timezonecalculator_control() {

	// Get our options and see if we're handling a form submission.
	$options = get_option('widget_timezonecalculator');
	if ( !is_array($options) )
		$options = array('title'=>'', 'buttontext'=>__('TimeZoneCalculator', 'widgets'));
		if ( $_POST['timezonecalculator-submit'] ) {
			$options['title'] = strip_tags(stripslashes($_POST['timezonecalculator-title']));
			update_option('widget_timezonecalculator', $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		
		echo '<p style="text-align:right;"><label for="timezonecalculator-title">' . __('Title:') . ' <input style="width: 200px;" id="timezonecalculator-title" name="timezonecalculator-title" type="text" value="'.$title.'" /></label></p>';
		echo '<input type="hidden" id="timezonecalculator-submit" name="timezonecalculator-submit" value="1" />';
		echo '<p style="text-align:left;"><label for="timezonecalculator-options">Find the options <a href="options-general.php?page=timezonecalculator/timezonecalculator.php">here</a>!</label></p>';
	}

/*
this methods echos all timezone entries
*/

function getTimeZonesTime() {
	$fieldsPre="timezones_";

	$before_list=stripslashes(get_option($fieldsPre.'before_List'));
	$after_list=stripslashes(get_option($fieldsPre.'after_List'));

	//necessary library is not installed
	if (!timezonecalculator_checkTimeZoneFunction()) {
		echo($before_list);
		timezonecalculator_getErrorMessage('Please make sure that you have a recent version of php including the php timezones library installed!');
		echo($after_list);

		return;
	}

	$timeFormat=stripslashes(get_option($fieldsPre.'Time_Format'));
	if (strlen($timeFormat)<1)
		$timeFormat="Y-m-d H:i";

	$timeZonesTimeOption=get_option('TimeZones');

	$errors=array('wrong number of parameters',
		'timezone-id wrong',
		'wrong parameter used for database abbreviations',
		'wrong parameter used for database names',
		'old timezone-format');

	//at minimum one entry
	if ($timeZonesTimeOption) {
		$counter=0;

		echo($before_list);

		$timeZonesTime=explode("\n", $timeZonesTimeOption);

		foreach ($timeZonesTime as $timeZoneTimeOption) {

			//is there anything to parse in the particular line?
			if (strlen($timeZoneTimeOption)>1) {
				$counter++;

				$timeZoneTime=explode(";", $timeZoneTimeOption);

				$dateTimeZone=timezonecalculator_checkData($timeZoneTime);

				//data-check failed
				if ($dateTimeZone<0) {				
					timezonecalculator_getErrorMessage("Sorry, could not read timezones-entry ".$counter.": ".$errors[abs($dateTimeZone)-1]);
				}

				//data-check ok
				else {
					echo (getTimeZoneTime($dateTimeZone, 					array($timeZoneTime[1],$timeZoneTime[2]),
					array($timeZoneTime[3],$timeZoneTime[4]),
					trim($timeZoneTime[5]),trim($timeZoneTime[6]),
					$timeFormat)."\n");
				}
			}
		}

		echo($after_list);

	}
}

/*
checks if the data matches the defined criteria
*/

function timezonecalculator_checkData($timeZoneTime) {
	//first check if the size of the timezones-array match
	if (sizeof($timeZoneTime)!=7)
		return -1;

	//WordPress 2.8 TimeZones Support
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

function getTimeZoneTime($dateTimeZone, $abbrs, $names, $use_db_abbr, $use_db_name, $timeFormat) {
	$fieldsPre="timezones_";
	$before_tag=stripslashes(get_option($fieldsPre.'before_Tag'));
	$after_tag=stripslashes(get_option($fieldsPre.'after_Tag'));

	$dateTime=new DateTime(gmdate("Y-m-d H:i:s", TIMEZONECALCULATOR_CURRENTGMDATE), $dateTimeZone);

	if ($dateTime) {
		$offset=$dateTime->getOffset();
		$daylightsavingArr=timezonecalculator_isDST($dateTimeZone);
		$daylightsaving=$daylightsavingArr[0];

		//first optional the abbreviation
		$abbr=$daylightsavingArr[1];

		if ($use_db_abbr==0)
			$abbr=$abbrs[$daylightsaving];

		if (strlen($abbr)>0) {
			//and also optional a mouse-over tooltiptext
			$name=str_replace('_', ' ', $dateTimeZone->getName());
			if ($use_db_name==0)
				$name=$names[$daylightsaving];

			if (strlen($name)>0)
				$ret="<abbr title=\"".$name."\">".$abbr."</abbr>";
			else
				$ret=$abbr;

			$ret.=": ";
		}

		//display only the time
		else {
			$ret="";
		}
	}

	$ret.=gmdate($timeFormat,(TIMEZONECALCULATOR_CURRENTGMDATE + $offset));

	return $before_tag.$ret.$after_tag;
}

/*
checks if timezone is within DST
returns boolean isdst and current abbreviation in array
*/

function timezonecalculator_isDST($timezone) {
	$isDst=0;
	$abbr=$timezone->getName();

	//lookup array until current transition has been found
	foreach (timezone_transitions_get($timezone) as $tr) {
		if ($tr['ts'] > TIMEZONECALCULATOR_CURRENTGMDATE)
			break;

		if((bool)$tr['isdst']===true)
			$isDst=1;
		else
			$isDst=0;

		$abbr=$tr['abbr'];
	}

	return array($isDst, $abbr);
}

/*
display errormessage
*/

function timezonecalculator_getErrorMessage($msg) {
	$fieldsPre="timezones_";
	$before_tag=stripslashes(get_option($fieldsPre.'before_Tag'));
	$after_tag=stripslashes(get_option($fieldsPre.'after_Tag'));
	echo($before_tag.$msg.$after_tag);
}

/*
checks for necessary php timezone-functions
*/

function timezonecalculator_checkTimeZoneFunction() {
	if (function_exists('timezone_open') &&
		function_exists('timezone_transitions_get') &&
		function_exists('timezone_name_get') &&
		function_exists('timezone_offset_get') &&
		function_exists('timezone_identifiers_list') &&
		function_exists('date_create') )

		return true;
	else
		return false;
}

/*
add TimeZoneCalculator to WordPress Option Page
*/

function addTimeZoneCalculatorOptionPage() {
    if (function_exists('add_options_page')) {
	if (timezonecalculator_checkTimeZoneFunction()) {
        $page=add_options_page('TimeZoneCalculator', 'TimeZoneCalculator', 8, __FILE__, 'createTimeZoneCalculatorOptionPage');
        add_action('admin_print_scripts-'.$page, 'timezones_admin_print_scripts');
        add_action('admin_head-'.$page, 'timezones_admin_head');
	}
	else {
        $page=add_options_page('TimeZoneCalculator', 'TimeZoneCalculator', 8, __FILE__, 'createTimeZoneCalculatorFailurePage');
	}
    }
}

/*
produce toggle button for showing and hiding sections
*/

function timezonecalculator_open_close_section($section, $default) {

	if ($default==='1') {
		$defaultImage='down';
		$defaultAlt='hide';
	}
	else {
		$defaultImage='right';
		$defaultAlt='show';
	}

	echo("<img class=\"timezones_sectionbutton\" onclick=\"timezonecalculator_toggle_div_and_image(this, '".$section."', 'blind', '".TIMEZONECALCULATOR_PLUGINURL."arrow_right_blue.png', '".TIMEZONECALCULATOR_PLUGINURL."arrow_down_blue.png');\" alt=\"".$defaultAlt." Section\" src=\"".TIMEZONECALCULATOR_PLUGINURL."arrow_".$defaultImage."_blue.png\" />&nbsp;");

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
	$structure .= ">UTC</option>\n";

	/*in addition add a local WordPress timezone option if the corresponding setting exists */

	$wordpress_timezone=get_option('timezone_string');
	if(strlen($wordpress_timezone)>0 && @timezone_open($wordpress_timezone)) {
		$structure .= "<option value=\"Local_WordPress_Time\">Local Wordpress Time</option>\n";
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
				$structure .= "</optgroup>\n";
			}

			$structure .= '<optgroup label="'.$continent.'">' . "\n";
		}

		//if a city name exists, add entry to list
		if ( !empty($city) ) {
			if ( !empty($subcity) ) {
				$city = $city . '/'. $subcity;
			}
			$structure .= "\t<option".((($continent.'/'.$city)==$selectedzone)?' selected="selected"':'')." value=\"".($continent.'/'.$city)."\">&nbsp;&nbsp;&nbsp;".str_replace('_',' ',$city)."</option>\n";
		}
	}

	return $structure.= "</optgroup>\n";
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

<?php phpinfo(); ?>

	</div>
<?php }

/*
Output JS
*/

function TimeZoneCalculatorOptionPageActionButtons($num) {
	global $wp_version;

	if (version_compare($wp_version, "2.1", ">=")) { ?>
	    <div id="timezones_actionbuttons_<?php echo($num); ?>" class="submit" style="display:none">
      	<input type="button" id="info_update_click<?php echo($num); ?>" name="info_update_click<?php echo($num); ?>" value="<?php _e('Update options') ?>" />
	      <input type="button" id="load_default_click<?php echo($num); ?>" name="load_default_click<?php echo($num); ?>" value="<?php _e('Load defaults') ?>" />
	    </div>
<?php }

}

/*
displays the Option Page in the Admin Menu
*/

function createTimeZoneCalculatorOptionPage() {

    $fieldsPre="timezones_";
    $csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "Time_Format");
    $csstags_defaults=array("<ul>", "</ul>", "<li>", "</li>", "Y-m-d H:i");

    $sections=array('Instructions_Section' => '1', 'Content_Section' => '1', 'CSS_Tags_Section' => '1');

    /*
    configuration changed => store parameters
    */

    if (isset($_POST['info_update'])) {

        foreach ($csstags as $csstag) {
            update_option($fieldsPre.$csstag, $_POST[$fieldsPre.$csstag]);
        }

        update_option('TimeZones', $_POST['TimeZones']);

        foreach ($sections as $key => $section) {
            update_option($fieldsPre.$key, $_POST[$fieldsPre.$key.'_Show']);
        }

        ?><div class="updated"><p><strong>
        <?php _e('Configuration changed!')?></strong></p></div>
      <?php }

      elseif (isset($_POST['load_default'])) {

        for ($i = 0; $i < sizeof($csstags); $i++) {
            update_option($fieldsPre.$csstags[$i], $csstags_defaults[$i]);
        }

        update_option('TimeZones', 'UTC;;;;;1;1');

        foreach ($sections as $key => $section) {
            update_option($fieldsPre.$key, $section);
        }

        ?><div class="updated"><p><strong>
        <?php _e('Defaults loaded!')?></strong></p></div>

      <?php }

	/* manual cleanup */

      elseif (isset($_GET['cleanup'])) {

	  timezonecalculator_uninstall();

        ?><div class="updated"><p><strong>
        <?php _e('Settings deleted!')?></strong></p></div>

      <?php }

	global $wp_version;
	if (version_compare($wp_version, "2.1", ">=")) {

	foreach($sections as $key => $section) {
		if (get_option($fieldsPre.$key)!="") $sections[$key] = get_option($fieldsPre.$key);
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

		echo($before_list);

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
						$otherOptions.=trim(implode(';',array_slice($timeZoneTime, 1)));
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

	}

	/*
	options form
	*/

	?>

     <div class="wrap"><div class="timezones_wrap">

<br/><br/>Welcome to the Settings-Page of <a target="_blank" href="http://www.neotrinity.at/projects/">TimeZoneCalculator</a>. This plugin calculates times and dates in different timezones with respect to daylight saving on basis of <abbr title="Coordinated Universal Time">UTC</abbr>.

<?php if (version_compare($wp_version, "2.1", ">=")) { ?><h2><?php timezonecalculator_open_close_section($fieldsPre.'Instructions_Section', $sections['Instructions_Section']); ?>Instructions</h2>

	<div id="<?php echo($fieldsPre); ?>Instructions_Section" <?php if ($sections['Instructions_Section']==='0') { ?>style="display:none"<?php } ?>>

     <ul>
        <li>It may be a good start for TimeZoneCalculator first-timers to click on <strong>Load defaults</strong>.</li>
        <li>You can insert new timezones by filling out the form on the right in the <a href="#<?php echo($fieldsPre); ?>Drag_and_Drop">Drag and Drop Layout Section</a> and clicking <strong>Insert</strong>. All parameters of TimeZoneCalculator can also be changed in the <a href="#<?php echo($fieldsPre); ?>Content">Content Section</a> without the usage of Javascript. Anyway, new entries are only saved after clicking on <strong>Update options</strong>.<br />

Hint: Information about cities and their timezones can be searched below.</li>
	  <li>To customize existing timezones click on the entry you want to change in any list and edit the parameters in the form on the right. After clicking <strong>Edit</strong> the selected timezone's parameters are adopted in its list. The timezones can be re-orderd within a list either by drag and drop or by clicking on the arrows on the particular timezone's left hand side. Don't forget to save all your adjustments by clicking on <strong>Update options</strong>.</li>
        <li>To remove timezones from the list just drag and drop them onto the Garbage Bin and click on <strong>Update options</strong>.</li>
        <li>Style-customizations can be made in the <a href="#<?php echo($fieldsPre); ?>CSS_Tags">CSS-Tags Section</a>. (Defaults are automatically populated via the <strong>Load defaults</strong> button)</li>
        <li>Before you publish the results you can use the <a href="#<?php echo($fieldsPre); ?>Preview">Preview Section</a>.</li>
        <li>Finally, you can publish the previously selected and saved timezones either by adding a <a href="widgets.php">Sidebar Widget</a> or by calling the php function <code>getTimeZonesTime()</code> wherever you like.</li>
    <?php
	if (!function_exists('register_uninstall_hook')) { ?>
        <li>If you decide to uninstall TimeZoneCalculator firstly remove the optionally added <a href="widgets.php">Sidebar Widget</a> or the integrated php function call(s) and secondly  <a href="?page=timezonecalculator/timezonecalculator.php&amp;cleanup=true" onclick="javascript:return confirm('Are you sure you want to delete all your settings?')">click here</a> to clean up the database, then disable it in the <a href="plugins.php">Plugins Tab</a> and delete the <code>timezonecalculator</code> directory in your WordPress Plugins directory (usually wp-content/plugins) on your webserver.</li>
    <?php }
	else { ?>
        <li>If you decide to uninstall TimeZoneCalculator firstly remove the optionally added <a href="widgets.php">Sidebar Widget</a> or the integrated php function call(s) and secondly disable and delete it in the <a href="plugins.php">Plugins Tab</a>.</li>
    <?php } ?>
</ul>

<?php TimeZoneCalculatorOptionPageActionButtons(1); ?>

</div><?php } ?>


<h2>Support</h2>
        If you like to support the development of this plugin, donations are welcome. <?php echo(convert_smilies(':)')); ?> Maybe you also want to <a href="link-add.php">add a link</a> to <a href="http://www.neotrinity.at/projects/">http://www.neotrinity.at/projects/</a>.<br /><br />

        <form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_xclick" /><input type="hidden" name="business" value="&#110;&#101;&#111;&#64;&#x6E;&#x65;&#x6F;&#x74;&#x72;&#105;&#110;&#x69;&#x74;&#x79;&#x2E;&#x61;t" /><input type="hidden" name="item_name" value="neotrinity.at" /><input type="hidden" name="no_shipping" value="2" /><input type="hidden" name="no_note" value="1" /><input type="hidden" name="currency_code" value="USD" /><input type="hidden" name="tax" value="0" /><input type="hidden" name="bn" value="PP-DonationsBF" /><input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" style="border:0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" /><img alt="if you like to, you can support me" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" /></form><br /><br />

    <?php if (version_compare($wp_version, "2.1", ">=")) { ?>
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
	  <td><select onkeyup="if(event.keyCode==13) timezones_appendEntry();" name="<?php echo($timezones_newentry); ?>timezone_id" id="<?php echo($timezones_newentry); ?>timezone_id" style="size:1">
	<?php echo(timezonecalculator_makeTimeZonesSelect()); ?>
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

<?php } ?>

       <form action="options-general.php?page=timezonecalculator/timezonecalculator.php" method="post">

          <a name="<?php echo($fieldsPre); ?>Content"></a><h2><?php if (version_compare($wp_version, "2.1", ">=")) { timezonecalculator_open_close_section($fieldsPre.'Content_Section', $sections['Content_Section']); } ?>Content</h2>

	<div id="<?php echo($fieldsPre); ?>Content_Section" <?php if (($sections['Content_Section']==='0') && (version_compare($wp_version, "2.1", ">=")) ) { ?>style="display:none"<?php } ?>>

   		In this section you can edit your TimeZones. - Please stick to the syntax stated below.

    <?php
	if (version_compare($wp_version, "2.1", ">=")) { ?>
        This static customizing section forms the mirror of the <a href="#<?php echo($fieldsPre) ?>Drag_and_Drop">Drag and Drop Layout Section</a>. Changes to positions which you make here are only reflected in the <a href="#<?php echo($fieldsPre) ?>Drag_and_Drop">Drag and Drop Layout Section</a> after pressing <strong>Update options</strong>.
	<?php } ?><br/><br/>

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
          	<td><textarea name="TimeZones" id="TimeZones" cols="90" rows="5"><?php echo(get_option('TimeZones')); ?></textarea></td>
		</tr>

	</table>

	<?php TimeZoneCalculatorOptionPageActionButtons(3); ?>

	</div><br /><br />


          <a name="<?php echo($fieldsPre); ?>CSS_Tags"></a><h2><?php if (version_compare($wp_version, "2.1", ">=")) { timezonecalculator_open_close_section($fieldsPre.'CSS_Tags_Section', $sections['CSS_Tags_Section']); } ?>CSS-Tags</h2>

	<div id="<?php echo($fieldsPre); ?>CSS_Tags_Section" <?php if (($sections['CSS_Tags_Section']==='0') && (version_compare($wp_version, "2.1", ">=")) ) { ?>style="display:none"<?php } ?>>

In this section you can customize the layout of <a href="#<?php echo($fieldsPre); ?>Preview">TimeZoneCalculator's output</a> after saving your changes by clicking on <strong>Update options</strong>. The structure of the available fields is as follows:<br /><br />

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
            	_e($csstag);
            	echo("</label></td>");
              	echo("<td><input type=\"text\" size=\"30\" maxlength=\"50\" name=\"".$fieldsPre.$csstag."\" id=\"".$fieldsPre.$csstag."\" value=\"".htmlspecialchars(stripslashes(get_option($fieldsPre.$csstag)))."\" /></td>");
       	   	echo("</tr>");
	      } ?>

	</table><br /><br />

	  You can customize the Time_Format by using standard PHP syntax. default: yyyy-mm-dd hh:mm which in PHP looks like Y-m-d H:i<br/><br/>
        For details please refer to the WordPress <a target="_blank" href="http://codex.wordpress.org/Formatting_Date_and_Time">
	  Documentation on date and time formatting</a>.

 	  <?php TimeZoneCalculatorOptionPageActionButtons(4); ?>
	  </div>
        <br/><br/>

        <a name="<?php echo($fieldsPre); ?>Preview"></a><h2>Preview</h2>

You can publish this output either by adding a <a href="widgets.php">Sidebar Widget</a> or by calling the php function <code>getTimeZonesTime()</code> wherever you like.<br /><br />
		<?php getTimeZonesTime(); ?>

    <div class="submit">
      <input type="submit" name="info_update" id="info_update" value="<?php _e('Update options') ?>" />
      <input type="submit" name="load_default" id="load_default" value="<?php _e('Load defaults') ?>" />
    </div>

    <?php

	if (version_compare($wp_version, "2.1", ">="))
	  foreach($sections as $key => $section) {
		echo("<input type=\"hidden\" id=\"".$fieldsPre.$key."_Show\" name=\"".$fieldsPre.$key."_Show\" value=\"".$section."\" />");
	  }

    ?>

    </form>
    </div></div>

    <?php
	if (version_compare($wp_version, "2.1", ">=")) { ?>

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

		document.getElementById(timezones_newentry+'SuccessLabel').style.display='none';

		var errormsg="";
		//check for ; in fields as we don't wont to break the timezones-syntax
		for (var i=0; i<timezones_abbr_fields.length; i++) {
			if (document.getElementById(timezones_newentry+timezones_abbr_fields[i]).value.indexOf(';')>-1)
				errormsg+="\n - Semilokons are not allowed in Field "+timezones_abbr_fields[i];
		}

		for (var i=0; i<timezones_name_fields.length; i++) {
			if ( document.getElementById(timezones_newentry+timezones_name_fields[i]).value.indexOf(';')>-1)
				errormsg+="\n - Semilokons are not allowed in Field "+timezones_name_fields[i];
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

			var idtochange=document.getElementById(timezones_newentry+'idtochange').value;

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

	 for (var i=1;i<5;i++) {
	       Event.observe('info_update_click'+i, 'click', function(e){ document.getElementById('info_update').click(); });
       	 Event.observe('load_default_click'+i, 'click', function(e){ document.getElementById('load_default').click(); });
		 new Effect.Appear(document.getElementById('timezones_actionbuttons_'+i), {duration:0, from:1, to:1});
	 }

       <?php echo($listTakenListeners); ?>

       /* ]]> */

       </script>

	 <?php }

}

add_action('init', 'timezonecalculator_init');
add_action('widgets_init', 'widget_timezonecalculator_init');

if ( function_exists('register_uninstall_hook') )	register_uninstall_hook( __FILE__, 'timezonecalculator_uninstall' );

/*
database cleanup on uninstall
*/

function timezonecalculator_uninstall() {
	delete_option('widget_timezonecalculator');

	$fieldsPre="timezones_";
	$csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "Time_Format");

    $sections=array('Instructions_Section' => '1', 'Content_Section' => '0', 'CSS_Tags_Section' => '0');

	foreach ($csstags as $csstag) {
		delete_option($fieldsPre.$csstag);
	}

	delete_option("TimeZones");

	foreach ($sections as $key => $section) {
		delete_option($fieldsPre.$key);
	}
}

?>