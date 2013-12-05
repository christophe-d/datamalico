<?php
/** 
 * @file
 * File where the mil_pdf class is defined
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2013
 */

include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/mil_/mil_pdf.class.php";

/**
 * Class that makes a PDF document, extends the FPDF class.
 *
 * Make an output and see current position::
 * @code
 * $pdf->Cell(0, 1, "X: " . $pdf->GetX() . " / Y: " . $pdf->GetY(), 0, 1);
 * @endcode
 */
class mil_pdf_invoice extends mil_pdf
{
	var $props;

	public function __construct ($orientation='P', $unit='cm', $size='A4')
	{ 
		parent::__construct ($orientation, $unit, $size);

		$this->SetAutoPageBreak(true, 4);
		$this->AliasNbPages();
		$this->SetTitle("Facture");
		$this->SetAuthor("operationdecoration.fr");
		$this->SetMargins(1, 2, 1);

		// set the bMargin:
		//$this->SetAutoPageBreak(true, 2);
	}

	/**
	 * Is the overloading definition of the mil_pdf::Header() method which is already the overloading of FPDF::Header() method (See the FPDF documentation).
	 */
	function Header()
	{
		// ############################
		// Our designation:

		// operationdecoration:
		$operationdecoration = getDomain("http://".$GLOBALS['config_ini']['site_domain']);
		$operationdecoration = iconv ('UTF-8', 'ISO-8859-1', $operationdecoration);
		$this->SetTextColor(0, 0, 150);
		$this->SetFont('Arial', 'BU', 9.5);
		$this->Cell(0, 0.4, $operationdecoration, 0, 1, "L", false, "http://operationdecoration.fr");

		// trademark:
		$trademark = $this->props['mil_lang']['trademark'];
		$trademark = iconv ('UTF-8', 'ISO-8859-1', $trademark);
		$this->SetTextColor(0, 0, 0);
		$this->SetFont('Arial', 'I', 8.5);
		$this->Cell(0, 0.4, $trademark, 0, 1);

		// Our address:
		$our_header_designation = $this->props['mil_lang']['our_name'] . "\n" . $this->props['mil_lang']['our_address'];
		$our_header_designation = iconv ('UTF-8', 'ISO-8859-1', $our_header_designation);
		$this->SetFont('Arial', 'B', 9.5);
		$this->MultiCell(0, 0.4, $our_header_designation);

		// Logo:
		$this->Image($_SERVER["DOCUMENT_ROOT"]."/1001_addon/assets/templates/common/img/operationdecoration_invoice.png", 5.5, 1.75, 0, 2.5, "", "http://operationdecoration.fr");
		// Police Arial gras 15


		// ############################
		// Customer designation:
		$free_zone_center = $this->get_free_zone_center();
		$this->SetXY($free_zone_center['x'] + 1.5, $this->tMargin);

		// dedicated:
		$dedicated = $this->props['mil_lang']['dedicated'];
		$dedicated = iconv ('UTF-8', 'ISO-8859-1', $dedicated);
		$this->SetFont('Arial', '', 8.5);
		$this->MultiCell(0, 0.4, $dedicated);

		// Customer address:
		$this->SetXY($free_zone_center['x'] + 1.5, $this->GetY());

		$lang = $GLOBALS['config_ini']['lang'];
		$delimiter = " ";
		$customer_designation_array[] = array_to_string_nice_concat ($delimiter, array (
			$this->props['data']['invoice']['results']['records'][1]['companyname']
			, 
		));
		$customer_designation_array[] = array_to_string_nice_concat ($delimiter, array (
			$this->props['data']['invoice']['results']['records'][1]['gender']
			, $this->props['data']['invoice']['results']['records'][1]['firstname']
			, $this->props['data']['invoice']['results']['records'][1]['lastname']
		));
		$customer_designation_array[] = array_to_string_nice_concat ($delimiter, array (
			$this->props['data']['invoice']['results']['records'][1]['addrnum']
			, $this->props['data']['invoice']['results']['records'][1]['street']
		));
		$customer_designation_array[] = array_to_string_nice_concat ($delimiter, array (
			$this->props['data']['invoice']['results']['records'][1]['zipcode']
			, $this->props['data']['invoice']['results']['records'][1]['city']
		));
		$customer_designation_array[] = array_to_string_nice_concat ($delimiter, array (
			$this->props['data']['invoice']['results']['records'][1]['state']
			, $this->props['data']['invoice']['results']['records'][1]['country_living']
		));

		foreach ($customer_designation_array as $index => $value)
			if (
				!exists_and_not_empty($customer_designation_array[$index])
				|| !exists_and_not_empty(preg_replace('/\s/', '', $customer_designation_array[$index]))
			) unset ($customer_designation_array[$index]);

		$customer_designation = implode ("\n", $customer_designation_array);

		$customer_designation = iconv ('UTF-8', 'ISO-8859-1', $customer_designation);
		$this->SetFont('Arial', 'B', 9.5);
		$this->MultiCell(0, 0.4, $customer_designation);


		// ############################
		// Invoice number:
		$invoice_id = $this->props['data']['invoice']['results']['records'][1]['invoice_id'];
		$this->Ln();
		$this->SetXY($free_zone_center['x'] + 1.5, $this->GetY());
		$number = $this->props['mil_lang']['invoice_title'] . " " . $this->props['mil_lang']['number'] . " " . $invoice_id;
		$number = iconv ('UTF-8', 'ISO-8859-1', $number);
		$this->SetFont('Arial', '', 9.5);
		$this->MultiCell(0, 0.4, $number);


		// ############################
		// invoice_credate:
		$last_update = $this->props['data']['invoice']['results']['records'][1]['last_update'];
		$this->SetXY($free_zone_center['x'] + 1.5, $this->GetY());
		$invoice_credate = $this->props['mil_lang']['invoice_credate'] . " " . $last_update;
		$invoice_credate = iconv ('UTF-8', 'ISO-8859-1', $invoice_credate);
		$this->SetFont('Arial', '', 9.5);
		$this->MultiCell(0, 0.4, $invoice_credate);


		// ############################
		// Close header:
		$this->Ln();


		// ############################
		// Be ready for the page:
		$this->header_bMargin = $this->GetY();
		$this->SetXY($this->lMargin, $this->header_bMargin);
	}

