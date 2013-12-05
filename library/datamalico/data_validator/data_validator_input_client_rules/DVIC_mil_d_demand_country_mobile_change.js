//
function DVIC_mil_d_registered_country_mobile_change (event) 
{
	event.stopPropagation();

	var country_mobile = $(this).val();

	// impact mobile format
	$('input[name="delupsert[mobile][f][mobile]"]').phone_format($(this));

	// impact country_id
	var country_id = $('#country_id select').val();
	if (parseInt(country_id) == 0) $('#country_id select').do_selection(country_mobile);

	// impact country_phone
	var country_phone = $('#country_phone select').val();
	if (parseInt(country_phone) == 0) $('#country_phone select').do_selection(country_mobile);

	return $(this);
}
