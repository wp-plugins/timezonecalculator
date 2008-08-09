<?php

/*  Copyright 2006-2008  Bernhard Riedl  (email : neo@neotrinity.at)

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

function timezonecalculator_uninstall() {

	delete_option('widget_timezonecalculator');

	$fieldsPre="timezones_";
	$csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "Time_Format");

	$sections=array('Content_Section' => '0', 'CSS_Tags_Section' => '0');

	foreach ($csstags as $csstag) {
		delete_option($fieldsPre.$csstag);
	}

	delete_option("TimeZones");

	foreach ($sections as $key => $section) {
		delete_option($fieldsPre.$key);
	}

}

?>