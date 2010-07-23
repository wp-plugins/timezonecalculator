<?php

/*
Plugin Name: TimeZoneCalculator
Plugin URI: http://www.neotrinity.at/projects/
Description: Calculates, displays and automatically updates times and dates in different timezones with respect to daylight saving.
Author: Dr. Bernhard Riedl
Version: 2.10
Author URI: http://www.bernhard.riedl.name/
*/

/*
Copyright 2006-2010 Dr. Bernhard Riedl

Inspirations & Proof-Reading 2007-2010
by Veronika Grascher

This program is free software:
you can redistribute it and/or modify
it under the terms of the
GNU General Public License as published by
the Free Software Foundation,
either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope
that it will be useful,
but WITHOUT ANY WARRANTY;
without even the implied warranty of
MERCHANTABILITY or
FITNESS FOR A PARTICULAR PURPOSE.

See the GNU General Public License
for more details.

You should have received a copy of the
GNU General Public License
along with this program.

If not, see http://www.gnu.org/licenses/.
*/

/*
create global instance
*/

global $timezonecalculator;

if (empty($timezonecalculator) || !is_object($timezonecalculator) || !$timezonecalculator instanceof TimeZoneCalculator)
	$timezonecalculator=new TimeZoneCalculator();

/*
Class
*/

class TimeZoneCalculator {

	/*
	prefix for fields, options, etc.
	*/

	private $prefix='timezonecalculator';

	/*
	nicename for options-page,
	meta-data, etc.
	*/

	private $nicename='TimeZoneCalculator';

	/*
	plugin_url with trailing slash
	*/

	private $plugin_url;

	/*
	fallback defaults
	*/

	private $fallback_defaults=array(
		'timezones' => array(
			'UTC;;;;;1;1'
		),

		'prefer_user_timezones' => false,

		'query_time' => '',
		'query_timezone' => '',

		'before_list' => '<ul>',
		'after_list' => '</ul>',
		'format_timezone' => '<li><abbr title="%name">%abbreviation</abbr>: <span title="%name">%datetime</span></li>',
		'format_datetime' => 'Y-m-d H:i',
		'use_container' => true,
		'display' => true,
	);

	/*
	current defaults
	(merged database and fallback_defaults)
	*/

	private $defaults=array();

	/*
	fallback options
	*/

	private $fallback_options=array(
		'use_ajax_refresh' => true,
		'ajax_refresh_time' => 30,
		'renew_nonce' => false,
		'anonymous_ajax_refresh' => true,

		'dashboard_widget' => false,
		'dashboard_widget_capability' => 'read',
		'dashboard_right_now' => false,
		'dashboard_right_now_capability' => 'read',

		'calculator_capability' => 'read',

		'world_clock_tools_page' => false,
		'world_clock_tools_page_capability' => 'read',
		'include_world_clock_user_profile' => false,

		'include_wordpress_clock_admin_head' => false,

		'view_other_users_timezones_capability' => 'edit_users',

		'debug_mode' => false,

		'section' => 'drag_and_drop'
	);

	/*
	current options
	(merged database and fallback_options)
	*/

	private $options=array();

	/*
	block_count holds the current number
	of elements which have been processed
	*/

	private $block_count=0;

	/*
	options-page sections/option-groups
	*/

	private $options_page_sections=array(
		'drag_and_drop' => array(
			'nicename' => 'Drag and Drop Layout',
			'callback' => 'drag_and_drop'
		),
		'expert' => array(
			'nicename' => 'Expert Settings',
			'callback' => 'expert',
			'fields' => array(
				'timezones' => 'TimeZones'
			)
		),
		'format' => array(
			'nicename' => 'Format',
			'callback' => 'format',
			'fields' => array(
				'before_list' => 'before List',
				'after_list' => 'after List',
				'format_timezone' => 'Format of TimeZone-Entry',
				'format_datetime' => 'Format of Date/Time',
				'use_container' => 'Wrap output in div-container',
				'display' => 'Display Results'
			)
		),
		'ajax_refresh' => array(
			'nicename' => 'Ajax Refresh',
			'callback' => 'ajax_refresh',
			'fields' => array(
				'use_ajax_refresh' => 'Use Ajax Refresh',
				'ajax_refresh_time' => 'Ajax Refresh Time',
				'renew_nonce' => 'Renew nonce to assure continous updates',
				'anonymous_ajax_refresh' => 'Allow anonymous Ajax Refresh Requests'
				)
		),
		'dashboard' => array(
			'nicename' => 'Dashboard',
			'callback' => 'dashboard',
			'fields' => array(
				'dashboard_widget' => 'Enable Dashboard Widget',
				'dashboard_widget_capability' => 'Capability to view Dashboard Widget',
				'dashboard_right_now' => 'Integrate in "Right Now" Box',
				'dashboard_right_now_capability' => 'Capability to integrate in "Right Now" Box'
			)
		),
		'calculator' => array(
			'nicename' => 'Calculator',
			'callback' => 'calculator',
			'fields' => array(
				'calculator_capability' => 'Capability to work with TimeZone-Calculator'
			)
		),
		'world_clock' => array(
			'nicename' => 'World Clock',
			'callback' => 'world_clock',
			'fields' => array(
				'world_clock_tools_page' => 'Enable World Clock in Tools Menu',
				'world_clock_tools_page_capability' => 'Capability to view World Clock in Tools Menu',
				'include_world_clock_user_profile' => 'Display user selected timezones (world clock) on user profile page'
			)
		),
		'administrative_options' => array(
			'nicename' => 'Administrative Options',
			'callback' => 'administrative_options',
			'fields' => array(
				'prefer_user_timezones' => 'Prefer User TimeZones',
				'include_wordpress_clock_admin_head' => 'Display WordPress Clock in Admin Header',
				'view_other_users_timezones_capability' => 'Capability to view timezones-selection of other users',
				'debug_mode' => 'Enable Debug-Mode'
			)
		),
		'preview' => array(
			'nicename' => 'Preview',
			'callback' => 'preview'
		)
	);

	/*
	calculator-page sections/calculator-groups
	*/

	private $calculator_page_sections=array(
		'calculator' => array(
			'nicename' => 'Calculator',
			'callback' => 'calculator'
		),
		'selected_timezones' => array(
			'nicename' => 'Selected TimeZones',
			'callback' => 'selected_timezones'
		)
	);

	/*
	Constructor
	*/

	function __construct() {

		/*
		store current timestamp in constant
		*/

		DEFINE ('TIMEZONECALCULATOR_CURRENTGMDATE', gmdate('U'));

		/*
		initialize object
		*/

		$this->set_plugin_url();
		$this->retrieve_settings();
		$this->register_scripts();
		$this->register_styles();
		$this->register_hooks();
	}

	/*
	register js libraries
	*/

	private function register_scripts() {
		wp_register_script($this->get_prefix().'refresh', $this->get_plugin_url().'js/refresh.js', array('prototype'), '2.00');

		wp_register_script($this->get_prefix().'datepicker', $this->get_plugin_url().'js/datepicker/js/datepicker.js', array(), '5.4');

		wp_register_script($this->get_prefix().'utils', $this->get_plugin_url().'js/utils.js', array('prototype'), '2.10');

		wp_register_script($this->get_prefix().'timezones', $this->get_plugin_url().'js/timezones.js', array('prototype'), '2.00');

		wp_register_script($this->get_prefix().'drag_and_drop', $this->get_plugin_url().'js/drag_and_drop.js', array('prototype', 'scriptaculous-effects', 'scriptaculous-dragdrop', $this->get_prefix().'utils', $this->get_prefix().'timezones'), '2.00');

		wp_register_script($this->get_prefix().'settings_page', $this->get_plugin_url().'js/settings_page.js', array('prototype', $this->get_prefix().'utils'), '2.00');

		wp_register_script($this->get_prefix().'calculator', $this->get_plugin_url().'js/calculator.js', array('prototype', $this->get_prefix().'datepicker', $this->get_prefix().'utils', $this->get_prefix().'timezones', $this->get_prefix().'refresh'), '2.10');
	}

	/*
	register css styles
	*/

	private function register_styles() {
		wp_register_style($this->get_prefix().'datepicker', $this->get_plugin_url().'js/datepicker/css/datepicker.css', array(), '5.4');
	}

	/*
	register WordPress hooks
	*/

	private function register_hooks() {

		/*
		general
		*/

		add_filter('plugin_action_links', array(&$this, 'plugin_action_links'), 10, 2);

		add_action('admin_menu', array(&$this, 'admin_menu'));

		/*
		ajax refresh calls
		*/

		/*
		always allowed ajax actions
		*/

		add_action('wp_ajax_'.$this->get_prefix().'calculator', array(&$this, 'wp_ajax_calculator'));

		if ($this->get_option('use_ajax_refresh')) {

			/*
			include ajax refresh scripts
			*/

			add_action('wp_print_scripts', array(&$this, 'refresh_print_scripts'));

			/*
			allowed ajax actions
			*/

			add_action('wp_ajax_'.$this->get_prefix().'output', array(&$this, 'wp_ajax_refresh'));

			/*
			the calculator_ajax_nonce action
			should only be allowed
			if renew_nonce has been set
			*/

			if ($this->get_option('renew_nonce'))
				add_action('wp_ajax_'.$this->get_prefix().'calculator_ajax_nonce', array(&$this, 'wp_ajax_calculator_ajax_nonce'));

			/*
			anonymous ajax refresh requests
			can be restricted
			*/

			if ($this->get_option('anonymous_ajax_refresh'))
				add_action('wp_ajax_nopriv_'.$this->get_prefix().'output', array(&$this, 'wp_ajax_refresh'));
		}

		/*
		meta-data
		*/

		add_action('wp_head', array(&$this, 'head_meta'));
		add_action('admin_head', array(&$this, 'head_meta'));

		/*
		widgets
		*/

		add_action('widgets_init', array(&$this, 'widgets_init'));

		add_action('wp_dashboard_setup', array(&$this, 'add_dashboard_widget'));

		add_action('activity_box_end', array(&$this, 'add_right_now_box'));

		add_action('admin_print_styles-index.php', array(&$this, 'add_dashboard_widget_css'));

		/*
		i18n for timezones
		*/

		add_action('init', array(&$this, 'init'));

		/*
		shortcode
		*/

		add_shortcode($this->get_prefix().'output', array(&$this, 'shortcode_output'));

		/*
		profile_page
		*/

		add_action('show_user_profile', array(&$this, 'show_user_profile'));
		add_action('edit_user_profile', array(&$this, 'show_user_profile'));

		/*
		admin_head
		*/

		add_action('admin_footer', array(&$this, 'admin_head_clock'));

		/*
		whitelist options
		*/

		add_action('admin_init', array(&$this, 'admin_init'));
	}

	/*
	GETTERS AND SETTERS
	*/

	/*
	getter for prefix
	true with trailing _
	false without trailing _
	*/

	function get_prefix($trailing_=true) {
		if ($trailing_)
			return $this->prefix.'_';
		else
			return $this->prefix;
	}

	/*
	getter for nicename
	*/

	function get_nicename() {
		return $this->nicename;
	}

	/*
	setter for plugin_url
	*/

	private function set_plugin_url() {
		$this->plugin_url=plugins_url('', __FILE__).'/';
	}

	/*
	getter for plugin_url
	*/

	private function get_plugin_url() {
		return $this->plugin_url;
	}

	/*
	increment for block_count
	*/

	private function increment_block_count() {
		$this->block_count++;
	}

	/*
	getter for block-count
	*/

	private function get_block_count() {
		return $this->block_count;
	}

	/*
	getter for calculator tools_page
	*/

	private function get_calculator_tools_page() {
		return $this->get_prefix().'calculator';
	}

	/*
	getter for world_clock tools_page
	*/

	private function get_world_clock_tools_page() {
		return $this->get_prefix().'world_clock';
	}

	/*
	getter for default parameter
	*/

	private function get_default($param) {
		if (isset($this->defaults[$param]))
			return $this->defaults[$param];
		else
			return false;
	}

	/*
	getter for default parameter
	*/

	private function get_option($param) {
		if (isset($this->options[$param]))
			return $this->options[$param];
		else
			return false;
	}

	/*
	retrieve settings from database
	and merge with fallback-settings
	*/

	private function retrieve_settings() {
		$settings=get_option($this->get_prefix(false));

		/*
		did we retrieve an non-empty
		settings-array which we can
		merge with the default settings?
		*/

		if (!empty($settings) && is_array($settings)) {

			/*
			process options-array
			*/

			if (array_key_exists('options', $settings) && is_array($settings['options'])) {
				$this->options = array_merge($this->fallback_options, $settings['options']);
				$this->log('merging fallback-options '.var_export($this->fallback_options, true).' with database options '.var_export($settings['options'], true));
			}

			/*
			process defaults-array
			*/

			if (array_key_exists('defaults', $settings) && is_array($settings['defaults'])) {
				$this->defaults = array_merge($this->fallback_defaults, $settings['defaults']);
				$this->log('merging fallback-defaults '.var_export($this->fallback_defaults, true).' with database defaults '.var_export($settings['defaults'], true));
			}
		}

		/*
		settings-array does not exist
		*/

		else {

			/*
			are we handling an update?

			check for trigger field
			if this field exist,
			we handle an update
			*/

			$maybe_timezones=get_option('TimeZones');

			if (!empty($maybe_timezones)) {

				/*
				conduct upgrade
				*/

				$this->upgrade_v2();

				/*
				restart retrieval process
				*/

				$this->retrieve_settings();

				/*
				avoid further processing of
				first retrieve_settings
				function call
				*/

				return;
			}

			/*
			for some strange reason,
			WordPress doesn't like to
			write to an empty option
			*/

			else {
				update_option($this->get_prefix(false), array());
			}
		}

		/*
		if the settings have not been set
		we use the fallback-options array instead
		*/

		if (empty($this->options)) {
			$this->options = $this->fallback_options;
			$this->log('using fallback-options '.var_export($this->fallback_options, true));
		}

		/*
		if the settings have not been set
		we use the fallback-defaults array instead
		*/

		if (empty($this->defaults)) {
			$this->defaults = $this->fallback_defaults;
			$this->log('using fallback-defaults '.var_export($this->fallback_defaults, true));
		}

		$this->log('setting options to '.var_export($this->options, true));

		$this->log('setting defaults to '.var_export($this->defaults, true));
	}

	/*
	Sanitize and validate input
	Accepts an array, return a sanitized array
	*/

