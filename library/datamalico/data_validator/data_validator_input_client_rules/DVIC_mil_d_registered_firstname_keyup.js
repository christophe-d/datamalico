//
function DVIC_mil_d_registered_firstname_keyup (event) 
{
	event.stopPropagation();

	var firstname_value = $(this).val();
	firstname_value = firstname_value.toLowerCase();
	firstname_value = firstname_value.capitalize();
	$(this).val(firstname_value);

	return $(this);
}
