<?php
  
  define('FPDF_FONTPATH','../fpdf16/font/');
  require('../fpdf16/fpdf.php');
  
function prepare_PDF_data($xml, $currentTab, $removal_date, $removal_type) {
  $removal_type_items_time = array();
  $common_items_time = array();
  $common_items = $xml->xpath("removalTipItem[@type='common']");
  $removal_type_items = $xml->xpath("removalTipItem[@type='".$removal_type."']");
  $sizeof_common_items = sizeof($common_items);
  $sizeof_removal_type_items = sizeof($removal_type_items);
  $ri = 0;
  $ci = 0;
  $merged_items_time = array();
  $pdf_data1 = array();
  /* wenn pdfs erzeugt werden sollen die sich auf bestimmte Zeiträume beziehen dann wieder einbinden
  $pdf_data2 = array();
  $pdf_data3 = array();
  $pdf_data4 = array();*/
  $pdf_data_i = 0;
  foreach($removal_type_items as $item) {
    $removal_type_items_time[$ri] = (int) $item->attributes()->order;
    $ri++;
  }
  foreach($common_items as $item) {
    $common_items_time[$ci] = (int) $item->attributes()->order;
    $ci++;
  }
  $merged_items_time = array_merge($removal_type_items_time, $common_items_time);
  sort($merged_items_time, SORT_NUMERIC);
  foreach($merged_items_time as $item_time){
  	$order = (int)$item_time;
  	$item_date_ts= $removal_date + ($order*86400);
    $removal_week_end_ts = $removal_date + (7*86400);
    $tip = getTip($xml, $order, $removal_type);
    $pdf_data1[$item_date_ts] = $tip;
    /* wenn pdfs erzeugt werden sollen die sich auf bestimmte Zeiträume beziehen dann wieder einbinden
    if ($item_date_ts < $removal_date) {
      $pdf_data2[$item_date_out] = $pdf_data1[$item_date_out];
    }
    if ($item_date_ts >= $removal_date && $item_date_ts <= $removal_week_end_ts) {
      $pdf_data3[$item_date_out] = $pdf_data1[$item_date_out];
    }
    if ($item_date_ts > $removal_week_end_ts) {
      $pdf_data4[$item_date_out] = $pdf_data1[$item_date_out];
    }*/
    
  }
  return $pdf_data1;
}
function getTip($xml, $order, $removal_type) {
  $temp = array();
  $li = 0;
  $removal_tip = $xml->xpath("removalTipItem[@type='".$removal_type."'][@order='".$order."']");
  if (sizeof($removal_tip) < 1){
    $removal_tip = $xml->xpath("removalTipItem[@type='common'][@order='".$order."']");
  }
  $headline = (string)$removal_tip[0]->headline;
  $text;
  $list = array();
  $link_list = array();
  if (sizeof($removal_tip[0]->text) > 0){
  	$text = (string)$removal_tip[0]->text;
  }
  if (sizeof($removal_tip[0]->list) > 0){
  	$list_holder = $removal_tip[0]->list->listItem;
  	foreach ($list_holder as $list_item) {
      $list[$li] = (string)$list_item;
      $li++;
  	}
  }
  if (sizeof($removal_tip[0]->link) > 0){
  	$link_holder = $removal_tip[0]->link;
  	foreach ($link_holder as $link_item) {
	  $link_list[(string)$link_item[@href]] = (string)$link_item;
  	}
  }
  $temp[0] = $headline;
  $temp[1] = $text;
  $temp[2] = $list;
  $temp[3] = $link_list;
  return $temp;
}
$currentTab = $_GET['currentTab'];
$removal_date=$_GET['removal_date'];
$removal_type=$_GET['removal_type'];
if (file_exists('removalTips.xml')) {
  $xml = simplexml_load_file('removalTips.xml');
  $pdf_data = prepare_PDF_data($xml, $currentTab, $removal_date, $removal_type);
} else {
    exit('Konnte removalTips.xml nicht öffnen.');
}
  
