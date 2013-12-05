//
function DVIC_mil_d_demand_country_id_change (event) 
{
	event.stopPropagation();

	var country_id = $(this).val();

	// impact country_phone
	var country_phone = $('#country_phone select').val();
	if (parseInt(country_phone) == 0) $('#country_phone select').do_selection(country_id);

	// impact country_mobile
	var country_mobile = $('#country_mobile select').val();
	if (parseInt(country_mobile) == 0) $('#country_mobile select').do_selection(country_id);

	return $(this);
}
