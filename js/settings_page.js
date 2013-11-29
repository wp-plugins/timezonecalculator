/*
hide all option-page sections
*/

function timezonecalculator_hide_sections() {
	for (var i=0; i<timezonecalculator_sections.length; i++) {
		jQuery('#timezonecalculator_'+timezonecalculator_sections[i]+'_link').removeClass('current');
		jQuery('#timezonecalculator_'+timezonecalculator_sections[i]).css('display', 'none');
	}
}

/*
opens admin-menu section
*/

function timezonecalculator_open_section(section) {
	timezonecalculator_hide_sections();

	var my_section='';

	if (section.length>0) {
		for (var i=0;i<timezonecalculator_sections.length;i++) {
			if (timezonecalculator_sections[i]==section) {
				my_section=section;
				break;
			}
		}
	}

	if (my_section.length===0)
		my_section=timezonecalculator_sections[0];

	jQuery('#timezonecalculator_'+my_section).css('display', 'block');
	jQuery('#timezonecalculator_'+my_section+'_link').addClass('current');
	jQuery('#timezonecalculator_section').val(my_section);
}