//
function DVIC_generic_only_digits_strict (event) 
{
	event.stopPropagation();

	var field = $(this).val();
	field = only_digits_strict(field);
	$(this).val(field);

	return $(this);
}
