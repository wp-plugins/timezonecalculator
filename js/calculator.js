/*
set today's date as default
*/

function timezonecalculator_calculator_set_default_date() {
	datePickerController.setSelectedDate('timezonecalculator_date', timezonecalculator_get_today_date());
}

/*
automatically update _ajax_nonce
*/

var timezonecalculator_calculator_query_params=new Hashtable();

timezonecalculator_calculator_query_params.put('action', 'timezonecalculator_calculator_ajax_nonce');

timezonecalculator_calculator_query_params.put('_ajax_nonce', timezonecalculator_calculator_settings._ajax_nonce);

if (parseInt(timezonecalculator_calculator_settings.refresh_nonce, 10)==1)
	window.setInterval(function(){
		timezonecalculator_refresh(new Hashtable(), timezonecalculator_calculator_query_params);
		},
		600*1000
	);

/*
display error/success message
*/
		
function timezonecalculator_calculator_display_message(message) {
	var divid='timezonecalculator_block_'+timezonecalculator_calculator_settings.block_id;
	jQuery('#'+divid).html(message);
}

/*
display error/success message
*/
		
function timezonecalculator_calculator_error_calculation(error) {
	var message='A communication error occurred! - Please try again!';

	if (error==-2 || error==-3)
		message='An error occurred! - Please check your input!';

	timezonecalculator_calculator_display_message(message);
}

/*
calculate_time button
retrieve timezones via AJAX Call

- display 'none yet...'
- disable calculate_time button
- disable form_reset button
- display wait-cursor
*/

function timezonecalculator_calculator_calculation() {
	timezonecalculator_calculator_display_message('none yet&hellip;');

	var query_time=jQuery('#timezonecalculator_date').val();

	if (query_time.length<3) {
		timezonecalculator_calculator_display_message('no date entered!');
		return;
	}

	jQuery('#timezonecalculator_date').prop('disabled', true);
	jQuery('#timezonecalculator_hour').prop('disabled', true);
	jQuery('#timezonecalculator_minute').prop('disabled', true);
	jQuery('#timezonecalculator_continent').prop('disabled', true);
	jQuery('#timezonecalculator_timezone').prop('disabled', true);
	datePickerController.disable('timezonecalculator_date');

	jQuery('#timezonecalculator_calculate_time').prop('disabled', true);
	jQuery('#timezonecalculator_form_reset').prop('disabled', true);
	jQuery('#timezonecalculator_wait_calculator').css('display', 'inline');

	var params = timezonecalculator_refresh_create_params('timezonecalculator_block_'+timezonecalculator_calculator_settings.block_id, '<div id="timezonecalculator_block_'+timezonecalculator_calculator_settings.block_id+'" class="timezonecalculator-output"');

	params.put('callback_finished', timezonecalculator_calculator_after_calculation);
	params.put('callback_error', timezonecalculator_calculator_error_calculation);

	if (jQuery('#timezonecalculator_hour').prop('selectedIndex')>0 && jQuery('#timezonecalculator_minute').prop('selectedIndex')>0) {
		query_time+=jQuery('#timezonecalculator_hour').val()+':'+jQuery('#timezonecalculator_minute').val();
	}

	var query_timezone=jQuery('#timezonecalculator_timezone').val();
	var query_timezones=escape(jQuery('#timezonecalculator_timezones').val());

	var query_string='id='+timezonecalculator_calculator_settings.block_id+'&query_time='+query_time+'&query_timezone='+query_timezone+'&timezones='+query_timezones+'&before_list='+timezonecalculator_calculator_settings.before_list+'&after_list='+timezonecalculator_calculator_settings.after_list+'&format_timezone='+timezonecalculator_calculator_settings.format_timezone+'&format_datetime='+timezonecalculator_calculator_settings.format_datetime;

	var query_params=timezonecalculator_refresh_create_query_params_basis(timezonecalculator_calculator_query_params.get('_ajax_nonce'), query_string);

	query_params.put('action', 'timezonecalculator_calculator');

	timezonecalculator_refresh(params, query_params);
}

function timezonecalculator_calculator_after_calculation() {
	jQuery('#timezonecalculator_date').prop('disabled', false);
	jQuery('#timezonecalculator_hour').prop('disabled', false);
	jQuery('#timezonecalculator_minute').prop('disabled', false);
	jQuery('#timezonecalculator_continent').prop('disabled', false);
	jQuery('#timezonecalculator_timezone').prop('disabled', false);
	datePickerController.enable('timezonecalculator_date');

	jQuery('#timezonecalculator_calculate_time').prop('disabled', false);
	jQuery('#timezonecalculator_form_reset').prop('disabled', false);
	jQuery('#timezonecalculator_wait_calculator').css('display','none');
}

/*
reset button
*/

function timezonecalculator_calculator_reset_form() {
	timezonecalculator_calculator_display_message('none yet&hellip;');

	jQuery('#timezonecalculator_date').val('');
	jQuery('#timezonecalculator_hour').prop('selectedIndex', 0);
	jQuery('#timezonecalculator_minute').prop('selectedIndex', 0);

	jQuery('#timezonecalculator_continent').prop('selectedIndex', 0);
	timezonecalculator_set_timezone_array('timezonecalculator_');

	jQuery('#timezonecalculator_timezone').prop('selectedIndex', 0);
	timezonecalculator_calculator_set_default_date();
}