	function settings_validate($input) {

		/*
		we handle a reset call
		*/

		if (isset($input['reset'])) {
			return array(
				'defaults' => $this->fallback_defaults,
				'options' => $this->fallback_options
			);
		}

		/*
		check-fields are either 0 or 1
		*/

		$check_fields=array(
			'use_container',
			'display',
			'use_ajax_refresh',
			'renew_nonce',
			'anonymous_ajax_refresh',
			'dashboard_widget',
			'dashboard_right_now',
			'world_clock_tools_page',
			'include_world_clock_user_profile',
			'prefer_user_timezones',
			'include_wordpress_clock_admin_head',
			'debug_mode'
		);

		foreach ($check_fields as $check_field) {
			$input[$check_field] = ($input[$check_field] == 1 ? true : false);
		}

		/*
		these text-fields should not be empty
		*/

		$text_fields=array(
			'timezones',
			'format_timezone',
			'format_datetime',
			'ajax_refresh_time'
		);

		foreach ($text_fields as $text_field) {
			if (isset($input[$text_field]) && strlen($input[$text_field])<1)
				unset($input[$text_field]);
		}

		/*
		selected capabilities have to be
		within available capabilities
		*/

		$capability_fields=array(
			'dashboard_widget',
			'dashboard_right_now',
			'calculator',
			'world_clock_tools_page',
			'view_other_users_timezones'
		);

		$capabilities=$this->get_all_capabilities();

		foreach ($capability_fields as $capability_field) {
			if (!in_array($input[$capability_field.'_capability'], $capabilities))
				unset($input[$capability_field.'_capability']);
		}

		/*
		1 <= ajax_refresh_time <= 3600 (seconds)
		*/

		if (array_key_exists('ajax_refresh_time', $input))
			if (!$this->is_integer($input['ajax_refresh_time']) || $input['ajax_refresh_time']<1 || $input['ajax_refresh_time']>3600)
				$input['ajax_refresh_time']=$this->fallback_options['ajax_refresh_time'];

		/*
		split timezones-string by newline
		*/

		if (array_key_exists('timezones', $input)) {
			$temp_timezones=explode("\n", $input['timezones']);

			/*
			did we receive a results array?
			*/

			if (is_array($temp_timezones)) {
				$input['timezones']=array();

				/*
				we don't need empty lines
				*/

				foreach ($temp_timezones as $timezone) {
					if (strlen($timezone)>1)
						array_push($input['timezones'], trim($timezone));
				}

				/*
				nothing parsed
				*/

				if (empty($input['timezones']))
					unset($input['timezones']);
			}

			/*
			couldn't convert
			*/

			else
				unset($input['timezones']);
		}

		/*
		include options
		*/

		$options=$this->fallback_options;

		foreach($options as $option => $value) {
			if (array_key_exists($option, $input))
				$options[$option]=$input[$option];
		}

		/*
		include defaults
		*/

		$defaults=$this->fallback_defaults;

		foreach($defaults as $default => $value) {
			if (array_key_exists($default, $input))
				$defaults[$default]=$input[$default];
		}

		/*
		we don't need to store the query_time or
		timezone as it is only necessary for
		non-default function calls
		*/

		if (array_key_exists('query_time', $defaults))
			unset($defaults['query_time']);

		if (array_key_exists('query_timezone', $defaults))
			unset($defaults['query_timezone']);

		$ret_val=array();

		$ret_val['defaults']=$defaults;
		$ret_val['options']=$options;

		return $ret_val;
	}

	/*
	upgrade options to TimeZoneCalculator v2
	*/

	private function upgrade_v2() {
 
		$this->log('upgrade options to '.$this->get_nicename().' v2');

		$fieldsPre='timezones_';
		$sectionPost='_Section';

		/*
		this array will hold all old settings
		as if we would handle a "Save Changes"
		call of the options-page
		*/

		$settings=array();

		$timezones=get_option('TimeZones');
		$settings['timezones']=$timezones;

		$this->log('timezones set to '.$timezones);

		delete_option('TimeZones');

		/*
		key field holds the old option name
		value the new option name
		*/

		$upgrade_options=array(
			'before_List' => 'before_list',
			'after_List' => 'after_list',
			'before_Tag' => 'before_tag',
			'after_Tag' => 'after_tag',
			'Time_Format' => 'format_datetime',
			'Use_Ajax_Refresh' => 'use_ajax_refresh',
			'Refresh_Time' => 'ajax_refresh_time'
		);

		/*
		loop through all available old options
		*/

		foreach ($upgrade_options as $old_option => $new_option) {
			$old_option_value=get_option($fieldsPre.$old_option);
			$settings[$new_option]=$old_option_value;

			$this->log('option '.$new_option.' set to '.$old_option_value);

			/*
			remove old option
			*/

			delete_option($fieldsPre.$old_option);
		}

		/*
		old before_Tag and after_Tag,
		will be merged to format_timezone
		*/

		$settings['format_timezone']=$settings['before_tag'].'<abbr title="%name">%abbreviation</abbr>: <span title="%name">%datetime</span>'.$settings['after_tag'];

		unset($settings['before_tag']);
		unset($settings['after_tag']);

		/*
		delete section settings
		*/

		$sections=array(
			'Instructions',
			'Content',
			'CSS_Tags',
			'Administrative_Options',
			'Calculation'
		);

		foreach ($sections as $section) {
			delete_option($fieldsPre.$section.$sectionPost);
		}

		/*
		include check_fields
		which need to be set true
		*/

		$settings['use_container']='1';
		$settings['display']='1';
		$settings['anonymous_ajax_refresh']='1';

		/*
		validate retrieved settings
		*/

		$settings=$this->settings_validate($settings);

		/*
		store new settings
		*/

		update_option($this->get_prefix(false), $settings);

		$this->log('upgrade finished. - retrieved options are: '.var_export($settings, true));
	}

	/*
	merges parameter array with defaults array
	defaults-array can be changed with filter
	'timezonecalculator_defaults'
	*/

	private function fill_default_parameters($params) {

		/*
		apply filter timezonecalculator_defaults
		*/

		$filtered_defaults=apply_filters($this->get_prefix().'defaults', $this->defaults);

		/*
		merge filtered defaults with params
		params overwrite merged defaults
		*/

		return wp_parse_args($params, $filtered_defaults);
	}

	/*
	UTILITY FUNCTIONS
	*/

	/*
	checks if a value is an integer

	regex taken from php.net
	by mark at codedesigner dot nl
	*/

	function is_integer($value) {
		return preg_match('@^[-]?[0-9]+$@', $value);
	}

	/*
	rounds to the nearest multiple
	by Mark Blaszczyk

	taken from http://www.markblah.com/2008/05/11/php-round-number-to-nearest-x/
	*/

	function round_to_nearest($num, $nearest) {
		$ret = 0;
		$mod = $num % $nearest;

		if ($mod >= 0)
			$ret = ( $mod > ( $nearest / 2)) ? $num + ( $nearest - $mod) : $num - $mod;
		else
			$ret = ( $mod > (-$nearest / 2)) ? $num - $mod : $num + ( -$nearest - $mod);
		return $ret;
	}

	/*
	shows log messages on screen

	if debug_mode is set to true
	optionally executes trigger_error
	if we're handling an error
	*/

	private function log($message, $status=0) {
		$date_format = get_option('date_format');
		$time_format = get_option('time_format');

		$log_line=gmdate($date_format.' '.$time_format, current_time('timestamp')).' ';

		/*
		determine the log line's prefix
		*/

		if ($status==0)
			$log_line.='INFO';
		else if ($status==-1)
			$log_line.='<strong>ERROR</strong>';
		else if ($status==-2)
			$log_line.='WARNING';
		else if ($status==1)
			$log_line.='SQL';

		/*
		append message
		*/

		$log_line.=' '.$message.'<br />';

		/*
		output message to screen
		*/

		if ($this->get_option('debug_mode'))
			echo($log_line);

		/*
		output message to file
		*/

		if ($status<0)
			trigger_error($message);
	}

	/*
	warns about deprecated functions
	*/

	function deprecated_function($function, $version, $replacement) {
		$this->log(sprintf( __('%1$s is <strong>deprecated</strong> since '.$this->get_nicename().' version %2$s! Use <strong>$'.$this->get_prefix(false).'->%3$s()</strong> instead.'), $function, $version, $replacement), -2);
	}

	/*
	called by wp_ajax_* and wp_ajax_nopriv_* hooks
	*/

	private function do_ajax_refresh($_ajax_nonce=true) {

		$action=$_REQUEST['action'];
		$query_string='';

		if (isset($_REQUEST['query_string']))
			$query_string=$_REQUEST['query_string'];

		/*
		security check
		*/

		$security_string.=$action.str_replace(array('\n', "\n"), '', $query_string);

		check_ajax_referer($security_string);

		/*
		convert query_string to params-array
		*/

		$params=array();

		$ajax_musts=array(
			'use_container' => true,
			'display' => false,
			'no_refresh' => true
		);

		/*
		parse retrieved query_string
		*/

		if (!empty($query_string))
			$params=wp_parse_args($query_string);

		$params=array_merge($params, $ajax_musts);

		/*
		remove leading prefix from action
		*/

		$method=str_replace($this->get_prefix(), '', $action);

		/*
		prepare json string
		*/

		$json_params=array();

		/*
		we only provide a renewed nonce
		if the admin has chosen to
		*/

		if ($this->get_option('renew_nonce')) {

			/*
			return updated (2nd tick) _ajax_nonce
			*/

			if ($_ajax_nonce===true)
				$json_params['_ajax_nonce']=wp_create_nonce($security_string);

			/*
			use provided _ajax_nonce
			*/

			else if (!empty($_ajax_nonce))
				$json_params['_ajax_nonce']=$_ajax_nonce;
		}

		/*
		call function output/count
		*/

		$json_params['result']=call_user_func(array($this, $method), $params);

		$this->output_json($json_params);
	}

	/*
	outputs a json-object
	*/

	private function output_json($params) {

		if (!is_array($params)) {
			echo("-1");
			return -1;
		}

		/*
		prepare json string
		*/

		$ret_val='{';

		foreach ($params as $key => $param)
			$ret_val.='"'.$key.'":"'.addslashes($param).'",';

		$ret_val=substr($ret_val, 0, -1);

		$ret_val.='}';

		header('Content-type: application/json');
		echo($ret_val);
	}

	/*
	returns all capabilities without 'level_'
	*/

	private function get_all_capabilities() {
		$wp_roles=new WP_Roles();
		$names=$wp_roles->get_names();

		$all_caps=array();

		foreach($names as $name_key => $name) {
			$wp_role=$wp_roles->get_role($name_key);
			$role_caps=$wp_role->capabilities;

			foreach($role_caps as $cap_key => $role_cap) {
				if (!in_array($cap_key, $all_caps) && strpos($cap_key, 'level_')===false)
					$all_caps[]=$cap_key;
			}
		}

		asort($all_caps);

		return $all_caps;
	}

	/*
	process the admin_color-array
	*/

	private function get_admin_colors() {

		/*
		default colors = fresh
		*/

		$available_admin_colors=array(
			'fresh' => array(
				'#464646',
				'#6D6D6D',
				'#F1F1F1',
				'#DFDFDF'
			),
			'classic' => array(
				'#073447',
				'#21759B',
				'#EAF3FA',
				'#BBD8E7'
			)
		);

		$current_color = get_user_option('admin_color');
		if (strlen($current_color)<1)
			$current_color='fresh';

		/*
		include user-defined color schemes
		*/

		$user_available_admin_colors = apply_filters($this->get_prefix().'available_admin_colors', array());

		if (!empty($user_available_admin_colors) && is_array($user_available_admin_colors))
			foreach($user_available_admin_colors as $key => $available_admin_color)
				if (is_array($available_admin_color) && sizeof($available_admin_color)==4)
					if (!array_key_exists($key, $available_admin_colors))
						$available_admin_colors[$key]=$user_available_admin_colors[$key];

		if (!array_key_exists($current_color, $available_admin_colors))
			return $available_admin_colors['fresh'];
		else
			return $available_admin_colors[$current_color];
	}

	/*
	get block id from output block
	*/

	private function get_block_id_from_block($block) {
		$prefix='block_';

		$pos_block=strpos($block, $prefix);
		$class=strpos($block, '"', $pos_block+1);

		return substr($block, $pos_block+strlen($prefix), $class-$pos_block-strlen($prefix));
	}

	/*
	echoes js-timezones-array
	*/

	function timezone_js_arrays($etc_group=true) {
		$available_continents = array(
			'Africa',
			'America',
			'Antarctica',
			'Arctic',
			'Asia',
			'Atlantic',
			'Australia',
			'Europe',
			'Indian',
			'Pacific'
		);

		$available_timezones = timezone_identifiers_list();

		/*
		prepare array
		*/

		$i = 0;
		foreach ( $available_timezones as $zone ) {
			$zoneArr = explode('/',$zone);

			if (!in_array($zoneArr[0], $available_continents))
				continue;

			$zonen[$i]['continent'] = isset($zoneArr[0]) ? $zoneArr[0] : '';
			$zonen[$i]['city'] = isset($zoneArr[1]) ? $zoneArr[1] : '';
			$zonen[$i]['subcity'] = isset($zoneArr[2]) ? $zoneArr[2] : '';

			$zonen[$i]['t_continent'] = isset($zoneArr[0]) ? __(str_replace('_',' ',$zoneArr[0]), 'continents-cities') : '';
			$zonen[$i]['t_city'] = isset($zoneArr[1]) ? __(str_replace('_',' ',$zoneArr[1]), 'continents-cities') : '';
			$zonen[$i]['t_subcity'] = isset($zoneArr[2]) ? __(str_replace('_',' ',$zoneArr[2]), 'continents-cities') : '';

			$i++;
		}

		usort($zonen, '_wp_timezone_choice_usort_callback');

		/*
		prepare output string
		*/

		$continents = 'var '.$this->get_prefix().'continents_array=\'';

		/*
		add etc group
		*/

		if ($etc_group)
			$continents.='<option value="etc">etc</option>';

		$timezones = 'var '.$this->get_prefix().'timezones_array=new Array(';

		/*
		add etc/UTC as first entry
		*/

		if ($etc_group)
			$timezones.='\'<option value="UTC">UTC</option>';

		if ($etc_group) {

			/*
			if the user has already selected
			a timezone in the admin menu,
			we provide the Local_WordPress_Time option
			*/

			$wordpress_timezone=get_option('timezone_string');

			if (!empty($wordpress_timezone))
				$timezones.='<option value="Local_WordPress_Time">WordPress Time</option>';

			$timezones.='\', ';
		}

		$selectcontinent='';
		$firstcontinent=true;

		/*
		loop through the timezones
		*/

		foreach ( $zonen as $zone ) {
			extract($zone);

			/*
			create continent optgroup
			and close an open one
			*/

			if ( ($selectcontinent != $continent) && !empty($city) ) {
				$selectcontinent = $continent;

				if ($firstcontinent) {
					$firstcontinent=false;
				}
				else {
					$timezones .= "', ";
				}

				$continents .= '<option value="'.$continent.'">'.$t_continent.'</option>';
				$timezones .= "'";
			}

			/*
			if a city name exists,
			add entry to list
			*/

			if ( !empty($city) ) {
				if ( !empty($subcity) ) {
					$city = $city . '/'. $subcity;
					$t_city = $t_city . '/'. $t_subcity;
				}
				$timezones .= '<option value="'.$continent.'/'.$city.'">'.str_replace("'", '&rsquo;', $t_city).'</option>';
			}
		}

	$continents .= "';";
	$timezones.= "');";

	?><script type="text/javascript">

	/* <![CDATA[ */

	<?php echo($continents."\n"); ?>
	<?php echo($timezones."\n"); ?>

	/* ]]> */

	</script><?php

	}

	/*
	checks and transforms a datetime
	and a timezone into a UTC unix timestamp
	*/

