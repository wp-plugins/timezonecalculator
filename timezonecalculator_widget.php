<?php

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
widget class instance
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

		<p><a href="options-general.php?page=<?php echo(plugin_basename(dirname(__FILE__))); ?>/timezonecalculator.php"><?php _e('Settings') ?></a></p>

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

?>