//
function DVIC_mil_d_basket_item_item_nb_100th (event) 
{
	event.stopPropagation();

	var supposedServicePrice = $(this).val();
	//supposedServicePrice = only_digits_plus(supposedServicePrice);
	supposedServicePrice = supposedServicePrice.replace(/[^01]/g,""); 		// allow only figures: 0 or 1, and no other chars
	supposedServicePrice = supposedServicePrice.replace(/^(.)(.*)/gi, "$1");	// allow only one figure, owing to backreference

	$(this).val(supposedServicePrice);

	return $(this);
}
