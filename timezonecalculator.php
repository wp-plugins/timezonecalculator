<?php

/*
Plugin Name: TimeZoneCalculator
Plugin URI: http://www.neotrinity.at/projects/
Description: Calculates different times and dates in timezones with respect to daylight saving on basis of utc. - Find the options <a href="options-general.php?page=timezonecalculator/timezonecalculator.php">here</a>!
Author: Bernhard Riedl
Version: 0.50
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


/*
**********************************************
stop editing here unless you know what you do!
**********************************************
/*

/*
called from init hook
*/

function timezonecalculator_init() {
	add_action('wp_head', 'timezonecalculator_wp_head');
	add_action('admin_menu', 'addTimeZoneCalculatorOptionPage');
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
	if (version_compare($wp_version, "2.1", ">=")) {
?>

     <style type="text/css">

      li.timezones_sortablelist {
  		background-color : #14568a;
		color: #c3def1;
		cursor : move;
		padding: 3px 5px 3px 5px;
      }

      ul.timezones_sortablelist {
		border: 1px dotted;
		list-style-image : none;
		list-style-type : none;
		margin: 10px 20px 20px 30px;
		padding: 10px;
      }

      #timezones_DragandDrop{
		float: right;
		cursor : move;
		border: 1px dotted;
		margin: 35px 20px 0px 0px;
		width: 400px;
		padding: 5px;
      }

      #timezones_new, #timezones_create, #timezones_loadExample {
		margin: 10px 5px 10px 0px;
		background: url( images/fade-butt.png );
		border: 3px double #999;
		border-left-color: #ccc;
		border-top-color: #ccc;
		color: #333;
		padding: 0.25em;
		width: 124px;
		text-align: center;
		float:left;
      }

	#timezones_new:active, #timezones_create:active, #timezones_loadExample:active {
		background: #f4f4f4;
		border: 3px double #ccc;
		border-left-color: #999;
		border-top-color: #999;
	}

	img.timezones_arrowbutton {
		vertical-align: bottom;
		cursor: pointer;
		margin-left: 5px;
	}

	img.timezones_arrowbutton:hover {
		border-bottom: 1px dotted #ffffff;
		border-top: 1px dotted #ffffff;
	}

      </style>

<?php
	}

}

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
  echo("<meta name=\"TimeZoneCalculator\" content=\"0.50\" />\n");
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

	$timeFormat=stripslashes(get_option($fieldsPre.'Time_Format'));
	if (strlen($timeFormat)<1)
		$timeFormat="Y-m-d H:i";

	$timeZonesTimeOption=get_option('TimeZones');

	//at minimum one correct entry
	if ($timeZonesTimeOption) {
		$counter=0;

		echo($before_list);

		$timeZonesTime=explode("\n", $timeZonesTimeOption);

		foreach ($timeZonesTime as $timeZoneTimeOption) {

			$timeZoneTime=explode(";", $timeZoneTimeOption);

			$counter++;

			//data-check ok
			if (timezonecalculator_checkData($timeZoneTime)) {
				echo (getTimeZoneTime(array($timeZoneTime[0],$timeZoneTime[2]),
							    array($timeZoneTime[1],$timeZoneTime[3]),
							    $timeZoneTime[4],
							    $timeZoneTime[5],$timeZoneTime[6],$timeFormat)."\n");
			}

			else {
				timezonecalculator_getErrorMessage("Could not read line ".$counter."! - Offset, hemisphere or us timezone parameters are not correct. See the examples for hints.");
			}
		}

		echo($after_list);

	}
}

/*
checks if the data matches the defined criteria
*/