	function calculate_date($query_time, $query_timezone, $current_utc=TIMEZONECALCULATOR_CURRENTGMDATE) {
		$ret_val=false;

		/*
		process unix timestamp
		*/

		if ($this->is_integer($query_time))
			$ret_val=$query_time;

		/*
		parse date/time
		*/
	
		elseif (strlen($query_time)>2) {

			$adopted_for_strtotime=$current_utc;

			/*
			adopt timestamp for strtotime
			so +2 hours and tomorrow 3pm
			will be interpreted correctly
			*/

			if (!empty($query_timezone)) {
				$offset=$this->calculate_utc_offset($current_utc, $query_timezone);
				$adopted_for_strtotime+=$offset;
			}

			/*
			set default_timezone to UTC
			because strtotime uses local_time

			remark: actually WordPress uses UTC
			as default timezone in wp-settings.php,
			so the following code shouldn't
			get executed; though I leave it for
			compatibility purposes
			*/

			$zero_offsets=array(
				'UTC',
				'UCT',
				'GMT',
				'GMT0',
				'GMT+0',
				'GMT-0',
				'Greenwich',
				'Universal',
				'Zulu'
			);

			$server_timezone=@date_default_timezone_get();
			$server_timezone_changed=false;

			/*
			check for UTC-strings
			*/

			if (!in_array(str_ireplace('etc/', '', $server_timezone), $zero_offsets)) {
				@date_default_timezone_set('UTC');
				$server_timezone_changed=true;
			}

			/*
			convert query_time, based on
			adopted datetime
			*/

			$parsed_date=strtotime($query_time, $adopted_for_strtotime);

			/*
			set back to previous timezone
			*/

			if ($server_timezone_changed)
				@date_default_timezone_set($server_timezone);

			/*
			could we parse the date/time?
			*/

			if ($parsed_date===false || $parsed_date==-1)
				throw new Exception('could not parse date!');

			$ret_val=$parsed_date;
		}

		/*
		all timestamps between
		1930-01-01 and
		2038-01-01 are accepted
		*/

		if ($ret_val!==false && (int)$ret_val>=-1262304000 && (int)$ret_val<=2145916800) {

			/*
			if no timezone
			has been given, we're
			finished
			*/

			if (empty($query_timezone))
				return $ret_val;

			/*
			otherwise we calculate the offset to utc
			*/

			$offset=$this->calculate_utc_offset($ret_val, $query_timezone);

			return ($ret_val-$offset);
		}

		throw new Exception('unkown datetime');
	}

	/*
	CALLED BY HOOKS
	(and therefore public)
	*/

	/*
	called by wp_ajax_* and wp_ajax_nopriv_* hooks
	*/

	function wp_ajax_refresh() {
		$this->do_ajax_refresh();
		exit;
	}

	/*
	ajax refresh for calculator
	*/

	function wp_ajax_calculator() {

		global $user_ID;

		/*
		load current user's details
		*/

		get_currentuserinfo();

		/*
		security check
		*/

		$pre_security_string=$this->get_prefix().'calculator'.$user_ID;

		check_ajax_referer($pre_security_string);

		$security_string=$this->get_prefix().'output'.str_replace(array('\n', "\n"), '', $_REQUEST['query_string']);
		$_ajax_nonce=wp_create_nonce($security_string);

		$_REQUEST['_ajax_nonce']=$_ajax_nonce;
		$_REQUEST['action']=$this->get_prefix().'output';

		$this->do_ajax_refresh(false);
		exit;
	}

	/*
	ajax refresh for calculator's ajax nonce
	*/

	function wp_ajax_calculator_ajax_nonce() {

		global $user_ID;

		/*
		load current user's details
		*/

		get_currentuserinfo();

		/*
		security check
		*/

		$security_string=$this->get_prefix().'calculator'.$user_ID;

		check_ajax_referer($security_string);

		/*
		prepare json string
		*/

		$json_params=array();

		/*
		return updated (2nd tick) _ajax_nonce
		*/

		$json_params['_ajax_nonce']=wp_create_nonce($security_string);

		$this->output_json($json_params);
		exit;
	}

	/*
	Calculator Tools-Page
	*/

	function calculator_page_timezones_update() {

		global $user_ID;

		/*
		load current user's details
		*/

		get_currentuserinfo();

		/*
		security check
		*/

		if (!current_user_can($this->get_option('calculator_capability')))
			wp_die(__('You do not have sufficient permissions to display this page.'));

		/*
		we handle a save call
		*/

		if (isset($_REQUEST[$this->get_prefix(false)]) && is_array($_REQUEST[$this->get_prefix(false)])) {

			/*
			security check
			*/

			$adminurl = strtolower(admin_url());
			$referer = strtolower(wp_get_referer());

			$result = isset($_REQUEST['_wpnonce']) ? wp_verify_nonce($_REQUEST['_wpnonce'], $this->get_prefix().'set_user_timezones'.$user_ID) : false;

			if (!$result && !(-1 == $action && strpos($referer, $adminurl) !== false)) {
				echo('<div class="error"><p><strong>'.__('Please try again.').'</strong></p></div>');

				return;
			}

			$input=$_REQUEST[$this->get_prefix(false)];

			/*
			reset -> use global timezones
			*/

			if (array_key_exists('reset', $input)) {
				update_user_option($user_ID, $this->get_prefix().'timezones', array());
			}

			/*
			handle a save call
			*/

			else if (array_key_exists('timezones', $input)) {
				$temp_timezones=explode("\n", $input['timezones']);

				/*
				did we receive a results array?
				*/

				if (is_array($temp_timezones)) {
					$input['timezones']=array();

					/*
					we don't need empty lines
					*/

					foreach ($temp_timezones as $timezone) {
						if (strlen($timezone)>1)
							array_push($input['timezones'], trim($timezone));
					}

				update_user_option($user_ID, $this->get_prefix().'timezones', $input['timezones']);
				}
			}

			/*
			dirty patch, because
			WordPress does not update the
			user's cached options,
			so if we reload the page once, we
			'force the cache to be refreshed'
			*/

			$redirect_args=array(
				'page' => $this->get_calculator_tools_page(),
				'updated' => 'true'
			);

			wp_redirect(add_query_arg($redirect_args, admin_url('tools.php')));

			exit;
		}
	}

	/*
	Calculator Tools-Page
	*/

	function calculator_page() {

		/*
		security check
		*/

		if (!current_user_can($this->get_option('calculator_capability')))
			wp_die(__('You do not have sufficient permissions to display this page.'));

		/*
		handle a save call
		*/

		if (isset($_GET['updated']) && $_GET['updated']) { ?>
			<div class="updated"><p><strong>Timezones updated!</strong></p></div>
		<?php }

		$this->settings_page($this->calculator_page_sections, $this->get_option('calculator_capability'), 'calculator', false);
	}

	/*
	Calculator World-Clock-Page
	*/

	function world_clock_page() {
		?><div class="wrap">

		<?php if (function_exists('screen_icon')) screen_icon();

		if ($this->get_option('world_clock_tools_page') && current_user_can($this->get_option('world_clock_tools_page_capability'))) {
			echo('<h2>World Clock</h2><br />');
			$this->display_world_clock('world_clock_tools_page');
		}

		?></div><?php
	}

	/*
	Options Page
	*/

	function options_page() {
		$this->settings_page($this->options_page_sections, 'manage_options', 'settings', true);
	}

	/*
	white list options
	*/

	function admin_init() {

		/*
		if on calculator-page handle
		user's timezones-update
		*/

		if (isset($_REQUEST['page']) && !empty($_REQUEST['page']) && $_REQUEST['page']==$this->get_calculator_tools_page())
			$this->calculator_page_timezones_update();

		/*
		settings
		*/

		register_setting($this->get_prefix(false), $this->get_prefix(false), array(&$this, 'settings_validate'));

		$this->add_settings_sections($this->options_page_sections, 'settings');
		$this->add_settings_sections($this->calculator_page_sections, 'calculator');
	}

	/*
	add TimeZoneCalculator to WordPress Settings Menu
	and create submenus in tools.php

	we use the hook admin_head instead of admin_print_styles
	because otherwise the CSS-background for disabled
	input fields does not work
	*/

	function admin_menu() {

		/*
		options page
		*/

		$options_page=add_options_page($this->get_nicename(), $this->get_nicename(), 'manage_options', $this->get_prefix(false), array(&$this, 'options_page'));

		add_action('admin_print_scripts-'.$options_page, array(&$this, 'settings_print_scripts'));
		add_action('admin_head-'.$options_page, array(&$this, 'admin_styles'));
		add_contextual_help($options_page, $this->options_page_help());

		/*
		calculator tools page
		*/

		$calculator_page=add_management_page($this->get_nicename(), $this->get_nicename(), $this->get_option('calculator_capability'), $this->get_calculator_tools_page(), array(&$this, 'calculator_page'));

		add_action('admin_print_scripts-'.$calculator_page, array(&$this, 'calculator_print_scripts'));
		add_action('admin_print_scripts-'.$calculator_page, array(&$this, 'refresh_print_scripts'));
		add_action('admin_print_styles-'.$calculator_page, array(&$this, 'calculator_print_styles'));
		add_action('admin_head-'.$calculator_page, array(&$this, 'admin_styles'));
		add_contextual_help($calculator_page, $this->calculator_page_help());

		/*
		world clock tools page
		*/

		if ($this->get_option('world_clock_tools_page')) {
			$world_clock_page=add_management_page('World Clock', 'World Clock', $this->get_option('world_clock_tools_page_capability'), $this->get_world_clock_tools_page(), array(&$this, 'world_clock_page'));

			add_action('admin_head-'.$world_clock_page, array(&$this, 'admin_styles'));
		}
	}

	/*
	adds meta-information to HTML header
	*/

	function head_meta() {
		echo("<meta name=\"".$this->get_nicename()."\" content=\"2.10\"/>\n");
	}

	/*
	add dashboard widget
	*/

	function add_dashboard_widget() {
		if ($this->get_option('dashboard_widget') && current_user_can($this->get_option('dashboard_widget_capability')))
			wp_add_dashboard_widget($this->get_prefix().'dashboard_widget', $this->get_nicename(), array(&$this, 'dashboard_widget_output'));
	}

	/*
	dashboard widget
	*/

	function dashboard_widget_output() {

		/*
		security check
		*/

		if (!current_user_can($this->get_option('dashboard_widget_capability')))
			return;

		$this->current_timezones_block('dashboard_widget');
	}

	/*
	add timezones to dashboard's right now box
	*/

	function add_right_now_box() {

		if ($this->get_option('dashboard_right_now') && current_user_can($this->get_option('dashboard_right_now_capability'))) {
			echo('<p></p>');

			$this->current_timezones_block('dashboard_right_now');
		}
	}

	/*
	adds some CSS to format TimeZoneCalculator on the dashboard
	*/

	function add_dashboard_widget_css() {
		if (($this->get_option('dashboard_widget') && current_user_can($this->get_option('dashboard_widget_capability'))) || ($this->get_option('dashboard_right_now') && current_user_can($this->get_option('dashboard_right_now_capability')))) { ?>

		<style type="text/css">
			.<?php echo($this->get_prefix(false)); ?>-refreshable-output, .<?php echo($this->get_prefix(false)); ?>-output {
				font-size:11px;
				line-height:140%;
			}
		</style>

		<?php }

	}

	/*
	embed world clock in user profile
	*/

	function show_user_profile($profileuser) {
		if ($this->get_option('include_world_clock_user_profile')) {
			echo('<h3>World Clock</h3>');
			$this->display_world_clock('user_profile', $profileuser->ID);
		}
	}

	/*
	embed local wordpress clock with
	absolute position in admin header
	*/

	function admin_head_clock() {
		if ($this->get_option('include_wordpress_clock_admin_head')) {
			$this->inline_wordpress_clock('admin_head_clock');
		}
	}

	/*
	called from widget_init hook
	*/

	function widgets_init() {
		register_widget('WP_Widget_'.$this->get_nicename());
	}

	/*
	called from init hook
	*/

	function init() {
		load_textdomain('continents-cities', WP_LANG_DIR . '/continents-cities-' . get_locale() . '.mo');
	}

	/*
	adds the javascript code for
	re-occuring timezone-updates
	*/

	function refresh_print_scripts() {
		wp_enqueue_script($this->get_prefix().'refresh');

		$security_string=$this->get_prefix().'output';
		$_ajax_nonce=wp_create_nonce($security_string);

		wp_localize_script($this->get_prefix().'refresh', $this->get_prefix().'refresh_settings', array('ajax_url' => admin_url('admin-ajax.php'),
'_ajax_nonce' => $_ajax_nonce, 'refresh_time' => $this->get_option('ajax_refresh_time')));
	}

	/*
	loads the necessary java-scripts
	for the options-page
	*/

	function settings_print_scripts() {
		wp_enqueue_script($this->get_prefix().'drag_and_drop');
		wp_enqueue_script($this->get_prefix().'settings_page');

		wp_localize_script($this->get_prefix().'drag_and_drop', $this->get_prefix().'settings', array('plugin_url' => $this->get_plugin_url()));

		$this->timezone_js_arrays();
	}

	/*
	loads the necessary java-scripts
	for the calculator-page
	*/

	function calculator_print_scripts() {

		/*
		we also need the libraries of options-page
		*/

		$this->settings_print_scripts();

		wp_enqueue_script($this->get_prefix().'calculator');

		global $user_ID;

		/*
		load current user's details
		*/

		get_currentuserinfo();

		$security_string=$this->get_prefix().'calculator'.$user_ID;
		$_ajax_nonce=wp_create_nonce($security_string);

		$unfiltered_params=array(
			'format_timezone' => urlencode($this->fallback_defaults['format_timezone']),
			'format_datetime' => urlencode($this->get_default('format_datetime')),
			'before_list' => urlencode($this->fallback_defaults['before_list']),
			'after_list' => urlencode($this->fallback_defaults['after_list'])
		);

		$filtered_params=apply_filters($this->get_prefix().'calculator', $unfiltered_params);

		$params=array(
			'_ajax_nonce' => $_ajax_nonce,
			'refresh_nonce' => $this->get_option('renew_nonce')
		);

		$params=array_merge($filtered_params, $params);

		wp_localize_script($this->get_prefix().'calculator', $this->get_prefix().'calculator_settings', $params);
	}

	/*
	loads the necessary css-styles
	for the calculator-page
	*/

	function calculator_print_styles() {
		wp_enqueue_style($this->get_prefix().'datepicker');
	}

	/*
	adds a settings and a calculator link
	in the plugin-tab
	*/

	function plugin_action_links($links, $file) {
		if ($file == plugin_basename(__FILE__)) {
			$links[] = '<a href="options-general.php?page='.$this->get_prefix(false).'">' . __('Settings') . '</a>';
			$links[] = '<a href="tools.php?page='.$this->get_calculator_tools_page().'">' . __('Calculator') . '</a>';
		}

		return $links;
	}

	/*
	loads the necessary CSS-styles
	for the admin-page
	*/

	function admin_styles() {
		$admin_css_colors=$this->get_admin_colors(); ?>

		<style type="text/css">

			.<?php echo($this->get_prefix()); ?>wrap ul {
				list-style-type : disc;
				padding: 5px 5px 5px 30px;
			}

			ul.subsubsub.<?php echo($this->get_prefix(false)); ?> {
				list-style: none;
				margin: 8px 0 5px;
				padding: 0;
				white-space: nowrap;
				float: none;
				display: block;
			}
 
			ul.subsubsub.<?php echo($this->get_prefix(false)); ?> a {
				line-height: 2;
				padding: .2em;
				text-decoration: none;
			}

			ul.subsubsub.<?php echo($this->get_prefix(false)); ?> li {
				display: inline;
				margin: 0;
				padding: 0;
				border-left: 1px solid #ccc;
				padding: 0 .5em;
			}

			ul.subsubsub.<?php echo($this->get_prefix(false)); ?> li:first-child {
				padding-left: 0;
				border-left: none;
			}

			li.<?php echo($this->get_prefix()); ?>sortablelist {
				background-color: <?php echo $admin_css_colors[1]; ?>;
				color: <?php echo $admin_css_colors[3]; ?>;
				cursor: move;
				padding: 3px 5px 3px 5px;
				-moz-border-radius: 6px;
			}

			ul.<?php echo($this->get_prefix()); ?>sortablelist {
				float: left;
				border: 1px <?php echo $admin_css_colors[0]; ?> solid;
				list-style-image : none;
				list-style-type : none;
				margin: 0px 20px 20px 0px;
				padding: 10px;
				-moz-border-radius: 6px;
			}

			#<?php echo($this->get_prefix()); ?>edit, #<?php echo($this->get_prefix()); ?>search {
				float: right;
				border: 1px solid;
				margin: 0px 20px 0px 0px;
				width: 400px;
				padding: 5px;
				-moz-border-radius: 6px;
			}

