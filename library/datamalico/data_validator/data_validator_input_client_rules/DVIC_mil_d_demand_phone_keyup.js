//
function DVIC_mil_d_registered_phone_keyup (event) 
{
	event.stopPropagation();

	$(this).phone_format($('select[name="delupsert[country_phone][f][country_phone]"]'));

	return $(this);
}
