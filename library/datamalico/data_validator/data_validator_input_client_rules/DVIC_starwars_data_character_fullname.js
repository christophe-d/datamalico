//
function DVIC_mil_c_countries_french_keyup (event) 
{
	event.stopPropagation();
	//console.log ($(this).val() + "Hey");

	var pattern = /r2d2/gi;
	var found = pattern.test($(this).val().toLowerCase());
	if (found === true)
	{
		alert ("Hey stop! The DVIC (Data Validator Input on Client side) has seen that you have typed r2d2.");
	}
}