			#<?php echo($this->get_prefix()); ?>edit_header {
				background-color: <?php echo $admin_css_colors[1]; ?>;
				color: <?php echo $admin_css_colors[3]; ?>;
				padding: 3px 3px 3px 5px;
				-moz-border-radius: 6px;
			}

			#<?php echo($this->get_prefix()); ?>edit_submit {
				margin: 10px 0px 5px 0px;
			}			

			img.<?php echo($this->get_prefix()); ?>arrowbutton {
				vertical-align: bottom;
				cursor: pointer;
				margin-left: 5px;
			}

			input[disabled], input[disabled='disabled'] {
				background: #EEE;
			}

		</style>
	<?php }

	/*
	LOGIC FUNCTIONS
	*/

	/*
	internal function of output
	*/

	private function _output($params=array()) {

		/*
		log call
		*/

		$this->log('function _output, $params='.var_export($params, true));

		/*
		fill params with default-values
		*/

		$params=$this->fill_default_parameters($params);

		/*
		use user's timezones?
		*/

		global $user_ID;

		/*
		load current user's details
		*/

		get_currentuserinfo();

		/*
		allow to query for a
		user's timezones-selection
		*/

		if (is_user_logged_in() && $params['prefer_user_timezones']) {

			/*
			if user_id has not been set,
			we use the user who is
			currently logged in
			*/

			if (!isset($params['user_id']) || empty($params['user_id']))
				$params['user_id']=$user_ID;

			/*
			if a user tries to
			view the timezones of
			another user, we conduct
			a security check
			*/

			if ($params['user_id']!=$user_ID && !current_user_can($this->get_option('view_other_users_timezones_capability')))
				throw new Exception('You are not authorized to view another user\'s timezones!');

			/*
			validate user_id
			*/

			if (!$this->is_integer($params['user_id']) || $params['user_id']<1 || !get_userdata($params['user_id']))
				throw new Exception('user_id '.$params['user_id'].' does not exist');

			$maybe_timezones=get_user_option($this->get_prefix().'timezones', $params['user_id']);

			if (!empty($maybe_timezones) && is_array($maybe_timezones)) {
				$params['timezones']=$maybe_timezones;

				$this->log('use timezones-selection of user '.$params['user_id']);
				}
		}

		/*
		convert query type string to array
		*/

		if (!array_key_exists('timezones', $params) || empty($params['timezones']) || !is_array($params['timezones'])) {
			$maybe_timezones=str_replace("\r", '', $params['timezones']);

			$maybe_timezones=explode("\n", $maybe_timezones);

			if (!empty($maybe_timezones) && is_array($maybe_timezones))
				$params['timezones']=$maybe_timezones;
			else
				$params['timezones']=$this->get_default('timezones');
		}

		/*
		log call
		*/

		$this->log('function _output, merged with defaults, $params='.var_export($params, true));

		$ret_val='';

		$refreshable=$this->is_output_refreshable($params);

		/*
		set block-id
		*/

		if (!isset($params['id']))
			$params['id']=$this->get_block_count();

		/*
		shall we wrap the output in a container?
		*/

		if ($params['use_container']) {
			$ret_val.='<div ';

			if (!$refreshable)
				$ret_val.='id="'.$this->get_prefix().'block_'.$params['id'].'" ';

			$ret_val.='class="'.$this->get_prefix(false).'-';

			if ($refreshable)
				$ret_val.='refreshable-';

			$ret_val.='output"';

			if (!empty($params['format_container']))
				$ret_val.=' style="'.$params['format_container'].'"';

			$ret_val.='>';
		}

		/*
		if query_time has not been set
		we use the current unix timestamp in utc instead
		*/

		if (!isset($params['query_time']) || empty($params['query_time']))
			$params['query_time']=TIMEZONECALCULATOR_CURRENTGMDATE;

		/*
		use a handed over timestamp
		or datetime-string
		*/

		else {

			/*
			calculate unix timestamp
			with parameters
			*/

			$params['query_time']=$this->calculate_date($params['query_time'], $params['query_timezone']);

			if ($params['query_time']===false)
				throw new Exception('Either the query_time or the query_timezone are not correct!');

			/*
			we don't need
			the query_timezone anymore
			*/

			if (isset($params['query_timezone']))
				unset($params['query_timezone']);
		}

		$ret_val.=$params['before_list'];

		/*
		process timezone-entries
		*/

		foreach ($params['timezones'] as $timezone) {

			/*
			is there anything to parse
			in the particular line?
			*/

			if (strlen($timezone)>1) {

				/*
				calculate and format timezone-entry
				*/

				try {
					$datetimezone=new TimeZoneCalculator_DateTimeZone($timezone, $params['query_time']);
					$ret_val.=$datetimezone->format_timezone($params);
				}
				catch(Exception $e) {
					$this->log($e->getMessage());
					$ret_val.=str_replace(array('%name', '%abbreviation', '%datetime'), array('', '', $e->getMessage()), $params['format_timezone']);
				}
			}
		}

		$ret_val.=$params['after_list'];

		if ($params['use_container'])
			$ret_val.='</div>';

		/*
		produce js refresh code
		*/

		if ($this->get_option('use_ajax_refresh') && $params['use_container'] && !$refreshable && !isset($params['no_refresh']) && $params['query_time']==TIMEZONECALCULATOR_CURRENTGMDATE) {

			/*
			allowed query params for ajax refresh call
			*/

			$refresh_query_params=array(
				'id',
				'prefer_user_timezones',
				'user_id',
				'before_list',
				'after_list',
				'format_timezone',
				'format_datetime',
				'format_container'
			);

			$query_string='';

			/*
			build ajax query-string
			*/

			foreach ($params as $key => $param)
				if (in_array($key, $refresh_query_params))
					$query_string.=$key.'='.urlencode($param).'&';

			$query_string.='timezones=';

			foreach ($params['timezones'] as $timezone)
				$query_string.=urlencode($timezone).'\\n';

			$query_string=substr($query_string, 0, -2);

			?>

			<script type="text/javascript">

			/* <![CDATA[ */

			var <?php echo($this->get_prefix()); ?>params_<?php echo($params['id']); ?> = <?php echo($this->get_prefix()); ?>refresh_create_params('<?php echo($this->get_prefix()); ?>block_<?php echo($params['id']); ?>', '<div id="<?php echo($this->get_prefix().'block_'.$params['id']); ?>" class="<?php echo($this->get_prefix(false)); ?>-output"');

			<?php
			$security_string=$this->get_prefix().'output'.str_replace(array('\n', "\n"), '', $query_string);
			$_ajax_nonce=wp_create_nonce($security_string);
			?>

			var <?php echo($this->get_prefix()); ?>query_params_<?php echo($params['id']); ?> = <?php echo($this->get_prefix()); ?>refresh_create_query_params_output('<?php echo($_ajax_nonce); ?>', '<?php echo($query_string); ?>');

			<?php echo($this->get_prefix()); ?>initiate_refresh(<?php echo($this->get_prefix()); ?>params_<?php echo($params['id']); ?>, <?php echo($this->get_prefix()); ?>query_params_<?php echo($params['id']); ?>);

			/* ]]> */

			</script>

		<?php }

		$this->increment_block_count();

		/*
		echo results
		*/

		if ($params['display'])
			echo($ret_val);

		/*
		return results
		*/

		else
			return $ret_val;
	}

	/*
	produce an inline clock

	CSS inspired by "Hello Dolly" plugin
	*/

	private function inline_wordpress_clock($filter) {

		/*
		This makes sure that the
		positioning is also good for
		right-to-left languages
		*/

		$x='right';

		if (function_exists('is_rtl'))
			$x = (is_rtl()) ? 'left' : 'right';

		$admin_css_colors=$this->get_admin_colors();		
		$unfiltered_style="position:absolute;padding:3px 5px;top:1.15em;$x:400px;font-size:11px;background-color:$admin_css_colors[1];color:$admin_css_colors[3];-moz-border-radius:8px;line-height:15px";

		$filtered_style=apply_filters($this->get_prefix().$filter.'_format_container', $unfiltered_style);

		$unfiltered_params=array(
			'timezones' => array(
				'Local_WordPress_Time'
			),

			'prefer_user_timezones' => false,

			'before_list' => '',
			'after_list' => '',
			'format_timezone' => '<span title="%name">%datetime</span> <abbr title="%name">%abbreviation</abbr>'
		);

		$filtered_params=apply_filters($this->get_prefix().$filter, $unfiltered_params);

		$params=array(
			'use_container' => true,
			'format_container' => $filtered_style,
			'display' => true
		);

		$this->output(array_merge($filtered_params, $params));
	}

	/*
	display world-clock
	*/

	private function display_world_clock($filter, $user_id=null) {
		$unfiltered_params=array(
			'user_id' => $user_id,
			'before_list' => '<ul class="'.$this->get_prefix().'sortablelist">',
			'after_list' => '</ul>',
			'format_timezone' => '<li style="cursor:default" class="'.$this->get_prefix().'sortablelist"><abbr title="%name">%abbreviation</abbr>: <span title="%name">%datetime</span></li>'
		);

		$filtered_params=apply_filters($this->get_prefix().$filter, $unfiltered_params);

		$params=array(
			'use_container' => true,
			'display' => true,
			'prefer_user_timezones' => true
		);

		$this->output(array_merge($filtered_params, $params));

		if (current_user_can($this->get_option('calculator_capability')))
				echo('<br style="clear:both" />You can customize your timezones in the <a href="tools.php?page='.$this->get_calculator_tools_page().'">'.$this->get_nicename().'</a>.');
	}

	/*
	checks if params lead to refreshable div
	check if timezones and defaults
	are same as selected in option page
	*/

	private function is_output_refreshable($params) {

		/*
		if a timestamp has been set
		the output is not automatically refreshable
		*/

		if (isset($params['query_time']) && !empty($params['query_time']))
			return false;

		/*
		if a id has been set
		the output is not automatically refreshable
		*/

		if (isset($params['id']) && strlen($params['id'])>0)
			return false;

		/*
		check for different timezones setting
		*/

		if (array_key_exists('timezones', $params)) {

			$default_timezones=$this->get_default('timezones');
			$params_timezones=$params['timezones'];

			/*
			do the arrays differ in size?
			*/

			if (sizeof($default_timezones)!=sizeof($params_timezones)) {
				$this->log('found different timezones setting - disabling refreshable');
				return false;
			}

			/*
			compare order of timezones of
			global defaults
			and function call timezones
			*/

			for($i=0; $i<sizeof($default_timezones); $i++) {
				if ($default_timezones[$i]!=$params_timezones[$i]) {
					$this->log('found different timezones  setting or timezones order - disabling refreshable');
					return false;
				}
			}

		}

		/*
		every set fallback-default
		which is not
		'display' or 'use_container'
		will trigger a new creation of
		the timezones block
		*/

		$trigger_params=$params;

		unset($trigger_params['display']);
		unset($trigger_params['use_container']);

		foreach($trigger_params as $key => $value) {
			if (array_key_exists($key, $this->defaults) && $value!=$this->get_default($key)) {
				$this->log('found trigger option '.$key. ' - disabling refreshable');
				return false;
			}
		}

		return true;
	}

	/*
	calculates offset to UTC for
	given unix-timestamp and query_timezone
	*/

	private function calculate_utc_offset($local_time, $query_timezone) {
		$offset=0;

		/*
		WordPress TimeZones Support
		*/

		if ($query_timezone=='Local_WordPress_Time') {
			$query_timezone=get_option('timezone_string');
		}

		$timezone=@timezone_open($query_timezone);

		/*
		if timezone is available,
		lookup offset
		*/

		if (!$timezone)
			throw new Exception('timezone unknown');

		/*
		inspired from Derick's talk
		http://talks.php.net/show/time-ffm2006/28

		lookup array until
		current transition has been found
		*/

		foreach (timezone_transitions_get($timezone) as $tr) {
			if ($tr['ts'] > ($local_time-$offset)) {
				return $offset;
			}

			$offset=$tr['offset'];
		}

		return $offset;
	}

	/*
	output current timezones-block
	*/

	private function current_timezones_block($filter) {
		$filtered_params=apply_filters($this->get_prefix().$filter, array());

		$params=array(
			'use_container' => true,
			'display' => true
		);

		$this->output(array_merge($filtered_params, $params));
	}

	/*
	ADMIN MENU - UTILITY
	*/

	/*
	register settings sections and fields
	*/

	private function add_settings_sections($settings_sections, $section_prefix) {

		/*
		settings-sections
		*/

		foreach($settings_sections as $section_key => $section) {
			$this->add_settings_section($section_key, $section['nicename'], $section_prefix, $section['callback']);

			/*
			fields for each section
			*/

			if (array_key_exists('fields', $section)) {
				foreach ($section['fields'] as $field_key => $field) {
					$this->add_settings_field($field_key, $field, $section_key, $section_prefix);
				}
			}
		}
	}

	/*
	adds a settings section
	*/

	private function add_settings_section($section_key, $section_name, $section_prefix, $callback) {
		add_settings_section('default', $section_name, array(&$this, 'callback_'.$section_prefix.'_'.$callback), $this->get_prefix().$section_prefix.'_'.$section_key);
	}

	/*
	adds a settings field
	*/

	private function add_settings_field($field_key, $field_name, $section_key, $section_prefix, $label_for='') {
		if (empty($label_for))
			$label_for=$this->get_prefix().$field_key;

		add_settings_field($this->get_prefix().$field_key, $field_name, array(&$this, 'setting_'.$field_key), $this->get_prefix().$section_prefix.'_'.$section_key, 'default', array('label_for' => $label_for));
	}

	/*
	creates section link
	*/

	private function get_section_link($sections, $section, $section_nicename='', $create_id=false) {
		if (strlen($section_nicename)<1)
			$section_nicename=$sections[$section]['nicename'];

		$id='';
		if ($create_id)
			$id=' id="'.$this->get_prefix().$section.'_link"';

		$menuitem_onclick=" onclick=\"".$this->get_prefix()."open_section('".$section."');\"";

		return '<a'.$id.$menuitem_onclick.' href="javascript:void(0);">'.$section_nicename.'</a>';
	}

	/*
	returns name="timezonecalculator[setting]" id="timezonecalculator_setting"
	*/

	private function get_setting_name_and_id($setting) {
		return 'name="'.$this->get_prefix(false).'['.$setting.']" id="'.$this->get_prefix().$setting.'"';
	}

	/*
	returns default value for option-field
	*/

	private function get_setting_default_value($field, $type) {
		$default_value=null;

		if ($type=='options')
			$default_value=htmlentities($this->get_option($field), ENT_QUOTES, get_option('blog_charset'), false);
		else if ($type=='defaults')
			$default_value=htmlentities($this->get_default($field), ENT_QUOTES, get_option('blog_charset'), false);
		else
			throw new Exception('type '.$type.' does not exist for field '.$field.'!');

		return $default_value;
	}

	/*
	outputs a settings section
	*/

	private function do_settings_sections($section_key, $section_prefix) {
		do_settings_sections($this->get_prefix().$section_prefix.'_'.$section_key);
	}

	/*
	Settings Page
	*/

	private function settings_page($settings_sections, $permissions, $section_prefix, $is_wp_options) {

		/*
		security check
		*/

		if (!current_user_can($permissions))
			wp_die(__('You do not have sufficient permissions to display this page.'));

		/*
		option-page html
		*/

		?><div class="wrap">
		<?php if (function_exists('screen_icon')) screen_icon(); ?>
		<h2><?php echo($this->get_nicename()); ?></h2>

		<?php call_user_func(array(&$this, 'callback_'.$section_prefix.'_intro')); ?>

		<div id="<?php echo($this->get_prefix()); ?>menu" style="display:none"><ul class="subsubsub <?php echo($this->get_prefix(false)); ?>">
		<?php

		$menu='';

		foreach ($settings_sections as $key => $section)
			$menu.='<li>'.$this->get_section_link($settings_sections, $key, '', true).'</li>';

		echo($menu);
		?>
		</ul></div>

		<div class="<?php echo($this->get_prefix()); ?>wrap">

		<?php if ($is_wp_options) { ?>
			<form method="post" action="<?php echo(admin_url('options.php')); ?>">
			<?php settings_fields($this->get_prefix(false));
		}

		foreach ($settings_sections as $key => $section) {

		?><div id="<?php echo($this->get_prefix().$key); ?>"><?php

			$this->do_settings_sections($key, $section_prefix);
			echo('</div>');
		}

		?>

		<?php if ($is_wp_options) { ?>
			<p class="submit">
			<?php
			$submit_buttons=array(
				'submit' => 'Save Changes',
				'reset' => 'Use Defaults'
			);

			foreach ($submit_buttons as $key => $submit_button)
				$this->setting_submit_button($key, $submit_button);
			?>
			</p>
			</form>
		<?php } ?>

		<?php $this->neotrinity_support(); ?>

		</div>

		</div>

		<?php /*
		JAVASCRIPT
		*/ ?>

		<?php $this->settings_page_js($settings_sections); ?>

	<?php }

	/*
	settings pages's javascript
	*/

	private function settings_page_js($settings_sections) { ?>

	<script type="text/javascript">

	/* <![CDATA[ */

	/*
	section-divs
	*/

	var <?php echo($this->get_prefix()); ?>sections = [<?php

	$available_sections=array();

	foreach($settings_sections as $key => $section)
		array_push($available_sections, '"'.$key.'"');

	echo(implode(',', $available_sections));
	?>];

	var section=$('<?php echo($this->get_prefix()); ?>section').value;
	if (!section)
		section='';

	<?php echo($this->get_prefix()); ?>open_section(section);

	/*
	display js-menu
	if js has been disabled,
	the menu will not be visible
	*/

	$('<?php echo($this->get_prefix()); ?>menu').style.display="block";

	/* ]]> */

	</script>

	<?php }

	/*
	ADMIN MENU - COMPONENTS
	*/

	/*
	generic checkbox
	*/

	private function setting_checkfield($name, $type, $related_fields=array(), $js_checked=true) {

		$javascript_onclick_related_fields='';

		/*
		build javascript function
		to enable/disable related fields
		*/

		if (!empty($related_fields)) {

			/*
			prepare for javascript array
			*/

			foreach($related_fields as &$related_field)
				$related_field='\''.$related_field.'\'';

			/*
			build onclick-js-call
			*/

			$javascript_toggle=$this->get_prefix().'toggle_related_fields(';

			$javascript_fields=', ['.implode(', ', $related_fields).']';

			/*
			add to body onload events
			*/

			/*
			check for disabled fields
			on onload event
			*/

			?>

			<script type="text/javascript">

			/* <![CDATA[ */

			Event.observe(window, 'load', function(e){ <?php echo($javascript_toggle.'$(\''.$this->get_prefix().$name.'\')'.$javascript_fields. ', '.($js_checked == 1 ? '1' : '0').');'); ?> });

			/* ]]> */

			</script>

			<?php

			/*
			build trigger for settings_field
			*/

			$javascript_onclick_related_fields='onclick="'.$javascript_toggle.'this'.$javascript_fields. ', '.($js_checked == 1 ? '1' : '0').');"';
		}

		$checked=$this->get_setting_default_value($name, $type); ?>
		<input <?php echo($this->get_setting_name_and_id($name)); ?> type="checkbox" <?php echo($javascript_onclick_related_fields); ?> value="1" <?php checked('1', $checked); ?> />
	<?php }

	/*
	generic textinput
	*/

	private function setting_textfield($name, $type, $size=30, $javascript_validate='') {
		$default_value=$this->get_setting_default_value($name, $type); ?>
		<input type="text" <?php echo($this->get_setting_name_and_id($name).' '.$javascript_validate); ?> maxlength="<?php echo($size); ?>" size="<?php echo($size); ?>" value="<?php echo $default_value; ?>" />
	<?php }

	/*
	generic submit-button
	*/

	private function setting_submit_button($field_key, $button) { ?>
		<input type="submit" name="<?php echo($this->get_prefix(false)); ?>[<?php echo($field_key); ?>]" id="<?php echo($this->get_prefix(false)); ?>_<?php echo($field_key); ?>" class="button-primary" value="<?php _e($button) ?>" />
	<?php }

	/*
	generic capability select
	*/

	private function setting_capability($name, $type) {
		?><select <?php echo($this->get_setting_name_and_id($name.'_capability')); ?>>

			<?php
			$capabilities=$this->get_all_capabilities();

			$ret_val='';

			foreach ($capabilities as $capability) {
				$_selected = $capability == $this->get_setting_default_value($name.'_capability', $type) ? " selected='selected'" : '';
				$ret_val.="\t<option value='".$capability."'".$_selected.">" . $capability . "</option>\n";
			}

			echo $ret_val;
			?>

		</select><?php
	}

	/*
	generic continent + timezone select
	*/

	function setting_timezone($name, $selected_timezone='') {
		$attrs = explode('/', $selected_timezone);
		$selected_continent=$attrs[0]; ?>

		<select class="continent" name="<?php echo($name.'continent'); ?>" id="<?php echo($name.'continent'); ?>" disabled="disabled"><option value="Currently loading...">Currently loading...</option></select>
		<select class="timezone" name="<?php echo($name.'timezone'); ?>" id="<?php echo($name.'timezone'); ?>" disabled="disabled"><option value="Currently loading...">Currently loading...</option></select>

		<script type="text/javascript">

		/* <![CDATA[ */

		Event.observe(window, 'load', function(e){

			/*
			populate continents select on window-load
			*/

			Element.replace('<?php echo($name.'continent'); ?>', '<select class="continent" name="<?php echo($name.'continent'); ?>" id="<?php echo($name.'continent'); ?>">'+<?php echo($this->get_prefix()); ?>continents_array+'</select');

			<?php
			if (!empty($selected_continent)) { ?>

				/*
				load selected continent
				*/

				<?php echo($this->get_prefix()); ?>select_value_in_select($('<?php echo($name.'continent'); ?>'), '<?php echo($selected_continent); ?>');
			<?php } ?>

			/*
			populate timezones-select
			according to continents-select
			*/

			<?php echo($this->get_prefix()); ?>set_timezone_array('<?php echo($name); ?>');

			<?php
			if (!empty($selected_timezone)) { ?>

			/*
			load selected timezone
			*/

			<?php echo($this->get_prefix()); ?>select_value_in_select($('<?php echo($name.'timezone'); ?>'), '<?php echo($selected_timezone); ?>');
			<?php } ?>

			/*
			all changes in the continent-select
			trigger a reload of the related
			timezones-select
			*/

			Event.observe('<?php echo($name.'continent'); ?>', 'change', function(e){ <?php echo($this->get_prefix()); ?>set_timezone_array('<?php echo($name); ?>'); });
		});

		/* ]]> */

		</script>

	<?php }

	/*
	generic date (with date-picker) select
	*/

	function setting_date($name, $selected_date=false, $formats=array('Y-m-d', 'm/d/y')) {
		$allowed_formats = array(
				'Y-m-d',
				'd-m-Y',
				'Y/m/d',
				'm/d/Y',
				'd.m.Y'
		);

		$default_formats=array(
			'Y-m-d',
			'm/d/y'
		);

		$checked_formats=array();

		/*
		verify formats
		*/

		if (is_array($formats)) foreach($formats as $format) {
			foreach($allowed_formats as $allowed_format) {
				if (strpos($format, $allowed_format)!==false) {
					array_push($checked_formats, $allowed_format);
					break;
				}
			}
		}

		/*
		could not parse any handed-over formats
		*/

		if (empty($checked_formats))
			$checked_formats=$default_formats;

		/*
		localize date-format
		for date-picker
		*/

		$orig_format=array('-', '/', '.');
		$replace_format=array('-ds-', '-sl-', '-dt-');

		/*
		convert php-datetime-strings to
		datepicker string
		*/

		$converted_formats=array();

		foreach($checked_formats as $checked_format) {
			array_push($converted_formats, str_replace($orig_format, $replace_format, $checked_format));
		}

		?>

		<input type="text" name="<?php echo($name.'date'); ?>" id="<?php echo($name.'date'); ?>" title="<?php echo(date_i18n($checked_formats[0], current_time('timestamp'), true)); ?>, tomorrow 3pm, ..." onfocus="this.focus(); this.select();" size="20" maxlength="25" value="<?php if(!empty($selected_date)) echo($selected_date); ?>" />

		<script type="text/javascript">

		/* <![CDATA[ */

		/*
		DatePicker v5.4 by frequency-decoder.com
		http://www.frequency-decoder.com/2009/09/09/unobtrusive-date-picker-widget-v5
		*/

		var opts = {
			formElements:{"<?php echo($name.'date'); ?>":"<?php echo($converted_formats[0]); ?>"},
			highlightDays:[0,0,0,0,0,1,1],
			fillGrid:true,
			rangeLow:"19300101",
			rangeHigh:"20371231",
			constrainSelection:false<?php if (sizeof($converted_formats)>1) { ?>,
			dateFormats:{"<?php echo($name.'date'); ?>":[<?php

				$alternative_formats=array_slice($converted_formats, 1);

				$ret_val='';

				foreach($alternative_formats as $alternative_format) {
					$ret_val.='"'.$alternative_format.'",';
				}

				$ret_val=substr($ret_val, 0, -1);

				echo($ret_val); ?>]}<?php } ?>

		};

		datePickerController.createDatePicker(opts);

		<?php
		if ($selected_date===false)
			echo($this->get_prefix().'calculator_set_default_date();');
		?>

		/* ]]> */

		</script>
	<?php }

	/*
	generic time (hour and minute) select
	*/

	function setting_time($name, $selected_hour='', $selected_minute='', $format='H:i') {

		/*
		round selected_minute to the nearest
		multiple of 5
		*/

		if (!empty($selected_minute) && $this->is_integer($selected_minute) && $selected_minute>-1)
			$selected_minute=$this->round_to_nearest($selected_minute, 5);

		/*
		to avoid to much binding
		we cheat a little bit here
		because 60 minutes would actually
		mean that we would have to also
		change the hour and maybe the date
		*/

		if ($selected_minute==60)
			$selected_minute=55;

		$format_12_hours=false;

		/*
		look for 'a' in format
		a=am/pm A=AM/PM
		*/

		if (stripos($format, 'a')!==false)
			$format_12_hours=true;

		/*
		make hour select
		*/

		echo('<select class="clock" name="'.$name.'hour" id="'.$name.'hour"><option value="?">?</option>');

		for ($i=0;$i<24;$i++) {
			$hour_string='';

			/*
			24-hours-string
			*/

			$hour_string_24=zeroise($i, 2);

			/*
			12-hours-string
			*/

			if ($i==0)
				$hour_string_12='12 am';
			elseif ($i==12)
				$hour_string_12='12 pm';
			elseif ($i<12)
				$hour_string_12=zeroise($i, 2).' am';
			else
				$hour_string_12=zeroise($i-12, 2).' pm';

			/*
			priorize chosen time-format
			*/

			if ($format_12_hours)
				$hour_string=$hour_string_12.' ('.$hour_string_24.')';
			else
				$hour_string=$hour_string_24.' ('.$hour_string_12.')';

			$selected='';
			if (zeroise($i, 2)==$selected_hour)
				$selected='selected="selected" ';

			echo('<option '.$selected.'value="'.zeroise($i, 2).'">'.$hour_string.'</option>');
		}

		echo('</select> : ');

		/*
		make minute select
		*/

		echo('<select class="clock" name="'.$name.'minute" id="'.$name.'minute"><option value="?">?</option>');

		for ($i=0;$i<60;$i=$i+5) {
			$minute=zeroise($i, 2);

			$selected='';
			if ($minute==$selected_minute)
				$selected='selected="selected" ';

			echo('<option '.$selected.'value="'.$minute.'">'.$minute.'</option>');
		}

		echo('</select>');
	}

	/*
	outputs support paragraph
	*/

	private function neotrinity_support() { ?>
		<h3>Support</h3>
		If you like to support the development of <?php echo($this->get_nicename()); ?>, you can invite me for a <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&amp;business=bernhard%40riedl%2ename&amp;item_name=Donation%20for%20TimeZoneCalculator&amp;no_shipping=1&amp;no_note=1&amp;tax=0&amp;currency_code=EUR&amp;bn=PP%2dDonationsBF&amp;charset=UTF%2d8">virtual pizza</a> for my work. <?php echo(convert_smilies(':)')); ?><br /><br />

		<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_xclick" /><input type="hidden" name="business" value="&#110;&#101;&#111;&#64;&#x6E;&#x65;&#x6F;&#x74;&#x72;&#105;&#110;&#x69;&#x74;&#x79;&#x2E;&#x61;t" /><input type="hidden" name="item_name" value="Donation for TimeZoneCalculator" /><input type="hidden" name="no_shipping" value="2" /><input type="hidden" name="no_note" value="1" /><input type="hidden" name="currency_code" value="EUR" /><input type="hidden" name="tax" value="0" /><input type="hidden" name="bn" value="PP-DonationsBF" /><input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" style="border:0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" /><img alt="if you like to, you can support me" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" /></form><br />

		Maybe you also want to <?php if (current_user_can('manage_links')) { ?><a href="link-add.php"><?php } ?>add a link<?php if (current_user_can('manage_links')) { ?></a><?php } ?> to <a href="http://www.neotrinity.at/projects/">http://www.neotrinity.at/projects/</a>.<?php if(strpos($_SERVER['HTTP_HOST'], 'journeycalculator.com')===false) { ?><br /><br />

		Fancy on timezones-calculation? - Try the free <a target="_blank" href="http://www.journeycalculator.com/">JourneyCalculator</a>...<?php } ?>