	/**
	 * Is the overloading definition of the mil_pdf::Footer() method which is already the overloading of FPDF::Footer() method (See the FPDF documentation).
	 */
	function Footer()
	{
		// ############################
		// Init footer:
		$this->footer_tMargin = $this->CurPageSize['h'] - $this->bMargin;
		//trace2file($this->footer_tMargin, "this->footer_tMargin", __FILE__, true);
		$this->SetTextColor(125, 125, 125);
		$this->SetY($this->footer_tMargin);
		$this->SetDrawColor(125, 125, 125);
		$this->SetFont('Arial', 'I', 8);

		// ############################
		// Put distance with the bMargin:
		$this->Ln();

		// ############################
		// Draw over line:
		$this->Cell(0, 0.2, "", "T", 1);

		// ############################
		// Our props:
		$our_footer_designation = $this->props['mil_lang']['our_name'] . " " . $this->props['mil_lang']['our_funds'] . " - " . $this->props['mil_lang']['our_company_id'] . "\n" . $this->props['mil_lang']['our_VAT_number'];
		$our_footer_designation = iconv ('UTF-8', 'ISO-8859-1', $our_footer_designation);
		$this->SetFont('Arial', 'I', 9.5);
		$this->MultiCell(0, 0.4, $our_footer_designation, 0, "C");

		// ############################
		// Page number:
		$this->Cell(0, 0.8, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');	
	}
}


?>
