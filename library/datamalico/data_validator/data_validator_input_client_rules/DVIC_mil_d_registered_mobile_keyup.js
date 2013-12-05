//
function DVIC_mil_d_registered_mobile_keyup (event) 
{
	event.stopPropagation();

	$(this).phone_format($('select[name="delupsert[country_mobile][f][country_mobile]"]'));

	return $(this);
}
