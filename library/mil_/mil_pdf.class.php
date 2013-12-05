<?php
/** 
 * @file
 * File where the mil_pdf class is defined
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2013
 */

include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/pdf/fpdf17/fpdf.php";


/**
 * Class that makes a PDF document, extends the FPDF class.
 *
 * Make an output and see current position::
 * @code
 * $pdf->Cell(0, 1, "X: " . $pdf->GetX() . " / Y: " . $pdf->GetY(), 0, 1);
 * @endcode
 */
class mil_pdf extends FPDF
{
	/*
var $pages;
var $page;  
var $w, $h;              // dimensions of current page in user unit
var $x, $y;              // current position in user unit
var $lasth;              // height of last printed cell
var $CurPageSize;        // current page size
	['w'] and ['h']
var $lMargin;            // left margin
var $tMargin;            // top margin
var $rMargin;            // right margin
var $bMargin;            // page break margin, distance to the bottom of the sheet (positive number).
var $cMargin;            // cell margin, only one margin, left or right.
var $PageBreakTrigger;   // threshold used to trigger page breaks
	 */

	var $header_bMargin;	// distance to the top of the sheet
	var $footer_tMargin;	// distance to the top of the sheet

	public function __construct ($orientation='P', $unit='cm', $size='A4')
	{ 
		parent::__construct ($orientation, $unit, $size); 
		$this->CurPageSize['w'] = &$this->CurPageSize[0];
		$this->CurPageSize['h'] = &$this->CurPageSize[1];
	}

	/**
	 * Is the overloading definition of the FPDF::Header() method (See the FPDF documentation).
	 *
	 * You can also override this method by creating another class for a certain type of document.
	 */
	function Header()
	{
		/*
		$this->header_bMargin = 3.5;

		// Our designation:
		$operationdecoration = "operationdecoration.fr";
		$operationdecoration = iconv ('UTF-8', 'ISO-8859-1', $operationdecoration);
		$this->SetTextColor(0, 0, 150);
		$this->SetFont('Arial', 'BU', 8);
		$this->Cell(0, 0.4, $operationdecoration, 0, 1, "L", false, "http://operationdecoration.fr");


		$our_designation = "marque dÃ©posÃ©e de\n1001 Expressions SARL\n7 rue de Puymaigre\n57070 Metz";
		$our_designation = iconv ('UTF-8', 'ISO-8859-1', $our_designation);
		$this->SetTextColor(0, 0, 0);
		$this->SetFont('Arial', 'B', 8);
		$this->MultiCell(0, 0.4, $our_designation);

		// Logo
		$this->Image($_SERVER["DOCUMENT_ROOT"]."/1001_addon/assets/templates/common/img/operationdecoration_invoice.png", 5, 0.6, 0, 2.3, "", "http://operationdecoration.fr");
		// Police Arial gras 15
		$this->SetFont('Arial', 'B', 15);
		// Décalage à droite
		$this->Cell(8);
		// Titre
		$this->Cell(3, 1, 'Facture', 1, 0, 'C');
		// Saut de ligne
		//$this->Ln(2);
		$this->SetXY($this->lMargin, $this->header_bMargin);
		 */
	}

	/**
	 * Is the overloading definition of the FPDF::Footer() method (See the FPDF documentation).
	 *
	 * You can also override this method by creating another class for a certain type of document.
	 */
	function Footer()
	{
		/*
		$this->footer_tMargin = -1.5;

		// Positionnement à 1, 5 cm du bas
		$this->SetY($this->footer_tMargin);
		// Police Arial italique 8
		$this->SetFont('Arial', 'I', 8);
		// Numéro de page
		$this->Cell(0, 1, "X: " . $this->GetX() . " / Y: " . $this->GetY(), 0, 1);
		$this->Cell(0, 1, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
		 */
	}

