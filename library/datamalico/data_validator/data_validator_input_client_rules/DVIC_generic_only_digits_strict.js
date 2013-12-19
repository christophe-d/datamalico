//
function DVIC_generic_only_digits_strict (event) 
{
	event.stopPropagation();

	var field = $jq1001(this).val();
	field = only_digits_strict(field);
	$jq1001(this).val(field);

	return $jq1001(this);
}
