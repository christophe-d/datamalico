//
function DVIC_mil_d_registered_phone_keyup (event) 
{
	event.stopPropagation();

	//$(this).phone_format($('select[name="delupsert[country_phone][f][country_phone]"]'));	// standard page and standard display
	// select[where][1_country_phone_20121208164518912][c][country_phone]	// research tab
	// delupsert[1_country_phone_20121208164154240][f][country_phone]	// data tab
	
	// For all select[where] or delupsert fields, but without 
	$(this).phone_format($('select[name$="[country_phone]"]').not('[name*="[o]"]'));

	return $(this);
}
