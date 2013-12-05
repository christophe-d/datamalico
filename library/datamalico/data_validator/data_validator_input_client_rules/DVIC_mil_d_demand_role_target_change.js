//
function DVIC_mil_d_demand_role_target_change (event) 
{
	event.stopPropagation();

	var role_id = $(this).val();
	var role_name;
	if (role_id === "2") role_name = "Volunteer";
	if (role_id === "3") role_name = "Professional";

	//console.log(role_id + ":" + role_name);

	// show or hide the budget fields:
	if (role_name === "Volunteer") $('#ATI_budget_container').slideUp();
	$('#ATI_budget input[type="text"]').val("");
	if (role_name === "Professional") $('#ATI_budget_container').slideDown();

	return $(this);
}