<br /><br />
	<?php }

	/*
	ADMIN MENU - SECTIONS + HELP
	*/

	/*
	intro callback
	*/

	function callback_settings_intro() { ?>
		Welcome to the Settings-Page of <a target="_blank" href="http://www.neotrinity.at/projects/"><?php echo($this->get_nicename()); ?></a>. This plugin calculates, displays and automatically updates times and dates in different timezones with respect to daylight saving.
	<?php }

	/*
	adds help-text to admin-menu contextual help
	*/

	function options_page_help() {
		return "<div class=\"".$this->get_prefix()."wrap\"><ul>

			<li>You can insert new or edit your existing timezone-entries in the ".$this->get_section_link($this->options_page_sections, 'drag_and_drop', 'Drag and Drop Layout Section')." or in the ".$this->get_section_link($this->options_page_sections, 'expert', 'Expert Section').". Latter section also works without the usage of Javascript. In any way, new entries are only saved after clicking on <strong>Save Changes</strong>.</li>

			<li>Style-customizations can be made in the ".$this->get_section_link($this->options_page_sections, 'format', 'Format Section').".</li>

			<li>You can activate an optional ".$this->get_section_link($this->options_page_sections, 'ajax_refresh', 'Ajax refresh for automatical updates ')." of your timezones-output.</li>

			<li>Before you publish the results you can use the ".$this->get_section_link($this->options_page_sections, 'preview', 'Preview Section').".</li>

			<li>Finally, you can publish the previously selected and saved timezones either by adding a <a href=\"widgets.php\">Sidebar Widget</a>, ".$this->get_section_link($this->options_page_sections, 'dashboard', 'Dashboard Widget')." or by enabling the ".$this->get_section_link($this->options_page_sections, 'world_clock', 'World Clock').". Moreover you can display a clock with your WordPress timezone in the header of the ".$this->get_section_link($this->options_page_sections, 'administrative_options', 'Admin Menu').".</li>

			<li><a target=\"_blank\" href=\"http://wordpress.org/extend/plugins/timezonecalculator/other_notes/\">Geek stuff</a>: You can output your timezones-selection by calling the <abbr title=\"PHP: Hypertext Preprocessor\">PHP</abbr> function <code>$".$this->get_prefix(false)."->output(\$params)</code> wherever you like (don't forget <code>global $".$this->get_prefix(false)."</code>). This function can also be invoked by the usage of a shortcode.</li>

			<li>You can enable the <a href=\"tools.php?page=".$this->get_calculator_tools_page()."\">TimeZone-Calculator</a> for your users or just for yourself in the ".$this->get_section_link($this->options_page_sections, 'calculator', 'Calculator Section').", where you can calculate a certain timestamp in your selected timezones.</li>

			<li>If you decide to uninstall ".$this->get_nicename().", firstly remove the optionally added <a href=\"widgets.php\">Sidebar Widget</a>, integrated <abbr title=\"PHP: Hypertext Preprocessor\">PHP</abbr> function or WordPress shortcode call(s). Afterwards, disable and delete ".$this->get_nicename()." in the <a href=\"plugins.php\">Plugins Tab</a>.</li>

			<li><strong>For more information:</strong><br /><a target=\"_blank\" href=\"http://wordpress.org/extend/plugins/".str_replace('_', '-', $this->get_prefix(false))."/\">".$this->get_nicename()." in the WordPress Plugin Directory</a></li>

		</ul></div>";
	}

	/*
	section drag_and_drop
	*/

	function callback_settings_drag_and_drop() {
		$this->do_settings_drag_and_drop();
	}

	/*
	function drag_and_drop
	*/

	function do_settings_drag_and_drop($timezones_array=array()) { ?>
		<ul>

			<li>You can insert new timezones by filling  out the form on the right and clicking <strong>Insert</strong>.</li>

			<li>To customize existing timezones click on the entry you want to change and edit the parameters in the form on the right. If you want to adjust the details of a timezone entry, you have to click on the arrow in the edit form. This will open the advanced menu where you can either select to use the abbreviations and names of the timezone database, or manually insert your own descriptions. After clicking <strong>Edit</strong> the selected timezone's parameters will be adopted.</li>

			<li>To re-order the timezones within a list either use drag and drop or click on the arrows on the left side of the particular timezone.</li>

			<li>To remove timezones from the list just drag and drop them into the Garbage Bin.</li>

			<li>Don't forget to save all your adjustments by clicking on <strong>Save Changes</strong>.</li>

			<li>Hint: Use the right form on the bottom of the page to find information about cities and their timezones.</li>

			<?php
			$wordpress_timezone=get_option('timezone_string');

			if (empty($wordpress_timezone) && current_user_can('manage_options'))
				echo('<li>To include the Local WordPress TimeZone in the timezones-select, set your timezone in the <a href="options-general.php">options</a></li>');
			?>

		</ul><?php

		/*
		check timezones_array
		*/

		if (!is_array($timezones_array) || empty($timezones_array))
			$timezones_array=$this->get_default('timezones');

		$list_selected='';
		$before_tag='<li class="'.$this->get_prefix().'sortablelist" id=';
		$after_tag='</li>';

		/*
		build list
		*/

		$before_key=$this->get_prefix().'timezone_';

		$counter=0;

		$list_selected_listeners='';

		/*
		loop through all timezone-entries
		*/

		foreach ($timezones_array as $timezone) {

			if (!empty($timezone)) {
				$timezone_attributes=explode(';', $timezone);
				$tag='';
				$otherOptions='';

				if (sizeof($timezone_attributes)>0) {
					$tag=$timezone_attributes[0];

					if (strlen($tag)<1)
						$tag='UTC';

					$otherOptions='<input type="hidden" value="';
					if (sizeof($timezone_attributes)==7) {
						$otherOptions.=htmlentities(trim(implode(';',array_slice($timezone_attributes, 1))), ENT_QUOTES, get_option('blog_charset'), false);
					}
					else {
						$otherOptions.=';;;;1;1';
					}

					$otherOptions.='" />';

					/*
					arrows
					*/

					$up_arrow='<img class="'.$this->get_prefix().'arrowbutton" src="'.$this->get_plugin_url().'arrow_up_blue.png" onclick="'.$this->get_prefix().'move_element_up('.$counter.');" alt="move element up" />';
					$down_arrow='<img class="'.$this->get_prefix().'arrowbutton" style="margin-right:20px;" src="'.$this->get_plugin_url().'arrow_down_blue.png" onclick="'.$this->get_prefix().'move_element_down('.$counter.');" alt="move element down" />';

					/*
					add listener for drag-and-drop
					edit panel
					*/

					$list_selected_listeners.="Event.observe('".$before_key.$counter."', 'click', function(e){ ".$this->get_prefix()."populate_drag_and_drop('".$counter."') });";
					/*
					add timezone to list-selected
					*/

					$list_selected.= $before_tag. '"'.$before_key.$counter.'">'.$up_arrow.$down_arrow.$tag.$otherOptions.$after_tag. "\n";

					$counter++;
				}
			}
		}

		/*
		format list
		*/

		$element_height=32;

		$sizelist_selected=$element_height;

		if ($counter>1)
			$sizelist_selected=$counter*$element_height;

		$sizelist_available=$element_height;

		/*
		output selected timezones
		and edit form
		*/

		echo('<div><h4>TimeZone Entries</h4><ul class="'.$this->get_prefix().'sortablelist" id="'.$this->get_prefix().'list_selected" style="height:'.$sizelist_selected.'px;width:370px;"><li style="display:none"></li>'.$list_selected.'</ul></div>');

		$edit_abbr_fields=array(
			'abbr_standard' => 'Abbreviation Standard',
			'abbr_daylightsaving' => 'Abbreviation Daylightsaving'
		);

		$edit_name_fields=array(
			'name_standard' => 'Name Standard',
			'name_daylightsaving' => 'Name Daylightsaving'
		);

		?>

		<div id="<?php echo($this->get_prefix()); ?>edit">

			<input type="hidden" value="" id="<?php echo($this->get_prefix()); ?>edit_selected_timezone" />

			<div id="<?php echo($this->get_prefix()); ?>edit_header">
				<label for="<?php echo($this->get_prefix()); ?>edit_continent">TimeZone</label>
				<?php $this->setting_timezone($this->get_prefix().'edit_'); ?><img style="margin:4px; float:right" class="<?php echo($this->get_prefix()); ?>arrowbutton" src="<?php echo($this->get_plugin_url()); ?>arrow_down_blue.png" alt="show details" onclick="<?php echo($this->get_prefix()); ?>toggle_element(this, '<?php echo($this->get_prefix()); ?>edit_details');" /></div>

			<table style="display:none; margin-bottom:0" id="<?php echo($this->get_prefix()); ?>edit_details" class="form-table">

			<tr valign="top">
				<th scope="row"><label for="<?php echo($this->get_prefix()); ?>edit_use_db_abbreviations">Use DB Abbreviations</label></th>
				<td><input type="checkbox" onclick="<?php echo($this->get_prefix()); ?>toggle_related_fields(this, <?php echo($this->get_prefix()); ?>edit_abbr_fields, false);" checked="checked" id="<?php echo($this->get_prefix()); ?>edit_use_db_abbreviations" /></td>
			</tr>

			<?php foreach($edit_abbr_fields as $key => $edit_abbr_field) {
				echo('<tr valign="top"><th scope="row"><label for="'.$this->get_prefix().'edit_'.$key.'">'.$edit_abbr_field.'</label></th>');
			echo('<td><input disabled="disabled" id="'.$this->get_prefix().'edit_'.$key.'" type="text" size="10" maxlength="15" /></td></tr>');
			} ?>

			<tr valign="top">
				<th scope="row"><label for="<?php echo($this->get_prefix()); ?>edit_use_db_names">Use DB Names</label></th>
				<td><input type="checkbox" onclick="<?php echo($this->get_prefix()); ?>toggle_related_fields(this, <?php echo($this->get_prefix()); ?>edit_name_fields, false);" checked="checked" id="<?php echo($this->get_prefix()); ?>edit_use_db_names" /></td>
			</tr>

			<?php foreach($edit_name_fields as $key => $edit_name_field) {
				echo('<tr valign="top"><th scope="row"><label for="'.$this->get_prefix().'edit_'.$key.'">'.$edit_name_field.'</label></th>');
			echo('<td><input disabled="disabled" id="'.$this->get_prefix().'edit_'.$key.'" type="text" size="30" maxlength="50" /></td></tr>');
			} ?>

			</table>

			<div id="<?php echo($this->get_prefix()); ?>edit_success_label" style="display:none; margin:5px; font-weight:bold">Successfully adopted!</div>

			<div id="<?php echo($this->get_prefix()); ?>edit_submit">
				<input class="button-secondary" type="button" id="<?php echo($this->get_prefix()); ?>edit_create" value="Insert" />
				<input class="button-secondary" type="button" id="<?php echo($this->get_prefix()); ?>edit_new" value="New" />
			</div>

		</div>

		<br style="clear:both" />

		<?php

		/*
		provide garbage bin
		*/

		echo('<div><h4>Garbage Bin</h4><ul class="'.$this->get_prefix().'sortablelist" id="'.$this->get_prefix().'list_available" style="height:'.$sizelist_available.'px;width:370px;"><li style="display:none"></li></ul></div>'); ?>

		<div id="<?php echo($this->get_prefix()); ?>search">
			<label for="<?php echo($this->get_prefix()); ?>search_timeanddate_query">Country, city, timezone, etc.</label>
			<input type="text" value="" id="<?php echo($this->get_prefix()); ?>search_timeanddate_query" size="15" maxlength="40" /> <input class="button-secondary" type="button" id="<?php echo($this->get_prefix()); ?>search_timeanddate" value="Search" onclick="<?php echo($this->get_prefix()); ?>search_timeanddate_open_window();"/>
		</div>

		<br style="clear:both" /><br />

		<?php

		/*
		include javascript
		*/

		?>

		<?php $this->callback_settings_drag_and_drop_js($list_selected_listeners);
	}

	private function callback_settings_drag_and_drop_js($list_selected_listeners) { ?>

	<script type="text/javascript">

	/* <![CDATA[ */

	<?php echo($this->get_prefix()); ?>initialize_drag_and_drop();

	/*
	register listeners for buttons
	*/

	Event.observe('<?php echo($this->get_prefix()); ?>edit_create', 'click', function(e){ <?php echo($this->get_prefix()); ?>change_or_append_entry(); });

	Event.observe('<?php echo($this->get_prefix()); ?>edit_new', 'click', function(e){ <?php echo($this->get_prefix()); ?>reset_edit_form(); });

	/*
	register listeners for list
	(list_available is always empty)
	*/

	<?php echo($list_selected_listeners."\n"); ?>

	/* ]]> */

	</script>

	<?php }

	/*
	section expert
	*/

	function callback_settings_expert() { ?>
		In this section you can adopt your timezones-selection 'by hand'. Changes you make here are only reflected in the <?php echo($this->get_section_link($this->options_page_sections, 'drag_and_drop', 'Drag and Drop Section')); ?> after clicking on <strong>Save Changes</strong>.<br /><br />

		All parameters need to be separated by a semicolon. Please note, that only the <em>timezone_id</em> is mandatory.

		<h3>Syntax</h3>
		<ul>
			<li><a target="_blank" href="http://php.net/manual/en/timezones.php">timezone_id</a>;</li>
			<li>abbreviation_standard;</li>
			<li>abbreviation_daylightsaving;</li>
			<li>name_standard;</li>
			<li>name_daylightsaving;</li>
			<li>use_db_abbreviations;<ul>
				<li>0 ... use user-input as abbreviations</li>
				<li>1 ... use abbreviations from <abbr title="PHP: Hypertext Preprocessor">PHP</abbr> timezones database</li>
			</ul></li>
			<li>use_db_names<ul>
				<li>0 ... use user-input as names</li>
				<li>1 ... use names from <abbr title="PHP: Hypertext Preprocessor">PHP</abbr> timezones database (the timezone_id)</li>
			</ul></li>
		</ul>

		<h3>Examples</h3>
		<ul>
	   		<li>Asia/Bangkok</li>
			<li>America/New_York;EST;EWT;New York, NY, US;New York, NY, US;0;0</li>
			<li>Europe/Vienna;;;sleep longer in winter;get up earlier to enjoy the sun;1;0</li>
	    	</ul>

		<h3>Infos about TimeZones</h3>
		<ul>
			<li><a target="_blank" href="http://php.net/manual/en/timezones.php">php.net</a></li>
			<li><a target="_blank" href="http://www.timeanddate.com/library/abbreviations/timezones/">timeanddate.com</a></li>
			<li><a target="_blank" href="http://en.wikipedia.org/wiki/Timezones">wikipedia.org</a></li>
		</ul>
	<?php }

	/*
	textarea timezones
	*/

	function setting_timezones($params=array()) { ?>
		<textarea <?php echo($this->get_setting_name_and_id('timezones')); ?> cols="90" rows="5"><?php echo(htmlentities(implode("\n", $this->get_default('timezones')), ENT_QUOTES, get_option('blog_charset'), false)); ?></textarea>
	<?php }

	/*
	section format
	*/

	function callback_settings_format() { ?>
		In this section you can customize the layout of <?php echo($this->get_section_link($this->options_page_sections, 'preview', $this->get_nicename().'\'s output')); ?> after saving your changes by clicking on <strong>Save Changes</strong>. Tutorials, references and examples about <abbr title="HyperText Markup Language">HTML</abbr> and <abbr title="Cascading Style Sheets">CSS</abbr> can be found on <a target="_blank" href="http://www.w3schools.com/">W3Schools</a>.

		<ul>
			<li>The timezones-list will be wrapped within <em>before List</em> and <em>after List</em>. Each timezone-entry is based on <em>Format of TimeZone-Entry</em>. The following fields will be replaced by the attributes of each timezone-entry: <em>%abbreviation</em>, <em>%name</em> and <em>%datetime</em>.</li>

			<li>You can customize the <em>Format of Date/Time</em> by using standard <abbr title="PHP: Hypertext Preprocessor">PHP</abbr> syntax. The default format is <em>yyyy-mm-dd hh:mm</em> which looks like <em>Y-m-d H:i</em> in <abbr title="PHP: Hypertext Preprocessor">PHP</abbr>. - For details please refer to the WordPress <a target="_blank" href="http://codex.wordpress.org/Formatting_Date_and_Time"> Documentation on date and time formatting</a>.</li>

			<li>In case you do not need a container, you can disable the option <em>Wrap output in div-container</em>.</li>

			<li>The last option, <em>Display Results</em>, only refers to <abbr title="PHP: Hypertext Preprocessor">PHP</abbr> function calls with <code>$<?php echo($this->get_prefix(false)); ?>->output($params)</code>.</li>

			<li>Moreover, you can add <abbr title="Cascading Style Sheets">CSS</abbr> style attributes for the following <code>div</code> elements in your <a href="themes.php">Theme</a>, e.g. with the WordPress <a href="theme-editor.php">Theme-Editor</a>.</li>

		</ul><br />

		<table class="widefat">
			<thead><tr><th>Container</th><th>Type</th><th>Function/Shortcode calls</th><th>used if</th></tr></thead>
			<tbody><tr><td><code><?php echo($this->get_prefix(false)); ?>-refreshable-output</code></td><td>div</td><td><code>$<?php echo($this->get_prefix(false)); ?>->output($params)</code></td><td>same timezones and format as set in Admin Menu</td></tr>
			<tr><td><code><?php echo($this->get_prefix(false)); ?>-output</code></td><td>div</td><td><code>$<?php echo($this->get_prefix(false)); ?>->output($params)</code></td><td>different timezones or format as in Admin Menu</td></tr></tbody>
		</table><br />
	<?php }

	function setting_before_list($params=array()) {
		$this->setting_textfield('before_list', 'defaults');
	}

	function setting_after_list($params=array()) {
		$this->setting_textfield('after_list', 'defaults');
	}

	function setting_format_timezone($params=array()) {
		$this->setting_textfield('format_timezone', 'defaults', 100);
	}

	function setting_format_datetime($params=array()) {
		$this->setting_textfield('format_datetime', 'defaults');
	}

	function setting_use_container($params=array()) {
		$this->setting_checkfield('use_container', 'defaults');
	}

	function setting_display($params=array()) {
		$this->setting_checkfield('display', 'defaults');
	}

	/*
	section ajax refresh
	*/

	function callback_settings_ajax_refresh() { ?>
		In this section you can enable and customize the <abbr title="asynchronous JavaScript and XML">Ajax</abbr>-Refresh of <?php echo($this->get_nicename()); ?>.

		<ul>
			<li>After activating <em>Use Ajax Refresh</em> you can specify the seconds for the update interval (<em>Ajax Refresh Time</em>).</li>

			<li>Remember that on every refresh all timezone-information has to be retrieved from the server. Thus, an <em>Ajax Refresh Time</em> of one second is not practicable for the average server out there. In addition, every update causes bandwith usage for your readers and your server.</li>

			<li>Due to security reasons, the time for <abbr title="asynchronous JavaScript and XML">Ajax</abbr> updates will be limited by default. WordPress normally defines this time to be 24 hours. If you activate <em>Renew nonce to assure continous updates</em> you override this security feature but provide unlimited time for <abbr title="asynchronous JavaScript and XML">Ajax</abbr> updates of your timezones.</li>

			<li>You can also choose to allow only logged in users to execute <abbr title="asynchronous JavaScript and XML">Ajax</abbr> refresh operations if you deactivate the option <em>Allow anonymous Ajax Refresh Requests</em>.</li>
		</ul>
	<?php }

	function setting_use_ajax_refresh($params=array()) {
		$this->setting_checkfield('use_ajax_refresh', 'options', array('ajax_refresh_time', 'renew_nonce', 'anonymous_ajax_refresh'));
	}

	function setting_ajax_refresh_time($params=array()) {
		$this->setting_textfield('ajax_refresh_time', 'options', 4, 'onblur="'.$this->get_prefix().'check_integer(this, 1, 3600);"');
	}

	function setting_renew_nonce($params=array()) {
		$this->setting_checkfield('renew_nonce', 'options');
	}

	function setting_anonymous_ajax_refresh($params=array()) {
		$this->setting_checkfield('anonymous_ajax_refresh', 'options');
	}

	/*
	section dashboard
	*/

	function callback_settings_dashboard() { ?>
		If you enable one of the next options, <?php echo($this->get_nicename()); ?> will show your timezones either as a <a href="index.php">Dashboard Widget</a> or in the Right-Now-Box on the <a href="index.php">Dashboard</a>. You can also choose the necessary <a target="_blank" href="http://codex.wordpress.org/Roles_and_Capabilities">capability</a>.
	<?php }

	function setting_dashboard_widget($params=array()) {
		$this->setting_checkfield('dashboard_widget', 'options', array('dashboard_widget_capability'));
	}

	function setting_dashboard_widget_capability($params=array()) {
		$this->setting_capability('dashboard_widget', 'options');
	}

	function setting_dashboard_right_now($params=array()) {
		$this->setting_checkfield('dashboard_right_now', 'options', array('dashboard_right_now_capability'));
	}

	function setting_dashboard_right_now_capability($params=array()) {
		$this->setting_capability('dashboard_right_now', 'options');
	}

	/*
	section calculator
	*/

	function callback_settings_calculator() { ?>
		In this section you can select which <a target="_blank" href="http://codex.wordpress.org/Roles_and_Capabilities">capability</a> is necessary to enable the timezone-calculator in the <a href="tools.php?page=<?php echo($this->get_calculator_tools_page()); ?>">tools-menu</a>.
	<?php }

	function setting_calculator_capability($params=array()) {
		$this->setting_capability('calculator', 'options');
	}

	/*
	section world_clock
	*/

	function callback_settings_world_clock() { ?>
		<ul>
			<li>The <a href="tools.php?page=<?php echo($this->get_calculator_tools_page()); ?>">user's timezones-selection</a> (or as fallback <?php echo($this->get_section_link($this->options_page_sections, 'drag_and_drop', 'the global timezones-selection')); ?> can be added as submenu of Tools. You can select which <a target="_blank" href="http://codex.wordpress.org/Roles_and_Capabilities">capability</a> is necessary for your users to be able to access the world clock.</li>
			<li>Furthermore, it can also be included on the user's profile page, if you enable <em>Display user selected timezones (world clock) on user profile page</em>.</li>
		</ul>
	<?php }

	function setting_world_clock_tools_page($params=array()) {
		$this->setting_checkfield('world_clock_tools_page', 'options', array('world_clock_tools_page_capability'));
	}

	function setting_world_clock_tools_page_capability($params=array()) {
		$this->setting_capability('world_clock_tools_page', 'options');
	}

	function setting_include_world_clock_user_profile($params=array()) {
		$this->setting_checkfield('include_world_clock_user_profile', 'options');
	}

	/*
	section administrative options
	(also holds hidden section id)
	*/

	function callback_settings_administrative_options() { ?>
		<ul>
			<li>You can choose that <a href="tools.php?page=<?php echo($this->get_calculator_tools_page()); ?>">user selected timezones</a> should be preferred to global and function call ones with the option <em>Prefer User TimeZones</em>.</li>

			<li><a href="options-general.php">Your local WordPress Date/Time</a> can be displayed in the header of the Admin Menu if you enable <em>Display WordPress Clock in Admin Header</em>.</li>

			<li>As it may be a privacy invasion to provide someone with access to a certain user's timezones-selection, you can define the <em><a target="_blank" href="http://codex.wordpress.org/Roles_and_Capabilities">Capability</a> to view timezones-selection of other users</em>.</li>

			<li>The <em>Debug Mode</em> can be used to have a look on the actions undertaken by <?php echo($this->get_nicename()); ?> and to investigate unexpected behaviour.</li>
		</ul>

		<input type="hidden" <?php echo($this->get_setting_name_and_id('section')); ?> value="<?php echo($this->get_option('section')); ?>" />
	<?php }

	function setting_prefer_user_timezones($params=array()) {
		$this->setting_checkfield('prefer_user_timezones', 'defaults');
	}

	function setting_include_wordpress_clock_admin_head($params=array()) {
		$this->setting_checkfield('include_wordpress_clock_admin_head', 'options');
	}

	function setting_view_other_users_timezones_capability($params=array()) {
		$this->setting_capability('view_other_users_timezones', 'options');
	}

	function setting_debug_mode($params=array()) {
		$this->setting_checkfield('debug_mode', 'options');
	}

	/*
	section preview
	*/

	function callback_settings_preview() { ?>
		You can publish this output either by adding a <a href="widgets.php">Sidebar Widget</a>, <?php echo($this->get_section_link($this->options_page_sections, 'dashboard', 'Dashboard Widget')); ?> or by calling the <abbr title="PHP: Hypertext Preprocessor">PHP</abbr> function <code>$<?php echo($this->get_prefix(false)); ?>->output($params)</code> (optionally with several parameters as described in the <a target="_blank" href="http://wordpress.org/extend/plugins/<?php echo($this->get_prefix(false)); ?>/other_notes/">Other Notes</a>) after calling <code>global $<?php echo($this->get_prefix(false)); ?></code> wherever you like.<br /><br />

		<?php
		$params=array(
			'use_container' => true,
			'display' => true
		);

		$this->output($params);
	}

	/*
	CALCULATOR PAGE - SECTIONS + HELP
	*/

	/*
	adds help-text to admin-menu contextual help
	*/

	function calculator_page_help() {
		return 'Chosing your date can be done by either picking it on the calender or entering it in <a target="_blank" href="http://www.w3.org/QA/Tips/iso-date">ISO format (yyyy-mm-dd)</a> as well as <a target="_blank" href="http://en.wikipedia.org/wiki/Calendar_date#mm.2Fdd.2Fyy_or_mm.2Fdd.2Fyyyy_.28month.2C_day.2C_year.29">American date format (mm/dd/yyyy)</a>.

		As an alternative you can also enter a <a target="_blank" href="http://www.onlineconversion.com/unix_time.htm">unix timestamp</a> or <a target="_blank" href="http://www.php.net/manual/en/function.strtotime.php">any English textual datetime description</a> (like tomorrow 3 pm or 2009-04-23 16:00).';
	}

	/*
	intro callback
	*/

	function callback_calculator_intro() { ?>
		This section is meant to be your personal timezones calculator (e.g. for checking flight schedules or finding online friends in other timezones). - You can specify a certain timestamp, which will be displayed in the <?php echo($this->get_section_link($this->calculator_page_sections, 'selected_timezones', 'timezones')); ?> that you've chosen.
		<?php if ($this->get_option('world_clock_tools_page') && current_user_can($this->get_option('world_clock_tools_page_capability'))) { ?><br /><br />You can also view the current time of your timezones<?php if ($this->get_option('use_ajax_refresh')) { ?>, which automatically updates,<?php } ?> on the <a href="tools.php?page=<?php echo($this->get_world_clock_tools_page()); ?>">world clock</a>.<?php } ?>
	<?php }

	/*
	section calculator
	*/

	function callback_calculator_calculator() { ?>

		<table class="widefat" style="width:750px">
		<thead><tr>
			<th>Date</th>
			<th>Time</th>
			<th>TimeZone</th>
		</tr></thead>

		<?php

		$date_format=apply_filters($this->get_prefix().'calculator_format_date', array($this->get_default('format_datetime')));

		$time_format=apply_filters($this->get_prefix().'calculator_format_time',  $this->get_default('format_datetime'));

		?>

		<tbody><tr>
			<td><?php $this->setting_date($this->get_prefix(), false, $date_format); ?></td>
			<td><?php $this->setting_time($this->get_prefix(), '', '', $time_format); ?></td>
			<td><?php $this->setting_timezone($this->get_prefix()); ?></td>
		</tr></tbody>
		</table>

		<div class="submit">
			<img style="display:none" src="<?php echo(get_option('home')); ?>/wp-admin/images/wpspin_light.gif" alt="please wait..." id="<?php echo($this->get_prefix()); ?>wait_calculator" />
			<input class="button-primary" type="button" name="<?php echo($this->get_prefix()); ?>calculate_time" id="<?php echo($this->get_prefix()); ?>calculate_time" value="<?php _e('Calculate time') ?>" />
			<input class="button-primary" type="button" name="<?php echo($this->get_prefix()); ?>form_reset" id="<?php echo($this->get_prefix()); ?>form_reset" value="<?php _e('Reset') ?>" />
		</div>

		<h3>Results</h3>

		<?php

		/*
		produce an initial output block
		which holds only the info-string
		*/

		$params=array(
			'use_container' => true,
			'display' => false,
			'timezones' => array(
				'UTC'
			),
			'before_list' => '',
			'after_list' => '',
			'format_timezone' => 'none yet...',
			'no_refresh' => true
		);

		$initial_block=$this->output($params);

		echo($initial_block);

		/*
		include javascript
		*/

		$block_id=$this->get_block_id_from_block($initial_block);

		$this->callback_calculator_calculator_js($block_id);
	}

	private function callback_calculator_calculator_js($block_id) { ?>

		<script type="text/javascript">

		/* <![CDATA[ */

		timezonecalculator_calculator_settings.block_id=<?php echo("'".$block_id."'"); ?>;

		Event.observe('<?php echo($this->get_prefix()); ?>calculate_time', 'click', function(e){ <?php echo($this->get_prefix()); ?>calculator_calculation(); });

		Event.observe('<?php echo($this->get_prefix()); ?>form_reset', 'click', function(e){ <?php echo($this->get_prefix()); ?>calculator_reset_form(); });

		/* ]]> */

		</script>

	<?php }

	/*
	section selected_timezones
	*/

	function callback_calculator_selected_timezones() {
		global $user_ID;

		/*
		load current user's details
		*/

		get_currentuserinfo();

		$timezones_array=get_user_option($this->get_prefix().'timezones', $user_ID);

		if (!is_array($timezones_array) || empty($timezones_array))
			$timezones_array=$this->get_default('timezones');

		$this->do_settings_drag_and_drop($timezones_array);
		?>

		<form method="post" action="<?php echo(admin_url('tools.php')); ?>?page=<?php echo($this->get_calculator_tools_page()); ?>">
		<?php wp_nonce_field($this->get_prefix().'set_user_timezones'.$user_ID); ?>

		<input type="hidden" <?php echo($this->get_setting_name_and_id('section')); ?> />

		<textarea <?php echo($this->get_setting_name_and_id('timezones')); ?> cols="90" rows="5" style="display:none"><?php echo(htmlentities(implode("\n", $timezones_array), ENT_QUOTES, get_option('blog_charset'), false)); ?></textarea>

		<p class="submit">
		<?php
		$submit_buttons=array(
			'submit' => 'Save Changes',
			'reset' => 'Use Defaults'
		);

		foreach ($submit_buttons as $key => $submit_button) 				$this->setting_submit_button($key, $submit_button);
		?>
		</p>

		</form>
	<?php }

	/*
	API FUNCTION
	*/

	/*
	this function outputs/returns a timezones-block

	$params:

	- `query_time`: any unix timestamp (where `-1262304000 <= query_time <= 2145916800`) or any English textual datetime description in the range of `1930-01-01` and `2038-01-01` which can be parsed with [PHP's strtotime function](http://php.net/manual/en/function.strtotime.php); default is set to current UTC

	- `query_timezone`: origin-timezone of `query_time`; you can choose a [PHP timezone_string](http://php.net/manual/en/timezones.php); otherwise `UTC` will be used

	- `before_list`: default `<ul>`

	- `after_list`: default `</ul>`

	- `format_timezone`: default `<li><abbr title="%name">%abbreviation</abbr>: <span title="%name">%datetime</span></li>`

	- `format_datetime`: default `Y-m-d H:i`

	- `timezones`: alternative timezones-array - each array entry has to be a string as described in the Expert Section of the Admin Menu; default is the timezones-entries array which can be modified in the Admin Menu

	- `prefer_user_timezones`: prefer user set timezones - if they exist - to global or function call timezones; default is `false`

	- `use_container`: if set to `true` (default value), the current UTC is used as `query_time` and the same selected stats and format is used as set in the admin menu, TimeZoneCalculator wraps the output in a html div with the class `timezonecalculator-refreshable-output` - the class `timezonecalculator-output` will be used for all other output; if you set `use_container` to `false`, no container div will be generated

	- `display`: if you want to return the timezone-information (e.g. for storing in a variable) instead of echoing it with this function-call, set this to `false`; default setting is `true`

	- `format_container`: This option can be used to format the `div` container with css. Please note, that it should only be used to provide individual formats in case the class-style itself cannot be changed.

	- `no_refresh`: If set to true, TimeZoneCalculator will not produce any Ajax-Refresh-code, even if you have enabled the Ajax refresh in the admin menu.
	*/

	function output($params=array()) {
		try {
			return $this->_output($params);
		}
		catch(Exception $e) {
			$this->log($e->getMessage(), -1);
			return false;
		}
	}

	/*
	SHORTCODE
	*/

	/*
	shortcode for function output
	*/

	function shortcode_output($params, $content=null) {
		$params['display']=false;

		return $this->output($params);
	}

}

