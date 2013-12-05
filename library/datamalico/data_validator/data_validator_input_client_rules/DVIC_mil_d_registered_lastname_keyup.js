//
function DVIC_mil_d_registered_lastname_keyup (event) 
{
	event.stopPropagation();

	var lastname_value = $(this).val();
	lastname_value = lastname_value.toUpperCase();
	$(this).val(lastname_value);

	return $(this);
}
