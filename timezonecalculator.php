<?php

/*
Plugin Name: TimeZoneCalculator
Plugin URI: http://www.neotrinity.at/projects/
Description: calculates different times and dates in timezones with respect to daylight saving on basis of utc. Edit your timezones here <a href="templates.php?file=wp-content%2Fplugins%2Ftimezones.txt&submit=Edit+file+%C2%BB">here</a>. (Works for me, maybe not for you!)
Version: 0.13 (beta)
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
  echo("<meta name=\"TimeZoneCalculator\" content=\"0.13\" />\n");
}

/*
environment variables
*/

$dataFile="timezones.txt";
$before="<li>";
$after="</li>";

/*
**********************************************
stop editing here unless you know what you do!
**********************************************
/*

/*
this methods prints all timezone entries of the chosen file
*/

function getTimeZonesTime() {
	global $dataFile;

	$timeZonesTime=readFile2Array($dataFile, 6, "TimeZones");

	//at minimum one correct entry
	if ($timeZonesTime) {
		$counter=0;

		foreach ($timeZonesTime as $timeZoneTime) {

			$counter++;

			//data-check ok
			if (checkData($timeZoneTime)) {
				echo (getTimeZoneTime(array($timeZoneTime[0],$timeZoneTime[2]),
							    array($timeZoneTime[1],$timeZoneTime[3]),
							    $timeZoneTime[4],
							    $timeZoneTime[5])."\n");
			}

			else {
				getErrorMessage("Could not load line ".$counter."! - The offset or the invert parameter are not correct. See the examples for hints.");
			}
		}
	}
}

/*
checks if the data is matching the defined criteria
*/

function checkData($timeZoneTime) {

	/* inverse-options:
	-1: no daylight saving ==> use standard time 4 the whole year
	0: daylight saving time of timezone is equal to server daylight saving time
	1: invert daylight saving time
	*/

	$inverse=$timeZoneTime[5];
	if ($inverse<-1 || $inverse>1) {
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
reads the input file
*/

function readFile2Array($dataFile, $len, $friendlyFileName) {

	//get filecontent
	//$lines = @file(dirname(__FILE__) . "/". $dataFile);

	$fileContent=file_get_contents(dirname(__FILE__) . "/". $dataFile);
	$lines=explode("\n", $fileContent);

	//existing lines loaded in array
	if ($lines) {
		$ret=array();

		$counter=0;
		foreach($lines as $line) {

			//is the line long enough?
			if (strlen($line)>$len*2) {
				$counter++;
				$res=readFileLine2Array($line, $len);

				//correct line, add to result array
				if ($res) {
					$ret=array_merge($ret, array($res));
				}

				//error in attributes
				else {
					getErrorMessage("Could not load line ".$counter."! - Some settings are not correct. See the examples for hints.");
				}

			}
		}

		//return result array if there is a least one correct line
		if (sizeof($ret)>0) {
			return $ret;
		}

		//no or no correct line
		else {
			getErrorMessage("There is nothing to display. - The file may be empty or there may be errors in the timezone entries.");
			return false;
		}

	}

	//file not found
	else {
		getErrorMessage("Could not find your ".$friendlyFileName." file!");
		return false;
	}

	return false;

}

/*
reads one line of the input file and fulfills some proofs
*/

function readFileLine2Array($line, $len) {

	//write attributes of each line into array
	$tokens=explode(";", $line);

	//all attributes set? wrong lines will be ignored; no other checks
	if (sizeof($tokens)==$len) {
		$end=false;
		$tokenCounter=0;

		while (!$end) {
			//work-around 4 windows-bug \r = end of line
			$temp=explode("\r", $tokens[$tokenCounter]);

			//length of all tokens > 0
			if (is_null($temp[0]) ||
                      strlen($temp[0])<1) {
				return false;
			}

			//termination
			if ($tokenCounter==(sizeof($tokens)-1)) {
				return $tokens;
			}

			$tokenCounter++;
		}
	}

	return false;
}

/*
this methods returns the actual timestamp including all relevant data for the chosen timezone for example as list-entry
*/

function getTimeZoneTime($abbrs, $names, $offset, $inverse) {
	global $before, $after;

	$ret="<abbr title=\"";

	//is daylightsaving set on server?
	$daylightsaving=date('I');

	//reverse daylightsaving, depends on server location & timezone set
	if ($inverse==1) {
		if ($daylightsaving==1) {
			$daylightsaving=0;
		}
		else {

			$daylightsaving=1;
		}
	}

	//no daylight-saving in this timezone
	else if ($inverse==-1) {
		$daylightsaving=0;
	}

	$ret=$ret.$names[$daylightsaving]."\">".$abbrs[$daylightsaving]."</abbr>: ";
	$ret=$ret.gmdate('Y-m-d H:i',(time() + 3600 * ($offset + $daylightsaving)));

	return $before.$ret.$after;
}

/*
display errormessage
*/

function getErrorMessage($msg) {
	global $before, $after;
	echo($before."Sorry! ".$msg.$after."\n");
}

?>