function timezonecalculator_checkData($timeZoneTime) {

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

function getTimeZoneTime($abbrs, $names, $offset, $hemisphere, $ustimezone, $timeFormat) {
	$fieldsPre="timezones_";
	$before_tag=stripslashes(get_option($fieldsPre.'before_Tag'));
	$after_tag=stripslashes(get_option($fieldsPre.'after_Tag'));

	$nowStdDST=timezonecalculator_isStdDST();
	$nowUSDST=timezonecalculator_isUSDST();

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

	//first the name (optional)
	$abbr=$abbrs[$daylightsaving];
	if (strlen($abbr)>0) {

		//and additionally a mouse-over tooltiptext
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

	$ret.=gmdate($timeFormat,(time() + 3600 * ($offset + $daylightsaving)));

	return $before_tag.$ret.$after_tag;
}

/*
checks if gmt is within European DST
European DST (since 1996) last Sunday in March to last Sunday in October
created by Matthew Waygood (www.waygoodstuff.co.uk)
modified by Bernhard Riedl (www.neotrinity.at)
*/

function timezonecalculator_isStdDST() {
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

function timezonecalculator_isUSDST() {
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

function timezonecalculator_getErrorMessage($msg) {
	$fieldsPre="timezones_";
	$before_tag=stripslashes(get_option($fieldsPre.'before_Tag'));
	$after_tag=stripslashes(get_option($fieldsPre.'after_Tag'));
	echo($before_tag."Sorry! ".$msg.$after_tag);
}

/*
add TimeZoneCalculator to WordPress Option Page
*/

function addTimeZoneCalculatorOptionPage() {
    if (function_exists('add_options_page')) {
        $page=add_options_page('TimeZoneCalculator', 'TimeZoneCalculator', 8, __FILE__, 'createTimeZoneCalculatorOptionPage');
        add_action('admin_print_scripts-'.$page, 'timezones_admin_print_scripts');
        add_action('admin_head-'.$page, 'timezones_admin_head');
    }
}

/*
Option Page
*/

function createTimeZoneCalculatorOptionPage() {

    $fieldsPre="timezones_";
    $csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "Time_Format");
    $csstags_defaults=array("<ul>", "</ul>", "<li>", "</li>", "Y-m-d H:i");

    /*
    configuration changed => store parameters
    */

    if (isset($_POST['info_update'])) {

        foreach ($csstags as $csstag) {
            update_option($fieldsPre.$csstag, $_POST[$fieldsPre.$csstag]);
        }

        update_option('TimeZones', $_POST['TimeZones']);

        ?><div class="updated"><p><strong>
        <?php _e('Configuration changed!')?></strong></p></div>
      <?php }

      elseif (isset($_POST['load_default'])) {

        for ($i = 0; $i < sizeof($csstags); $i++) {
            update_option($fieldsPre.$csstags[$i], $csstags_defaults[$i]);
        }

        update_option('TimeZones', 'UTC;Coordinated Universal Time;UTC;Coordinated Universal Time;0;-1;0');

        ?><div class="updated"><p><strong>
        <?php _e('Defaults loaded!')?></strong></p></div>

      <?php }

	global $wp_version;
	if (version_compare($wp_version, "2.1", ">=")) {

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

		$plugin_url = get_settings('siteurl'). '/wp-content/plugins/timezonecalculator/';

		foreach ($timeZonesTime as $timeZoneTimeOption) {

			$tag=$timeZoneTimeOption;
	            $upArrow='<img class="timezones_arrowbutton" src="'.$plugin_url.'arrow_up_blue.png" onclick="timezones_moveElementUp('.$counter.');" alt="move element up" />';
	            $downArrow='<img class="timezones_arrowbutton" style="margin-right:20px;" src="'.$plugin_url.'arrow_down_blue.png" onclick="timezones_moveElementDown('.$counter.');" alt="move element down" />';
			$listTakenListeners.="Event.observe('".$beforeKey.$counter."', 'click', function(e){ timezones_adoptDragandDropEdit('".$counter."') });";
			$listTaken.= $before_tag. "\"".$beforeKey.$counter."\">".$upArrow.$downArrow.$tag.$after_tag. "\n";
			$counter++;
		}
	}

	/*
	format list
	*/

	$elementHeight=52;

	$sizeListTaken=$counter*$elementHeight;
	if ($counter<=0) $sizeListTaken=$elementHeight;
	$sizeListAvailable=$elementHeight;

	$listTaken="<ul class=\"timezones_sortablelist\" id=\"listTaken\" style=\"height:".$sizeListTaken."px;width:350px;\">".$listTaken."</ul>";
	$listAvailable="<ul class=\"timezones_sortablelist\" id=\"listAvailable\" style=\"height:".$sizeListAvailable."px;width:350px;\"></ul>";

	}

	/*
	options form
	*/

	?>

     <div class="wrap">

    <?php
	if (version_compare($wp_version, "2.1", ">=")) { ?>

    <div class="submit">
      <input type="button" id="info_update_click" name="info_update_click" value="<?php _e('Update options') ?>" />
      <input type="button" id="load_default_click" name="load_default_click" value="<?php _e('Load defaults') ?>" />
    </div>

         <a name="<?php echo($fieldsPre); ?>Drag_and_Drop"></a><h2>Drag and Drop Layout</h2>

     <fieldset>
        <legend>It maybe a good start for TimeZoneCalculator first-timers to click on <em>Load defaults</em>.</legend>
        <legend>You can customize the descriptions by clicking on the desired timezone in each list.</legend>
        <legend>Don't forget to click <em>Insert</em> or <em>Edit</em> after adopting and <em>Update options</em> after you're finished.</legend>
        <legend>Without filling out the <a href="#<?php echo($fieldsPre); ?>CSS_Tags">CSS-Tags</a>, your users might be disappointed... ;) (defaults can be loaded via the <em>Load defaults</em> button)</legend>
        <legend>Before you publish the results of the plugin you can use the <a href="#<?php echo($fieldsPre); ?>Preview">Preview Section</a> to get the experience first (after pressing <em>Update options</em>).<br /><br /></legend>
     </fieldset>

    <?php
    /*
    show stored timezones
    */
    ?>

     <div style="float:left">
     <fieldset>
	  <legend>TimeZone Entries</legend>
     </fieldset>
     <?php echo($listTaken); ?>
     </div>

     <?php
	$timezones_newentry="timezones_newentry_";
	$timezones_newentry_label="label";

	$newentryFields=array("abbr_standard", "name_standard", "abbr_daylightsaving", "name_daylightsaving");
	$newentryFieldsLength=array(10,50,10,50);

	/*
	append dragable add/edit panel
	*/

     ?>

     <div id="timezones_DragandDrop" name="timezones_DragandDrop">

	<input type="hidden" value="" id="<?php echo($timezones_newentry); ?>idtochange" name="<?php echo($timezones_newentry); ?>_idtochange" />

     <?php

        for ($i = 0; $i < sizeof($newentryFields); $i++) {
        	echo("<fieldset><legend><label for=\"".$timezones_newentry.$newentryFields[$i]."\">".$newentryFields[$i]."</label></legend>");
        	echo("<input onkeyup=\"if(event.keyCode==13) timezones_appendEntry();\" name=\"".$timezones_newentry.$newentryFields[$i]."\" id=\"".$timezones_newentry.$newentryFields[$i]."\" type=\"text\" size=\"".$newentryFieldsLength[$i]."\" maxlength=\"".$newentryFieldsLength[$i]."\" /></fieldset>");
	}
	?>

      <fieldset><legend><label for="<?php echo($timezones_newentry); ?>offset">offset</label></legend>
      <input onkeyup="if(event.keyCode==13) timezones_appendEntry();" onBlur="timezones_checkNumeric(this,-12.5,12.5,'','.','-',true);" name="<?php echo($timezones_newentry); ?>offset" id="<?php echo($timezones_newentry); ?>offset" type="text" size="5" maxlength="5" /></fieldset>

     <fieldset>
        <legend><label for="<?php echo($timezones_newentry); ?>daylight_saving_for">daylight saving for</label>
	  <select onkeyup="if(event.keyCode==13) timezones_appendEntry();" name="<?php echo($timezones_newentry); ?>daylight_saving_for" id="<?php echo($timezones_newentry); ?>daylight_saving_for" style="size:1">
        <option value="0">northern hemisphere</option>
        <option value="1">southern hemisphere</option>
        <option value="-1">no daylight saving at all</option></legend>
        </select>
     </fieldset>

     <fieldset>
        <legend><label for="<?php echo($timezones_newentry); ?>daylight_saving_for_us_zone">daylight_saving_for_us_zone</label>
        <input onkeyup="if(event.keyCode==13) timezones_appendEntry();" type="checkbox" name="<?php echo($timezones_newentry); ?>daylight_saving_for_us_zone" id="<?php echo($timezones_newentry); ?>daylight_saving_for_us_zone" /></legend>
     </fieldset>

     <fieldset>
	  <legend style="display:none; color:#14568a" id="<?php echo($timezones_newentry); ?>SuccessLabel" name="<?php echo($timezones_newentry); ?>SuccessLabel"><br /><br /><em>Successfully adopted!</em></legend>
     </fieldset>

        <fieldset>
		<input type="button" id="timezones_create" value="Insert" />
		<input type="button" id="timezones_new" value="New" />
        	<input type="button" id="timezones_loadExample" value="Example" />
	  </fieldset>

     </div>

     <br style="clear:both" />

    <?php
    /*
    provide garbage bin
    */
     ?>

     <div style="float:left">
     <fieldset>
        <legend>Garbage Bin</legend>
     </fieldset>
     <?php echo($listAvailable); ?>
     </div>

     <br style="clear:both" />

<?php } ?>

       <form method="post">

            <a name="<?php echo($fieldsPre); ?>Content"></a><h2>Content</h2>

   		<fieldset>
        <legend>In this section you can edit your TimeZones. - Please stick to the syntax stated below.</legend>

    <?php
	global $wp_version;
	if (version_compare($wp_version, "2.1", ">=")) { ?>
        <legend>This is the static customizing section, forming the mirror of the <a href="#<?php echo($fieldsPre) ?>Drag_and_Drop">Drag and Drop Layout</a> section.</legend>
        <legend>Changes to positions which you make here are only reflected in the <a href="#<?php echo($fieldsPre) ?>Drag_and_Drop">dynamic section</a> after pressing <em>Update options</em>.</legend>
	<?php } ?>

        <legend>Without filling out the <a href="#<?php echo($fieldsPre); ?>CSS_Tags">CSS-Tags</a>, your users might be disappointed... ;) (defaults can be loaded via the <em>Load defaults</em> button)</legend>
        <legend>Before you publish the results of the plugin you can use the <a href="#<?php echo($fieldsPre); ?>Preview">Preview Section</a> to get the experience first (after pressing <em>Update options</em>).<br /><br /></legend>
   		</fieldset>

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

     		<a name="<?php echo($fieldsPre); ?>TimeZones"></a><fieldset>
          	<legend><label for="TimeZones">TimeZones</label></legend>
          	<textarea name="TimeZones" id="TimeZones" cols="100" rows="5"><?php echo(get_option('TimeZones')); ?></textarea>
     		</fieldset>

            <a name="<?php echo($fieldsPre); ?>CSS_Tags"></a><h2>CSS-Tags</h2>

		<?php

     		foreach ($csstags as $csstag) {
          		echo("<fieldset>");
            	echo("<legend><label for=\"".$fieldsPre.$csstag."\">");
            	_e($csstag);
            	echo("</label></legend>");
              	echo("<input type=\"text\" size=\"30\" name=\"".$fieldsPre.$csstag."\" id=\"".$fieldsPre.$csstag."\" value=\"".htmlspecialchars(stripslashes(get_option($fieldsPre.$csstag)))."\" />");
       	   	echo("</fieldset>");
	      } ?>

	  <fieldset>
	  <legend>You can optionally customize the Time_Format by using standard PHP syntax.</legend>
	  <legend>default: yyyy-mm-dd hh:mm which in PHP looks like Y-m-d H:i</legend>
        <legend>For details please refer to the WordPress <a href="http://codex.wordpress.org/Formatting_Date_and_Time">
	  Documentation on date and time formatting</a>.</legend>
        </fieldset>

        <a name="<?php echo($fieldsPre); ?>Preview"></a><h2>Preview (call getTimeZonesTime(); wherever you like!)</h2>
		<?php getTimeZonesTime(); ?><br /><br />

    <div class="submit">
      <input type="submit" name="info_update" value="<?php _e('Update options') ?>" />
      <input type="submit" name="load_default" value="<?php _e('Load defaults') ?>" />
    </div>

    </form>
    </div>

    <?php
	global $wp_version;
	if (version_compare($wp_version, "2.1", ">=")) { ?>

       <script type="text/javascript" language="javascript">

	 var fields = ["abbr_standard", "name_standard", "abbr_daylightsaving", "name_daylightsaving", "offset"];

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
	moves an element in a drag and drop one position up
	*/

	function timezones_moveElementUpforList(list, key) {
		var sequence=Sortable.sequence(list);
		var newsequence=[];
		var reordered=false;

		//move only, if there is more than one element in the list
		if (sequence.length>1) for (var j = 0; j < sequence.length; j++) {

			//move, if not already first element, the element is not null
			if (j>0 && sequence[j].length>0 && sequence[j]==key) {
				var temp=newsequence[j-1];
				newsequence[j-1]=key;
				newsequence[j]=temp;
				reordered=true;
			}
			
			//until element not found, just copy array
			else {
				newsequence[j]=sequence[j];
			}
		}

		if (reordered) Sortable.setSequence (list,newsequence);
		return reordered;
	}

	/*
	handles moving up for both lists
	*/

	function timezones_moveElementUp(key) {
		if (timezones_moveElementUpforList('listTaken', key)==false)
			timezones_moveElementUpforList('listAvailable', key);

		timezones_updateDragandDropLists();
	}

	/*
	moves an element in a drag and drop one position down
	*/

	function timezones_moveElementDownforList(list, key) {
		var sequence=Sortable.sequence(list);
		var newsequence=[];
		var reordered=false;

		//move only, if there is more than one element in the list
		if (sequence.length>1) for (var j = 0; j < sequence.length; j++) {

			//move, if not already last element, the element is not null
			if (j<(sequence.length-1) && sequence[j].length>0 && sequence[j]==key) {
				newsequence[j+1]=key;
				newsequence[j]=sequence[j+1];
				reordered=true;
				j++;
			}
			
			//until element not found, just copy array
			else {
				newsequence[j]=sequence[j];
			}
		}

		if (reordered) Sortable.setSequence (list,newsequence);
		return reordered;
	}

	/*
	handles moving down for both lists
	*/

	function timezones_moveElementDown(key) {
		if (timezones_moveElementDownforList('listTaken', key)==false)
			timezones_moveElementDownforList('listAvailable', key);

		timezones_updateDragandDropLists();
	}

    /*
    create drag and drop lists
    */

    Sortable.create("listTaken", {
	dropOnEmpty:true,
	containment:["listTaken","listAvailable"],
	constraint:false,
	onUpdate:function(){ timezones_updateDragandDropLists(); }
	});

    Sortable.create("listAvailable", {
	dropOnEmpty:true,
	containment:["listTaken","listAvailable"],
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

	var sequence=Sortable.sequence('listTaken');
	if (sequence.length>0) {
		var list = escape(Sortable.sequence('listTaken'));
		var sorted_ids = unescape(list).split(',');
	}

	else {
		var sorted_ids = [-1];
	}

	/*
	set new entries
	*/

	document.getElementsByName('TimeZones')[0].value='';

	for (var i = 0; i < sorted_ids.length; i++) {

		if (sorted_ids[i]!=-1) {
			var timeZoneFromElement=(document.getElementById('Tags_'+sorted_ids[i]).childNodes[2].nodeValue).split('\n');
			var oldValue=document.getElementsByName('TimeZones')[0].value;
			document.getElementsByName('TimeZones')[0].value=oldValue+timeZoneFromElement[0]+"\n";
		}

	}

	/*
	dynamically set new list heights
	*/

      var elementHeight=53;

	var listTakenLength=sorted_ids.length*elementHeight;
	if (listTakenLength<=0) listTakenLength=elementHeight;
	document.getElementById('listTaken').style.height = (listTakenLength)+'px';

	list = escape(Sortable.sequence('listAvailable'));
	sorted_ids = unescape(list).split(',');

	listTakenLength=sorted_ids.length*elementHeight;
	if (listTakenLength<=0) listTakenLength=elementHeight;
	document.getElementById('listAvailable').style.height = (listTakenLength)+'px';

	}

	/*
	load selected field in edit panel
	populate timezone attributes
	*/

	function timezones_adoptDragandDropEdit (key) {
		var timezones_newentry="timezones_newentry_";

		document.getElementsByName(timezones_newentry+'SuccessLabel')[0].style.display='none';
		document.getElementById(timezones_newentry+'idtochange').value=key;
		document.getElementById('timezones_create').value='Edit';

		var timeZoneFromElement=document.getElementById('Tags_'+key).childNodes[2].nodeValue;
		var timeZoneFromElementAttributes=timeZoneFromElement.split(';');

		/*
		set values of edit fields
		*/

		for (var j = 0; j < fields.length; j++) {
			document.getElementById(timezones_newentry+fields[j]).value=timeZoneFromElementAttributes[j];
		}

		if (timeZoneFromElementAttributes[5]==0 || timeZoneFromElementAttributes[5]==1)
			document.getElementsByName(timezones_newentry+'daylight_saving_for')[0].selectedIndex=timeZoneFromElementAttributes[5];
		else if (timeZoneFromElementAttributes[5]==-1)
			document.getElementsByName(timezones_newentry+'daylight_saving_for')[0].selectedIndex=2;

		if (timeZoneFromElementAttributes[6]==1)
			document.getElementById(timezones_newentry+'daylight_saving_for_us_zone').checked='checked';
		else
			document.getElementById(timezones_newentry+'daylight_saving_for_us_zone').checked='';

		document.getElementsByName(timezones_newentry+'abbr_standard')[0].focus();
	}

	 /*
	 append created entry into textarea or
	 apply changes of currently selected timezone
	 */

	 function timezones_appendEntry() {

		var timezones_newentry="timezones_newentry_";

		document.getElementsByName(timezones_newentry+'SuccessLabel')[0].style.display='none';

		var errormsg="";

		//check for name empty fields

		if (timezones_IsEmpty(document.getElementsByName(timezones_newentry+'abbr_standard')[0]) &&
		    !timezones_IsEmpty(document.getElementsByName(timezones_newentry+'name_standard')[0]) ) {
			errormsg+="\n - name standard will not be displayed, because abbr standard is empty";
		}

		if (timezones_IsEmpty(document.getElementsByName(timezones_newentry+'abbr_daylightsaving')[0]) &&
		    !timezones_IsEmpty(document.getElementsByName(timezones_newentry+'name_daylightsaving')[0]) ) {
			errormsg+="\n - name daylightsaving will not be displayed, because abbr daylightsaving is empty";
		}

		/*
		offset entered?
		*/

		if (timezones_IsEmpty(document.getElementsByName(timezones_newentry+'offset')[0]) ) {
			errormsg+="\n - offset empty";
		}

		/*
		if offset entered, check if numeric
		*/

		else {
			if (!timezones_checkNumeric(document.getElementsByName(timezones_newentry+'offset')[0],-12.5,12.5,'','.','-',false) ) {
				errormsg+="\n - offset not numeric or not between -12.5 and 12.5";
			}
		}

		if (errormsg.length==0) {		

			var timezones_newentry_abbr_standard= document.getElementsByName(timezones_newentry+'abbr_standard')[0].value;
			var timezones_newentry_name_standard= document.getElementsByName(timezones_newentry+'name_standard')[0].value;
			var timezones_newentry_abbr_daylightsaving= document.getElementsByName(timezones_newentry+'abbr_daylightsaving')[0].value;
			var timezones_newentry_name_daylightsaving= document.getElementsByName(timezones_newentry+'name_daylightsaving')[0].value;
			var timezones_newentry_offset= document.getElementsByName(timezones_newentry+'offset')[0].value;
			var timezones_newentry_daylight_saving_for= document.getElementsByName(timezones_newentry+'daylight_saving_for')[0].options[ document.getElementsByName(timezones_newentry+'daylight_saving_for')[0].selectedIndex ].value;
			var timezones_newentry_daylight_saving_for_us_zone= timezones_convertBoolean2Int(document.getElementsByName(timezones_newentry+'daylight_saving_for_us_zone')[0].checked);

			var ret=timezones_newentry_abbr_standard+";"+
				timezones_newentry_name_standard+";"+
				timezones_newentry_abbr_daylightsaving+";"+
				timezones_newentry_name_daylightsaving+";"+
				timezones_newentry_offset+";"+
				timezones_newentry_daylight_saving_for+";"+
				timezones_newentry_daylight_saving_for_us_zone;

			var idtochange=document.getElementById(timezones_newentry+'idtochange').value;

			/*
			change timezone attributes
			*/

			if (idtochange.length>0) {
				document.getElementById('Tags_'+idtochange).childNodes[2].nodeValue=ret;
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

				if (Sortable.sequence('listTaken').length>0 || Sortable.sequence('listAvailable').length>0) {
					var listTaken = escape(Sortable.sequence('listTaken'));
					var listTaken_sorted_ids = unescape(listTaken).split(',');

					var listAvailable = escape(Sortable.sequence('listAvailable'));
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

				var plugin_url = '<?php echo(get_settings('siteurl').'/wp-content/plugins/timezonecalculator/');?>';
		            var upArrow='<img class="timezones_arrowbutton" src="'+plugin_url+'arrow_up_blue.png" onclick="timezones_moveElementUp('+nextTagID+');" alt="move element up" />';
		            var downArrow='<img class="timezones_arrowbutton" style="margin-right:20px;" src="'+plugin_url+'arrow_down_blue.png" onclick="timezones_moveElementDown('+nextTagID+');" alt="move element down" />';

				var newElement='<li class="timezones_sortablelist" id="Tags_'+nextTagID+'">'+upArrow+downArrow+ret+'</li>';
				new Insertion.Bottom('listTaken',newElement);

				Event.observe('Tags_'+nextTagID, 'click', function(e){ timezones_adoptDragandDropEdit(nextTagID) });

				/*
				reinitialize drag and drop lists
				*/

			      Sortable.create("listTaken", {
					dropOnEmpty:true,
					containment:["listTaken","listAvailable"],
					constraint:false,
					onUpdate:function(){ timezones_updateDragandDropLists(); }
				});

			      Sortable.create("listAvailable", {
					dropOnEmpty:true,
					containment:["listTaken","listAvailable"],
					constraint:false
				});

				timezones_updateDragandDropLists();
				new Effect.Highlight(document.getElementById('Tags_'+nextTagID),{startcolor:'#30df8b'});

			}

			new Effect.Highlight(document.getElementsByName('timezones_DragandDrop')[0],{startcolor:'#30df8b'});
			new Effect.Appear(document.getElementsByName(timezones_newentry+'SuccessLabel')[0]);
		}

		else {
			new Effect.Highlight(document.getElementsByName('timezones_DragandDrop')[0],{startcolor:'#FF0000'});
			alert('The following error(s) occured:'+errormsg);
		}

	 }

	 /*
	 reset new entry form
	 */

	 function timezones_resetNewEntryForm() {
		var timezones_newentry="timezones_newentry_";
		document.getElementById(timezones_newentry+'idtochange').value='';
		document.getElementById('timezones_create').value='Insert';

		document.getElementsByName(timezones_newentry+'SuccessLabel')[0].style.display='none';

		for (var i = 0; i < fields.length; i++) {
			document.getElementsByName(timezones_newentry+fields[i])[0].value="";
		}

		document.getElementsByName(timezones_newentry+'daylight_saving_for')[0].selectedIndex=0;
		document.getElementsByName(timezones_newentry+'daylight_saving_for_us_zone')[0].checked=false;

	 }

	 /*
	 load a sample entry
	 */

	 function timezones_loadExample() {
		var timezones_newentry="timezones_newentry_";
		document.getElementById(timezones_newentry+'idtochange').value='';
		document.getElementById('timezones_create').value='Insert';

		document.getElementsByName(timezones_newentry+'SuccessLabel')[0].style.display='none';

		var example = ["CET", "Central European Time", "CEST", "Central European Summer Time", "1"];

		for (var i = 0; i < fields.length; i++) {
			document.getElementsByName(timezones_newentry+fields[i])[0].value=example[i];
		}

		document.getElementsByName(timezones_newentry+'daylight_saving_for')[0].selectedIndex=0;
		document.getElementsByName(timezones_newentry+'daylight_saving_for_us_zone')[0].checked=false;

	 }

	 new Draggable('timezones_DragandDrop');

       Event.observe('timezones_create', 'click', function(e){ timezones_appendEntry(); });
       Event.observe('timezones_new', 'click', function(e){ timezones_resetNewEntryForm(); });
       Event.observe('timezones_loadExample', 'click', function(e){ timezones_loadExample(); });

       Event.observe('info_update_click', 'click', function(e){ document.getElementsByName('info_update')[0].click(); });
       Event.observe('load_default_click', 'click', function(e){ document.getElementsByName('load_default')[0].click(); });

       <?php echo($listTakenListeners); ?>
       </script>

	 <?php }

}

add_action('init', 'timezonecalculator_init');
add_action('widgets_init', 'widget_timezonecalculator_init');

?>