class PDF extends FPDF {
	function Header() {
		if ($this->page  == 1) {
		  $this->Image('pdf_header_big.jpg', 0, 0, 210,70);
		  $this->Ln(70);
		} else {
		  $this->Image('pdf_header2.jpg',0, 10, 190, 20);
		  $this->Ln(40);
		}
	}
	function Footer() {
	    $this->SetY(-15);
	    $this->SetX(115);
	    $this->SetFont('Arial','B',12);
	    $this->SetTextColor(231, 92, 0);
	    $this->Cell(50, $cell_height, "www.immobilienscout24.de  |", 0, 0,'L', 0, "http://www.is24.de");
	    $this->SetY(-14);
      	$this->SetX(178);
	    $this->SetTextColor(1, 51, 106);
	    $this->Write(3,$this->PageNo().'/{nb}');
	}
    function SetLineStyle($style) {
        extract($style);
        if (isset($width)) {
            $width_prev = $this->LineWidth;
            $this->SetLineWidth($width);
            $this->LineWidth = $width_prev;
        }
        if (isset($cap)) {
            $ca = array('butt' => 0, 'round'=> 1, 'square' => 2);
            if (isset($ca[$cap]))
                $this->_out($ca[$cap] . ' J');
        }
        if (isset($join)) {
            $ja = array('miter' => 0, 'round' => 1, 'bevel' => 2);
            if (isset($ja[$join]))
                $this->_out($ja[$join] . ' j');
        }
        if (isset($dash)) {
            $dash_string = '';
            if ($dash) {
                if(ereg('^.+, ', $dash))
                    $tab = explode(', ', $dash);
                else
                    $tab = array($dash);
                $dash_string = '';
                foreach ($tab as $i => $v) {
                    if ($i > 0)
                        $dash_string .= ' ';
                    $dash_string .= sprintf('%.2f', $v);
                }
            }
            if (!isset($phase) || !$dash)
                $phase = 0;
            $this->_out(sprintf('[%s] %.2f d', $dash_string, $phase));
        }
        if (isset($color)) {
            list($r, $g, $b) = $color;
            $this->SetDrawColor($r, $g, $b);
        }
    }
    function Line($x1, $y1, $x2, $y2, $style = null) {
        if ($style)
            $this->SetLineStyle($style);
        parent::Line($x1, $y1, $x2, $y2);
    }
}

  $pdf = new PDF('P', 'mm', 'A4');
  $pdf->AliasNbPages();
  $pdf->SetAutoPageBreak(true, 40);
  $pdf->SetCreator( 'ImmoScout24' );
  $pdf->AddPage();
  $pdf->x = 15;
  $pdf->SetMargins(15,0,15);
  #print_r($pdf_data);
  $timestamp = time();
  $line_style = array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => '5, 2', 'phase' => 0, 'color' => array(219, 219, 219));
  foreach($pdf_data as $key => $value){
  	$item_date_out;
  	if ( $key < ($timestamp - 86400) ) {
  		$pdf->SetTextColor(231, 92, 0);
  		$item_date_out = "Baldmöglichst";
  	} else {
  		$pdf->SetTextColor(88, 88, 88);
  		$item_date_out =  date("d.m.Y", $key);
  	}
  	$pdf->SetFont('Arial','B',12);
  	if ( $pdf->y > 240) {
	  $pdf->AddPage(); 
  	}
  	$pdf->Cell(50, 6, utf8_decode($item_date_out), 0, 0,'L', 0);
  	$pdf->SetTextColor(1, 51, 106);
  	$pdf->SetFontSize(14);
  	$pdf->Cell(50, 6, utf8_decode($value[0]), 0, 1,'L', 0); 
  	$pdf->SetX(65); 
  	$pdf->SetFontSize(12);
  	if (strlen($value[1]) > 0){
  		$pdf->MultiCell(120, 6, utf8_decode($value[1]), 0, 'L');
  	}
  	foreach($value[2] as $list_item){
  		$pdf->SetX(65);
  		$pdf->MultiCell(120, 6, "- ".utf8_decode($list_item), 0, 'L');
  	}
  	if (count($value[3]) > 0) {
  		$pdf->Ln(3);
	  	$pdf->SetTextColor(231, 92, 0);
	  	foreach($value[3] as $key => $value){
	  	  $pdf->SetX(64);
	  	  $pdf->Image('arrow.gif', ($pdf->x) +2, ($pdf->y) + 2, 2, 2);
	  	  $pdf->Cell(50, 6, utf8_decode($value), 0, 1,'L', 0, $key);
	  	}
  	}
  	#print_r("huhu ".$y);
  	#print_r("<br/>");
  	#$pdf->SetY(10);
  	#$pdf->Cell(50, 6, '', 0, 1,'L', 0);
  	$pdf->Ln(7);
  	$pdf->Line(15, $pdf->y , 193 , $pdf->y, $line_style);
  	$pdf->Ln(7);
  }
  $pdf->Output();
?>
    
   