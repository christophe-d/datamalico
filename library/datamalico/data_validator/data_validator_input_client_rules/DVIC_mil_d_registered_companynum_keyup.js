//
function DVIC_mil_d_registered_companynum_keyup (event) 
{
	event.stopPropagation();

	$(this).companynum_format($('select[name="delupsert[country_id][f][country_id]"]'));

	return $(this);
}
