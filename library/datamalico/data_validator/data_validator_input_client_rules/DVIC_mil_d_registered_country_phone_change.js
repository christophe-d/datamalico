//
function DVIC_mil_d_registered_country_phone_change (event) 
{
	event.stopPropagation();

	var country_phone = $(this).val();

	// impact phone format
	$('input[name="delupsert[phone][f][phone]"]').phone_format($(this));

	// impact country_id
	var country_id = $('#country_id select').val();
	if (parseInt(country_id) == 0) $('#country_id select').do_selection(country_phone);

	// impact country_mobile
	var country_mobile = $('#country_mobile select').val();
	if (parseInt(country_mobile) == 0) $('#country_mobile select').do_selection(country_phone);

	return $(this);
}