/*
DATETIMEZONE CLASS

this class is an extended version of
DateTimeZone and is capable of
calculating daylightsaving and offset
for a certain timestamp
*/

class TimeZoneCalculator_DateTimeZone extends DateTimeZone {

	private $timezone_id='UTC';

	private $timestamp=null;

	private $abbreviation_std=null;
	private $abbreviation_dst=null;
	private $db_abbreviation=null;

	private $name_std=null;
	private $name_dst=null;

	private $use_db_abbreviation=false;
	private $use_db_name=false;

	private $is_dst=false;

	private $offset;

	/*
	Constructor
	*/

	function __construct($timezone, $timestamp) {

		/*
		parse data
		*/

		$this->parse_data($timezone);

		/*
		set timestamp
		*/

		$this->set_timestamp($timestamp);

		/*
		call parent's constructor
		*/

		parent::__construct($this->get_timezone_id());

		$this->is_dst();
	}

	/*
	GETTERS AND SETTERS
	*/

	private function set_timezone_id($timezone_id) {
		$this->timezone_id=$timezone_id;
	}

	private function get_timezone_id() {
		return $this->timezone_id;
	}

	private function set_timestamp($timestamp) {
		$this->timestamp=$timestamp;
	}

	private function get_timestamp() {
		return $this->timestamp;
	}

