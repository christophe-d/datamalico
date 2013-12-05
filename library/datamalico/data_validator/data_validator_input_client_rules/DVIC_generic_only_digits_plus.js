//
function DVIC_generic_only_digits_plus (event) 
{
	event.stopPropagation();

	var field = $(this).val();
	field = only_digits_plus(field);
	$(this).val(field);

	return $(this);
}