	/**
	 * Allow to write 'some' HTML structure in a pdf document. See the FPDF project page.
	 * You can use the following tags:
	 * 	- B, I, U, A, BR in both cases.
	 */
	function WriteHTML($html)
	{
		// Parseur HTML
		$html = str_replace("\n",' ',$html);
		$a = preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
		foreach($a as $i=>$e)
		{
			if($i%2==0)
			{
				// Texte
				if($this->HREF)
					$this->PutLink($this->HREF,$e);
				else
					$this->Write(0.5,$e);
			}
			else
			{
				// Balise
				if($e[0]=='/')
					$this->CloseTag(strtoupper(substr($e,1)));
				else
				{
					// Extraction des attributs
					$a2 = explode(' ',$e);
					$tag = strtoupper(array_shift($a2));
					$attr = array();
					foreach($a2 as $v)
					{
						if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3))
							$attr[strtoupper($a3[1])] = $a3[2];
					}
					$this->OpenTag($tag,$attr);
				}
			}
		}
	}

	function OpenTag($tag, $attr)
	{
		// Balise ouvrante
		if($tag=='B' || $tag=='I' || $tag=='U')
			$this->SetStyle($tag,true);
		if($tag=='A')
			$this->HREF = $attr['HREF'];
		if($tag=='BR')
			$this->Ln(0.5);
	}

	function CloseTag($tag)
	{
		// Balise fermante
		if($tag=='B' || $tag=='I' || $tag=='U')
			$this->SetStyle($tag,false);
		if($tag=='A')
			$this->HREF = '';
	}

	function SetStyle($tag, $enable)
	{
		// Modifie le style et sélectionne la police correspondante
		$this->$tag += ($enable ? 1 : -1);
		$style = '';
		foreach(array('B', 'I', 'U') as $s)
		{
			if($this->$s>0)
				$style .= $s;
		}
		$this->SetFont('',$style);
	}

	function PutLink($URL, $txt)
	{
		// Place un hyperlien
		$this->SetTextColor(0,0,255);
		$this->SetStyle('U',true);
		$this->Write(0.5,$txt,$URL);
		$this->SetStyle('U',false);
		$this->SetTextColor(0);
	}

	/**
	 * This method creates a table.
	 *
	 * @param table: {associative array} (mandatory) Are all the params to make this FancyTable.
	 * 	Note that there must be a default definition ot these called: $GLOBALS['config_ini']['pdf']['default_table']
	 * 	- columns_width: {numerical array} (optional) Are the values of column widths. The unit to use is the one of the document.
	 * 	- row_height: {float} (optional) Height of rows.
	 * 	- cell_margin: {float} (optional) You can specify a cell margin. Note that this does not impact the cell margin itself,
	 * 		but this is usefull to fine tune display bugs in case of text on several lines in a cell.
	 * 	- borders: {associative array} (optional)
	 * 		- size: {float} (optional) The width of the table borders.
	 * 		- color: {numerical array} (optional) RGB colors.
	 * 	- header: {associative array} (optional)
	 * 		- data: {numerical array} (optional) Names of the header columns. The format should be:
	 * 			- Eg: array ("Column One", "Column Two", "Col 3")
	 * 		- display: {associative array} (optional)
	 * 			- text_color: {numerical array} (optional) RGB colors.
	 * 			- text_family: {string} (optional)  Arial, Times, Courier, Symbol et ZapfDingbats. See the FPDF::SetFont() method for more information.
	 * 			- text_style: {string} (optional) B, I, U. See the FPDF::SetFont() method for more information.
	 * 			- text_size: {int or string} (optional) Font size in points, eg: 12. See the FPDF::SetFont() method for more information.
	 * 			- background_fill: {bool} (optional) Specify if you want an alternance of transparence and colored rows.
	 * 			- background_color: {numerical array} (optional) RGB colors. If background_fill is true, then the header is colored.
	 * 			- columns_align_horizontal: {string} (optional) Horizontal alignment, possible values are:
	 * 				- "L" for Left
	 * 				- "C" for Center
	 * 				- "R" for Right
	 * 			- columns_align_vertical: {string} (optional) "Top", "Middle", "Bottom". // Not implemented yet
	 * 	- body: {associative array} (optional)
	 * 		- data: {numerical array} (optional) Data to be displayed in the table. The format should be:
	 * 			- numerical or associative key as row count: {array, numerical or associative} (optional) One row content:
	 * 				- numerical or associative key as field_name: {int or string} (optional) Is the first cell content of the row.
	 * 				- numerical or associative key as field_name: {int or string} (optional) Is the second cell content of the row.
	 * 				- ...
	 * 		- display: {associative array} (optional)
	 * 			- text_color: {numerical array} (optional) RGB colors.
	 * 			- text_family: {string} (optional)  Arial, Times, Courier, Symbol et ZapfDingbats. See the FPDF::SetFont() method for more information.
	 * 			- text_style: {string} (optional) B, I, U. See the FPDF::SetFont() method for more information.
	 * 			- text_size: {int or string} (optional) Font size in points, eg: 12. See the FPDF::SetFont() method for more information.
	 * 			- background_fill: {bool} (optional) Specify if you want an alternance of transparence and colored rows.
	 * 			- background_color: {numerical array} (optional) RGB colors. If background_fill is true, then the header is colored.
	 * 			- columns_align_horizontal: {string} (optional) Horizontal alignment, possible values are:
	 * 				- "L" for Left
	 * 				- "C" for Center
	 * 				- "R" for Right
	 * 			- columns_align_vertical: {string} (optional) "Top", "Middle", "Bottom". // Not implemented yet
	 *
	 * @warning Tip: when you add a column to the table, just check if all params of the table param named "columns_*" and table['header'] fields fit the correct number of columns.
	 *
	 * @todo Implement the use of param table['body']['display']['columns_align_vertical']
	 */
	public function mil_table ($table)
	{
		//trace2file ("############################", "", __FILE__, TRUE);


		$table = replace_leaves_keep_all_branches ($table, $GLOBALS['config_ini']['pdf']['default_table']);

		$positions = array (
			'table' => array (
				'x' => $this->GetX()
				, 'y' => $this->GetY()
				, 'w' => array_sum($table['columns_width'])	// width
				//, 'h' => null
			)
			, 'rows' => array (
				0 => array (
					'x' => $this->GetX()
					, 'y' => $this->GetY()
					//, 'w' => array_sum($table['columns_width'])
					, 'h' => null
				)
				// ...
			)
		);


		// Si le tableau est à moins de 5cm de bas, alors on addPage();


		// Header:
		$row_num = 0;
		$table['header']['data'][1] = $table['header']['data'];
		//$this->SetFillColor($table['header']['display']['background_color'][0], $table['header']['display']['background_color'][1], $table['header']['display']['background_color'][2]);
		//$this->SetTextColor($table['header']['display']['text_color'][0], $table['header']['display']['text_color'][1], $table['header']['display']['text_color'][2]);
		//$this->SetFont('', 'B');
		$positions['rows'][$row_num] = $this->print_row_colored_bg ("header", $table, 1, "LTR");

		// Body:
		$row_num = 1;
		//$this->SetFillColor($table['body']['display']['background_color'][0], $table['body']['display']['background_color'][1], $table['body']['display']['background_color'][2]);
		//$this->SetTextColor($table['body']['display']['text_color'][0], $table['body']['display']['text_color'][1], $table['body']['display']['text_color'][2]);
		//$this->SetFont('');
		foreach ($table['body']['data'] as $index => $row_content)
		{
			//trace2file ($row_num, "row_num", __FILE__);
			//trace2file ($index, "index", __FILE__);
			// print data:
			if ($table['body']['display']['background_fill'] === true)
			{
				if (is_even ($row_num) === false)
				{
					$positions['rows'][$row_num] = $this->print_row_transparent_bg ("body", $table, $index, "LR");
				}
				else
				{
					$positions['rows'][$row_num] = $this->print_row_colored_bg ("body", $table, $index, "LR");
				}
			}
			else
			{
				$positions['rows'][$row_num] = $this->print_row_transparent_bg ("body", $table, $index, "LR");
			}

			//echo trace2web($positions['rows'][$row_num], "positions['rows'][$row_num] returned");
			//$this->SetXY($positions['rows'][$row_num]['x'], $positions['rows'][$row_num]['y'] + $positions['rows'][$row_num]['h']);
			$row_num++;
		}


		// Draw bottom line:
		$the_last_row_for_bottom_line = $row_num + 1;
		$table['body']['data'][$the_last_row_for_bottom_line];
		foreach ($table['header']['data'][1] as $col_index => $value)
		{
			$table['body']['data'][$the_last_row_for_bottom_line][] = ""; 
		}
		$table['row_height'] = 0.00001;
		$positions['rows'][$row_num] = $this->print_row_transparent_bg ("body", $table, $the_last_row_for_bottom_line, "T");
	}

	private function print_row_transparent_bg ($table_part, $table, $index, $border)
	{
		return $this->print_row ($table_part, $table, $index, false, $border);
	}

	private function print_row_colored_bg ($table_part, $table, $index, $border)
	{
		return $this->print_row ($table_part, $table, $index, true, $border);
	}

	/**
	 * Displays only one table row to the format you desire.
	 * This method places the new current position just after the row.
	 *
	 * @param table_part: {string} (mandatory) Specifies if you refer to the 'header' or 'body' $table member; espacially for the 'columns_align_*'.
	 * @param table: {associative array} (mandattory) The whole table information, melting data and display informations:
	 * 	- See mil_pdf::mil_table() method and its table param for more information. See also the $GLOBALS['config_ini']['pdf']['default_table'].
	 * @param index: {integer|string} (mandatory) The index of the data you want to display taken from $table[$table_part]['data'][$index]
	 * @param colored_bg: {bool} (optional, default is false) If you want the row to get a colored background.
	 * @param border: {string} (optional, default is "") The borders you want to draw arround the row. Can be "LTRB", see the FPDF::Cell() method
	 * 	and its 'border' parameter for more information.
	 *
	 * @return row_properties: {associative array} (mandatory) Are the row properties:
	 * 	- x
	 * 	- y
	 * 	- w: width
	 * 	- h: height
	 *
	 * Example of call:
	 * @code
	 * $table = replace_leaves_keep_all_branches ($table, $GLOBALS['config_ini']['pdf']['default_table']);
	 * $myrow = $this->print_row ("body", $table, 3, true, "B");
	 * @endcode
	 */
	public function print_row ($table_part, $table, $index, $colored_bg = false, $border = "")
	{
		$row = $table[$table_part]['data'][$index];

		$row_properties = array (
			'x' => $this->GetX()
			, 'y' => $this->GetY()
			, 'w' => array_sum($table['columns_width'])
			, 'h' => null
		);
		//echo trace2web($row_properties, "row_properties 1");


		// Draw the row, the line:
		$col_num = 0;
		$count = count ($row);
		$temp_x = $this->GetX();
		$highest_cell_height = 0;


		// ##########
		// Set color for the row if not transparent:
		$this->SetFillColor($table[$table_part]['display']['background_color'][0], $table[$table_part]['display']['background_color'][1], $table[$table_part]['display']['background_color'][2]);
		$this->SetTextColor($table[$table_part]['display']['text_color'][0], $table[$table_part]['display']['text_color'][1], $table[$table_part]['display']['text_color'][2]);
		$this->SetFont($table[$table_part]['display']['text_family'], $table[$table_part]['display']['text_style'], $table[$table_part]['display']['text_size']);



		// ##########
		// Predict the highest cell for the row, by checking each cell:
		//trace2file ("############################", "", __FILE__);
		//trace2file ("Before PREDICT:", "", __FILE__);
		//trace2file ($this->page, "page", __FILE__);
		//trace2file ($this->x, "x", __FILE__);
		//trace2file ($this->y, "y", __FILE__);
		foreach ($row as $col_name => $value)
		{
			$cell_width = $table['columns_width'][$col_num];
			$pred_cell = $this->MultiCellPredict($cell_width, $table['row_height'], $value, '', $table[$table_part]['display']['columns_align_horizontal'][$col_num], $colored_bg);
			$col_num++;

			$this_cell_height = $pred_cell['y'] - $row_properties['y'];
			$highest_cell_height = $this_cell_height > $highest_cell_height ? $this_cell_height : $highest_cell_height;
		}

		//trace2file ("After PREDICT:", "", __FILE__);
		//trace2file ($this->page, "page", __FILE__);
		//trace2file ($this->x, "x", __FILE__);
		//trace2file ($this->y, "y", __FILE__);

		// ##########
		// foreach cell, draw it:
		$col_num = 0;
		foreach ($row as $col_name => $value)
		{
			//trace2file ("--------------------", "", __FILE__);
			//trace2file ($value, "value", __FILE__);



			//trace2file ("---- before checking new page:", "", __FILE__);
			//trace2file ($this->page, "page", __FILE__);
			//trace2file ($this->x, "x", __FILE__);
			//trace2file ($this->y, "y", __FILE__);


			$cell_width = $table['columns_width'][$col_num];

			// ##########
			// manage if the cell would overwrite the bottom margin, and if so, then go to next page:	
			if($this->GetY() + $highest_cell_height > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
			{
				$before_newPage_x = $this->GetX();
				$before_newPage_y = $this->GetY();
				$this->Cell($cell_width, $this->PageBreakTrigger - $this->GetY() + 0.5, '' , '',  0, $table[$table_part]['display']['columns_align_horizontal'][$col_num], false);
				$after_newPage_y = $this->GetY();
				$row_properties['y'] = $after_newPage_y;
				$this->SetXY($before_newPage_x, $after_newPage_y);

				// Insert a new header at the top of the next page:
				// Header:
				$row_num = 0;
				$table['header']['data'][1] = $table['header']['data'];
				$this->SetFillColor($table['header']['display']['background_color'][0], $table['header']['display']['background_color'][1], $table['header']['display']['background_color'][2]);
				$this->SetTextColor($table['header']['display']['text_color'][0], $table['header']['display']['text_color'][1], $table['header']['display']['text_color'][2]);
				$this->SetFont('', 'B');
				$positions['rows'][$row_num] = $this->print_row_colored_bg ("header", $table, 1, "LTR");
				$row_properties['y'] = $this->GetY();

				// Body:
				$row_num = 1;
				$this->SetFillColor($table[$table_part]['display']['background_color'][0], $table[$table_part]['display']['background_color'][1], $table[$table_part]['display']['background_color'][2]);
				$this->SetTextColor($table[$table_part]['display']['text_color'][0], $table[$table_part]['display']['text_color'][1], $table[$table_part]['display']['text_color'][2]);
				$this->SetFont('');
			}


			//trace2file ("---- before bg color:", "", __FILE__);
			//trace2file ($this->page, "page", __FILE__);
			//trace2file ($this->x, "x", __FILE__);
			//trace2file ($this->y, "y", __FILE__);
			//trace2file ($highest_cell_height, "highest_cell_height", __FILE__);

			// ##########
			// color background:
			$before_bg_x = $this->GetX();
			$before_bg_y = $this->GetY();
			$this->Cell($cell_width, $highest_cell_height, '', '',  0, $table[$table_part]['display']['columns_align_horizontal'][$col_num], $colored_bg);
			$this->SetXY($before_bg_x, $before_bg_y);


			//trace2file ("---- before writing cell:", "", __FILE__);
			//trace2file ($this->page, "page", __FILE__);
			//trace2file ($this->x, "x", __FILE__);
			//trace2file ($this->y, "y", __FILE__);

			// ##########
			// write into cell:
			$this->MultiCell($cell_width, $table['row_height'], $value, '',  $table[$table_part]['display']['columns_align_horizontal'][$col_num], false);



			//trace2file ("---- before resetting current position:", "", __FILE__);
			//trace2file ($this->page, "page", __FILE__);
			//trace2file ($this->x, "x", __FILE__);
			//trace2file ($this->y, "y", __FILE__);



			$this_cell_height = $this->GetY() - $row_properties['y'];
			$highest_cell_height = $this_cell_height > $highest_cell_height ? $this_cell_height : $highest_cell_height;

			$temp_x = $temp_x + $cell_width;
			$this->SetXY($temp_x, $row_properties['y']);

			//trace2file ("---- before end of loop:", "", __FILE__);
			//trace2file ($this->page, "page", __FILE__);
			//trace2file ($this->x, "x", __FILE__);
			//trace2file ($this->y, "y", __FILE__);

			$col_num++;
		}
	/*
			// foreach cell
			foreach ($row as $col_name => $value)
			{
				$cell_width = $table['columns_width'][$col_num];
				$this->MultiCell($cell_width, $table['row_height'], $value, '',  $table[$table_part]['display']['columns_align_horizontal'][$col_num], $colored_bg);
				$col_num++;

				$this_cell_height = $this->GetY() - $row_properties['y'];
				$highest_cell_height = $this_cell_height > $highest_cell_height ? $this_cell_height : $highest_cell_height;

				$temp_x = $temp_x + $cell_width;
				$this->SetXY($temp_x, $row_properties['y']);
	}

	// If the row has a background color, then, redraw, in order to set the full height of the row, and have the background color in the whole height:
	if ($colored_bg === true)
	{
		$col_num = 0;
		$this->SetXY($row_properties['x'], $row_properties['y']);
		$temp_x = $this->GetX();
		foreach ($row as $col_name => $value)
		{
			$cell_width = $table['columns_width'][$col_num];

			// Colors the background:
			$temp_y = $this->GetY();
			$this->Cell($cell_width, $highest_cell_height, '', '',  0, $table[$table_part]['display']['columns_align_horizontal'][$col_num], $colored_bg);
			$this->SetXY($temp_x, $temp_y);

			// Write into the cell
			$this->MultiCell($cell_width, $table['row_height'], $value, '',  $table[$table_part]['display']['columns_align_horizontal'][$col_num], false);
			$col_num++;

			// readjust the current position:
			$this_cell_height = $this->GetY() - $row_properties['y'];
			$highest_cell_height = $this_cell_height > $highest_cell_height ? $this_cell_height : $highest_cell_height;

			$temp_x = $temp_x + $cell_width;
			$this->SetXY($temp_x, $row_properties['y']);
		}
		// this is a test row
	}*/

		//if ($col_num < $count - 1) $this->SetXY($x + $table['columns_width'][$col_num], $y);

		$row_properties['h'] = $highest_cell_height;

		// ##########
		// draw borders of the row:
		$this->SetXY($row_properties['x'], $row_properties['y']);
		$this->Cell($row_properties['w'], $highest_cell_height, '', $border,  0, $table[$table_part]['display']['columns_align_horizontal'][$col_num], false);


		// ##########
		// reposition the current position to the next line:
		$this->SetXY($row_properties['x'], ($row_properties['y'] + $row_properties['h']));

		//echo trace2web($row_properties, "row_properties returning");
		return $row_properties;
	}





	/**
	 * This method predicts the future current position.
	 * Params are exactly the same those for the FPDF::Cell() mthod.
	 *
	 * @return previewed_position: {associative array} (mandatory)
	 * 	- x: the previewed x position.
	 * 	- y: the previewed y position.
	 */
	public function CellPredict ($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
	{
		$original_position = array (
			'x' => $this->GetX()
			, 'y' => $this->GetY()
		);
		$original_margin = array (
			'bottom' => $this->bMargin
		);
		$this->bMargin = 999999999999999;
		$this->PageBreakTrigger = $this->h-$this->bMargin;

		// Output a cell
		$k = $this->k;
		if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
		{
			// Automatic page break
			$x = $this->x;
			$ws = $this->ws;
			if($ws>0)
			{
				$this->ws = 0;
				$this->_out('0 Tw');
			}
			//$this->AddPage($this->CurOrientation,$this->CurPageSize);
			$this->x = $x;
			if($ws>0)
			{
				$this->ws = $ws;
				$this->_out(sprintf('%.3F Tw',$ws*$k));
			}
		}
		if($w==0)
			$w = $this->w-$this->rMargin-$this->x;
		$s = '';
		if($fill || $border==1)
		{
			if($fill)
				$op = ($border==1) ? 'B' : 'f';
			else
				$op = 'S';
			$s = sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
		}
		if(is_string($border))
		{
			$x = $this->x;
			$y = $this->y;
			if(strpos($border,'L')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
			if(strpos($border,'T')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			if(strpos($border,'R')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			if(strpos($border,'B')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
		}
		if($txt!=='')
		{
			if($align=='R')
				$dx = $w-$this->cMargin-$this->GetStringWidth($txt);
			elseif($align=='C')
				$dx = ($w-$this->GetStringWidth($txt))/2;
			else
				$dx = $this->cMargin;
			if($this->ColorFlag)
				$s .= 'q '.$this->TextColor.' ';
			$txt2 = str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
			$s .= sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txt2);
			if($this->underline)
				$s .= ' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
			if($this->ColorFlag)
				$s .= ' Q';
			if($link)
				$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$this->GetStringWidth($txt),$this->FontSize,$link);
		}
		//if($s)
		//	$this->_out($s);
		$this->lasth = $h;
		if($ln>0)
		{
			// Go to next line
			$this->y += $h;
			if($ln==1)
				$this->x = $this->lMargin;
		}
		else
			$this->x += $w;


		$previewed_position = array (
			'x' => $this->GetX()
			, 'y' => $this->GetY()
		);

		// reset to original position:
		$this->SetXY($original_position['x'], $original_position['y']);
		$this->bMargin = $original_margin['bottom'];
		$this->PageBreakTrigger = $this->h-$this->bMargin;

		return $previewed_position;
	}


	/**
	 * Is necessary only for the mil_pdf::MultiCellPredict() method.
	 */
	private function CellPredict_2 ($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
	{
		// Output a cell
		$k = $this->k;
		if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
		{
			// Automatic page break
			$x = $this->x;
			$ws = $this->ws;
			if($ws>0)
			{
				$this->ws = 0;
				$this->_out('0 Tw');
			}
			//$this->AddPage($this->CurOrientation,$this->CurPageSize);
			$this->x = $x;
			if($ws>0)
			{
				$this->ws = $ws;
				$this->_out(sprintf('%.3F Tw',$ws*$k));
			}
		}
		if($w==0)
			$w = $this->w-$this->rMargin-$this->x;
		$s = '';
		if($fill || $border==1)
		{
			if($fill)
				$op = ($border==1) ? 'B' : 'f';
			else
				$op = 'S';
			$s = sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
		}
		if(is_string($border))
		{
			$x = $this->x;
			$y = $this->y;
			if(strpos($border,'L')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
			if(strpos($border,'T')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			if(strpos($border,'R')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			if(strpos($border,'B')!==false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
		}
		if($txt!=='')
		{
			if($align=='R')
				$dx = $w-$this->cMargin-$this->GetStringWidth($txt);
			elseif($align=='C')
				$dx = ($w-$this->GetStringWidth($txt))/2;
			else
				$dx = $this->cMargin;
			if($this->ColorFlag)
				$s .= 'q '.$this->TextColor.' ';
			$txt2 = str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
			$s .= sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txt2);
			if($this->underline)
				$s .= ' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
			if($this->ColorFlag)
				$s .= ' Q';
			if($link)
				$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$this->GetStringWidth($txt),$this->FontSize,$link);
		}
		//if($s)
		//	$this->_out($s);
		$this->lasth = $h;
		if($ln>0)
		{
			// Go to next line
			$this->y += $h;
			if($ln==1)
				$this->x = $this->lMargin;
		}
		else
			$this->x += $w;
	}

	/**
	 * This method predicts the future current position.
	 * Params are exactly the same those for the FPDF::MultiCell() mthod.
	 *
	 * @return previewed_position: {associative array} (mandatory)
	 * 	- x: the previewed x position.
	 * 	- y: the previewed y position.
	 */
	public function MultiCellPredict($w, $h, $txt, $border=0, $align='J', $fill=false)
	{
		$original_position = array (
			'x' => $this->GetX()
			, 'y' => $this->GetY()
		);

		// Output text with automatic or explicit line breaks
		$cw = &$this->CurrentFont['cw'];
		if($w==0)
			$w = $this->w-$this->rMargin-$this->x;
		$wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
		$s = str_replace("\r",'',$txt);
		$nb = strlen($s);
		if($nb>0 && $s[$nb-1]=="\n")
			$nb--;
		$b = 0;
		if($border)
		{
			if($border==1)
			{
				$border = 'LTRB';
				$b = 'LRT';
				$b2 = 'LR';
			}
			else
			{
				$b2 = '';
				if(strpos($border,'L')!==false)
					$b2 .= 'L';
				if(strpos($border,'R')!==false)
					$b2 .= 'R';
				$b = (strpos($border,'T')!==false) ? $b2.'T' : $b2;
			}
		}
		$sep = -1;
		$i = 0;
		$j = 0;
		$l = 0;
		$ns = 0;
		$nl = 1;
		while($i<$nb)
		{
			// Get next character
			$c = $s[$i];
			if($c=="\n")
			{
				// Explicit line break
				if($this->ws>0)
				{
					$this->ws = 0;
					$this->_out('0 Tw');
				}
				$this->CellPredict_2($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
				$i++;
				$sep = -1;
				$j = $i;
				$l = 0;
				$ns = 0;
				$nl++;
				if($border && $nl==2)
					$b = $b2;
				continue;
			}
			if($c==' ')
			{
				$sep = $i;
				$ls = $l;
				$ns++;
			}
			$l += $cw[$c];
			if($l>$wmax)
			{
				// Automatic line break
				if($sep==-1)
				{
					if($i==$j)
						$i++;
					if($this->ws>0)
					{
						$this->ws = 0;
						$this->_out('0 Tw');
					}
					$this->CellPredict_2($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
				}
				else
				{
					if($align=='J')
					{
						$this->ws = ($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
						$this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
					}
					$this->CellPredict_2($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
					$i = $sep+1;
				}
				$sep = -1;
				$j = $i;
				$l = 0;
				$ns = 0;
				$nl++;
				if($border && $nl==2)
					$b = $b2;
			}
			else
				$i++;
		}
		// Last chunk
		if($this->ws>0)
		{
			$this->ws = 0;
			$this->_out('0 Tw');
		}
		if($border && strpos($border,'B')!==false)
			$b .= 'B';
		$this->CellPredict_2($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
		$this->x = $this->lMargin;


		$previewed_position = array (
			'x' => $this->GetX()
			, 'y' => $this->GetY()
		);

		// reset to original position:
		$this->SetXY($original_position['x'], $original_position['y']);

		return $previewed_position;
	}

	/**
	 * This method returns the x position in order to center an element (cell, image...) in the available zone between lMargin and rMargin.
	 *
	 * @param element_width: {float} (mandatory) Is the previewed width of the element.
	 *
	 * @return x_centered: {int} (mandatory) Is the x position (in the user unit) in order to center the element.
	 */
	public function get_x_to_center_elem_in_doc ($elem_width)
	{
		$available_zone_width = $this->CurPageSize['w'] - ($this->lMargin + $this->rMargin); // width between lMargin and rMargin
		
		$x_from_lMargin = ($available_zone_width - $elem_width) / 2;
		$x_from_lBorder = $this->lMargin + $x_from_lMargin;

		if ($x_from_lMargin <= $this->lMargin) $x_from_lBorder = $this->lMargin;

		return $x_from_lBorder;
	}

	/**
	 * This method returns center position of the free zone (t between lMargin and rMargin and tMargin and bMargin)
	 *
	 * @return free_zone_center: {associative array} (mandatory) Are the x and y of the free zone center.
	 */
	public function get_free_zone_center ()
	{
		$available_zone_width = $this->CurPageSize['w'] - ($this->lMargin + $this->rMargin); // width between lMargin and rMargin
		$x_from_lBorder = $this->lMargin + ($available_zone_width / 2);

		$available_zone_height = $this->CurPageSize['h'] - ($this->tMargin + $this->bMargin); // width between lMargin and rMargin
		$y_from_tBorder = $this->tMargin + ($available_zone_height / 2);

		$free_zone_center = array (
			'x' => $x_from_lBorder
			, 'y' => $y_from_tBorder
		);

		return $free_zone_center;
	}

}


?>