	private function set_name_std($name_std) {
		$this->name_std=$name_std;
	}

	private function get_name_std() {
		return $this->name_std;
	}

	private function set_name_dst($name_dst) {
		$this->name_dst=$name_dst;
	}

	private function get_name_dst() {
		return $this->name_dst;
	}

	/*
	return std/dst db
	or handed over name
	*/

	function get_name() {

		/*
		use db name
		*/

		if ($this->get_use_db_name()) {
			$tokens = explode('/', $this->getName());

			$tokens[0]=__(str_replace('_', ' ', $tokens[0]), 'continents-cities');
			if (isset($tokens[1]))
				$tokens[1]=__(str_replace('_', ' ', $tokens[1]), 'continents-cities');
			if (isset($tokens[2]))
				$tokens[2]=__(str_replace('_', ' ', $tokens[2]), 'continents-cities');

			$string=$tokens[0];

			if (isset($tokens[1]) && !empty($tokens[1]))
				$string.='/'.$tokens[1];

			if (isset($tokens[2]) && !empty($tokens[2]))
				$string.='/'.$tokens[2];

			return $string;
		}

		/*
		decide between custom std and dst name
		*/

		else {
			if (!$this->is_dst)
				return $this->get_name_std();
			else
				return $this->get_name_dst();
		}
	}

	private function set_abbreviation_std($abbreviation_std) {
		$this->abbreviation_std=$abbreviation_std;
	}

	private function get_abbreviation_std() {
		return $this->abbreviation_std;
	}

	private function set_abbreviation_dst($abbreviation_dst) {
		$this->abbreviation_dst=$abbreviation_dst;
	}

	private function get_abbreviation_dst() {
		return $this->abbreviation_dst;
	}

	private function set_db_abbreviation($db_abbreviation) {
		$this->db_abbreviation=$db_abbreviation;
	}

	private function get_db_abbreviation() {
		return $this->db_abbreviation;
	}

	/*
	return std/dst db
	or handed over abbreviation
	*/

	function get_abbreviation() {

		/*
		use db abbreviation
		*/

		if ($this->get_use_db_abbreviation())
			return $this->get_db_abbreviation();

		/*
		decide between custom std and dst abbreviation
		*/

		else {
			if (!$this->is_dst)
				return $this->get_abbreviation_std();
			else
				return $this->get_abbreviation_dst();
		}
	}

	private function set_use_db_name($use_db_name) {
		$this->use_db_name=$use_db_name;
	}

	private function get_use_db_name() {
		return $this->use_db_name;
	}

	private function set_use_db_abbreviation($use_db_abbreviation) {
		$this->use_db_abbreviation=$use_db_abbreviation;
	}

	private function get_use_db_abbreviation() {
		return $this->use_db_abbreviation;
	}

	private function set_is_dst($is_dst) {
		$this->is_dst=$is_dst;
	}

	private function get_is_dst() {
		return $this->is_dst;
	}

	private function set_offset($offset) {
		$this->offset=$offset;
	}

	function get_offset() {
		return $this->offset;
	}

	/*
	LOGIC FUNCTIONS
	*/

	/*
	parses a timezone-entry

	in format
		timezone_id;
		abbr_standard;
		abbr_daylightsaving;
		name_standard;
		name_daylightsaving;
		use_db_abbreviations;
		use_db_names

	and fills object's attributes
	*/

	private function parse_data($timezone) {

		$timezone_array=explode(';', $timezone);

		/*
		first check if the size
		of the timezones-array match
		*/

		if (sizeof($timezone_array)!=7 && sizeof($timezone_array)!=1)
			throw new Exception('wrong number of parameters');

		/*
		WordPress TimeZones Support
		*/

		if ($timezone_array[0]=='Local_WordPress_Time')
			$timezone_array[0]=get_option('timezone_string');

		/*
		the timezone_id should contain
		at least one character
		*/

		if (strlen($timezone_array[0])<1)
			throw new Exception('no timezone-id given');

		/*
		if only the timezone-id has been given,
		we use default parameters for the rest
		*/

		if (sizeof($timezone_array)==1) {
			$this->set_timezone_id($timezone_array[0]);
			$this->set_abbreviation_std('');
			$this->set_abbreviation_dst('');
			$this->set_name_std('');
			$this->set_name_dst('');
			$this->set_use_db_abbreviation(true);
			$this->set_use_db_name(true);

			return true;
		}

		/*
		are the last
		two array-parameters 0 or 1?
		*/

		if (!$timezone_array[5]==1 && !$timezone_array[5]==0)
			throw new Exception('wrong parameter used for database abbreviations');

		if (!$timezone_array[6]==1 && !$timezone_array[6]==0)
			throw new Exception('wrong parameter used for database names');

		$this->set_timezone_id($timezone_array[0]);
		$this->set_abbreviation_std($timezone_array[1]);
		$this->set_abbreviation_dst($timezone_array[2]);
		$this->set_name_std($timezone_array[3]);
		$this->set_name_dst($timezone_array[4]);
		$this->set_use_db_abbreviation($timezone_array[5] == 1 ? true : false);
		$this->set_use_db_name($timezone_array[6] == 1 ? true : false);

		return true;
	}

	/*
	checks if timezone is within DST
	sets boolean isdst, current offset
	and current abbreviation in array
	*/

	private function is_dst() {

		/*
		defaults
		*/

		$isDst=0;
		$abbr=$this->getName();
		$offset=0;

		/*
		inspired from Derick's talk
		http://talks.php.net/show/time-ffm2006/28

		lookup array until current
		transition has been found
		*/

		foreach (timezone_transitions_get($this) as $tr) {
			if ($tr['ts'] > $this->get_timestamp())
				break;

			if((bool)$tr['isdst']===true)
				$isDst=1;
			else
				$isDst=0;

			$abbr=$tr['abbr'];
			$offset=$tr['offset'];
		}

		$this->set_is_dst($isDst == 1 ? true : false);
		$this->set_db_abbreviation($abbr);
		$this->set_offset($offset);
	}

	/*
	this methods returns
	the actual timestamp including
	all relevant descriptive data
	for the chosen timezone,
	for example as list-entry
	*/

	function format_timezone($params) {
		$name=htmlentities($this->get_name(), ENT_QUOTES, get_option('blog_charset'), false);
		$abbreviation=htmlentities($this->get_abbreviation(), ENT_QUOTES, get_option('blog_charset'), false);
		$datetime=date_i18n($params['format_datetime'], ($params['query_time']+ $this->get_offset()), true);

		/*
		build tag for html output
		*/

		return str_replace(array('%name', '%abbreviation', '%datetime'), array($name, $abbreviation, $datetime), $params['format_timezone']);
	}

}

/*
WIDGET CLASS
*/

class WP_Widget_TimeZoneCalculator extends WP_Widget {

	/*
	constructor
	*/

	function WP_Widget_TimeZoneCalculator() {
		global $timezonecalculator;

		$widget_ops = array(
			'classname' => 'widget_'.$timezonecalculator->get_prefix(false),
			'description' => 'Calculates, displays and automatically updates times and dates in different timezones with respect to daylight saving.'
		);

		$this->WP_Widget($timezonecalculator->get_prefix(false), $timezonecalculator->get_nicename(), $widget_ops);
	}

	/*
	produces the widget-output
	*/

	function widget($args, $instance) {
		global $timezonecalculator;

		extract($args);

		$title = !isset($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);

		$prefer_user_timezones=false;

		if (isset($instance['prefer_user_timezones']))
			$prefer_user_timezones=$instance['prefer_user_timezones'];

		echo $before_widget;
		echo $before_title . $title . $after_title;

		$params=array(
			'use_container' => true,
			'display' => true,
			'prefer_user_timezones' => $prefer_user_timezones
		);

		$timezonecalculator->output($params);

	    	echo $after_widget;
	}

	/*
	the backend-form
	*/

	function form($instance) {
		global $timezonecalculator;

		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$prefer_user_timezones = isset($instance['prefer_user_timezones']) ? $instance['prefer_user_timezones'] : '';
		?>

		<p><label for="<?php echo $this->get_field_id('title'); ?>">
		<?php _e('Title:'); ?>

		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

		<p><label for="<?php echo $this->get_field_id('prefer_user_timezones'); ?>">
		<?php _e('Prefer User TimeZones:'); ?>

		<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id('prefer_user_timezones'); ?>" name="<?php echo $this->get_field_name('prefer_user_timezones'); ?>" value="1" <?php checked('1', $prefer_user_timezones); ?> /></label></p>

		<p><a href='options-general.php?page=<?php echo($timezonecalculator->get_prefix(false)); ?>'><?php _e('Settings'); ?></a></p>

		<?php
	}

	/*
	saves updated widget-options
	*/

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['prefer_user_timezones'] = ($new_instance['prefer_user_timezones'] == 1 ? true : false);

		return $instance;
	}

}

/*
UNINSTALL
*/

function timezonecalculator_uninstall() {
 
		/*
		security check
		*/

		if (!current_user_can('manage_options'))
			wp_die(__('You do not have sufficient permissions to manage options for this blog.'));

		/*
		delete option-array
		*/

		delete_option('timezonecalculator');

		/*
		delete widget-options
		*/

		delete_option('widget_timezonecalculator');

		/*
		delete calculator user-settings
		*/

		global $wpdb;

		$q = "DELETE FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key LIKE '%timezonecalculator_timezones%'";

		$wpdb->query($q);
	}

register_uninstall_hook(__FILE__, 'timezonecalculator_uninstall');

/*
BACKPORTED
*/

if (!function_exists('_wp_timezone_choice_usort_callback')) {

	/*
	backport of _wp_timezone_choice_usort_callback
	for WP < 2.9.0
	*/

	function _wp_timezone_choice_usort_callback( $a, $b ) {
		// Don't use translated versions of Etc
		if ( 'Etc' === $a['continent'] && 'Etc' === $b['continent'] ) {
			// Make the order of these more like the old dropdown
			if ( 'GMT+' === substr( $a['city'], 0, 4 ) && 'GMT+' === substr( $b['city'], 0, 4 ) ) {
				return -1 * ( strnatcasecmp( $a['city'], $b['city'] ) );
			}
			if ( 'UTC' === $a['city'] ) {
				if ( 'GMT+' === substr( $b['city'], 0, 4 ) ) {
					return 1;
				}
				return -1;
			}
			if ( 'UTC' === $b['city'] ) {
				if ( 'GMT+' === substr( $a['city'], 0, 4 ) ) {
					return -1;
				}
				return 1;
			}
			return strnatcasecmp( $a['city'], $b['city'] );
		}
		if ( $a['t_continent'] == $b['t_continent'] ) {
			if ( $a['t_city'] == $b['t_city'] ) {
				return strnatcasecmp( $a['t_subcity'], $b['t_subcity'] );
			}
			return strnatcasecmp( $a['t_city'], $b['t_city'] );
		} else {
			// Force Etc to the bottom of the list
			if ( 'Etc' === $a['continent'] ) {
				return 1;
			}
			if ( 'Etc' === $b['continent'] ) {
				return -1;
			}
			return strnatcasecmp( $a['t_continent'], $b['t_continent'] );
		}
	}
}

/*
DEPRECATED FUNCTIONS
*/

function getTimeZonesTime($time_string='', $timezone_string='UTC', $alt_style=false, $alt_before_list='<ul>', $alt_after_list='</ul>', $alt_before_tag='<li>', $alt_after_tag='</li>', $alt_timeformat='Y-m-d H:i', $alt_timezones=array(), $display_name=true, $use_container=true) {
	global $timezonecalculator;

	$timezonecalculator->deprecated_function(__FUNCTION__, '2.00', 'output');

	$params=array();

	/*
	query options
	*/

	$params['query_time']=$time_string;
	$params['query_timezone']=$timezone_string;

	/*
	use alternative style
	*/

	if ($alt_style) {
		$params['before_list']=$alt_before_list;
		$params['after_list']=$alt_after_list;

		$params['format_timezone']=$alt_before_tag.'<abbr title="%name">%abbreviation</abbr>: <span title="%name">%datetime</span>'.$alt_after_tag;

		if (!$display_name)
			$params['format_timezone']=$alt_before_tag.'%abbreviation: %datetime'.$alt_after_tag;

		$params['format_datetime']=$alt_timeformat;
	}

	/*
	use alternative timezones
	*/

	if (is_array($alt_timezones) && !empty($alt_timezones))
		$params['timezones']=$alt_timezones;

	/*
	output options
	*/

	$params['use_container']=$use_container;
	$params['display']=true;

	$timezonecalculator->output($params);
}

?>