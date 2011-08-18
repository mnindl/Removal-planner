<?php
  
  define('FPDF_FONTPATH','../fpdf16/font/');
  require('../fpdf16/fpdf.php');
  
  class PDF extends FPDF {
    
  //Load data
  function LoadData($file) {
    //Read file lines
    $lines=file($file);
    $data=array();
    foreach($lines as $line)
      $data[]=explode(';',chop($line));
    return $data;
  }
  
  //Format data, ohne ext. Textdatei
  function FormatData($what) {
    //Read file lines
    $lines=explode("\n",$what);
    $data=array();
    foreach($lines as $line)
      $data[]=explode(';',chop($line));
    return $data;
  }
  
  //Simple table
  function BasicTable($header,$data) {
    //Header
    foreach($header as $col)
      $this->Cell(40,7,$col,1);
    $this->Ln();
    //Data
    foreach($data as $row)
    {
      foreach($row as $col)
        $this->Cell(40,6,$col,1);
      $this->Ln();
    }
  }
  
  //Better table
  function ImprovedTable($header,$data) {
    //Column widths
    $w=array(40,35,40,45);
    //Header
    for($i=0;$i<count($header);$i++)
      $this->Cell($w[$i],7,$header[$i],1,0,'C');
    $this->Ln();
    //Data
    foreach($data as $row)
    {
      $this->Cell($w[0],6,$row[0],'LR');
      $this->Cell($w[1],6,$row[1],'LR');
      $this->Cell($w[2],6,number_format($row[2]),'LR',0,'R');
      $this->Cell($w[3],6,number_format($row[3]),'LR',0,'R');
      $this->Ln();
    }
    //Closure line
    $this->Cell(array_sum($w),0,'','T');
  }
  
  //Colored table
  function FancyTable($header,$data,$gesamtNetto,$gesamtBrutto,$ust,$waehrung)
  {
    //Colors, line width and bold font
    $this->SetFillColor(204,204,204);
    $this->SetTextColor(0);
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.2);
    $this->SetFont('','B');
    //Header
    $w=array(35,35,40,40,32);
    $d = array('L','L','L','L','R');
    for($i=0;$i<count($header);$i++)
      $this->Cell($w[$i],7,$header[$i],1,0,$d[$i],1);
      
    $this->Ln();
    //Color and font restoration
    $this->SetFillColor(224,235,255);
    $this->SetTextColor(0);
    $this->SetFont('');
    //Data 
    $fill=0;
    foreach($data as $row)
    {
      $this->Cell($w[0],6,$row[0],'LR',0,'L',$fill);
      $this->Cell($w[1],6,$row[1],'LR',0,'L',$fill);
      $this->Cell($w[2],6,$row[2],'LR',0,'L',$fill);
      $this->Cell($w[3],6,$row[3],'LR',0,'L',$fill);
      $this->Cell($w[4],6,$row[4],'LR',0,'R',$fill);
      $this->Ln();
      $fill=!$fill;
    }
    
    # GesamtNetto
    $this->Cell($w[0],6,"",'T',0,'R',0);
    $this->Cell($w[1],6,"",'T',0,'R',0);
    $this->Cell($w[2],6,"",'T',0,'R',0);
    $this->Cell($w[3],6,"Summe: ",'T',0,'R',0);
    $this->Cell($w[4],6,number_format($gesamtNetto,2,",",".")." ".$waehrung."",'TB',0,'R',0);
    $this->Ln();
    
    # Wie isn das? Wenn nun gar keine UmSt. berechnet wird,
    # muss dann diese Spalte �berhaupt angezeigt werden?
    # GesamtMwSt
    #$gesamtMWST = ($gesamtNetto / 100) * $ust;
    $gesamtMWST = $gesamtBrutto - $gesamtNetto;
    $this->Cell($w[0],6,"",'',0,'R',0);
    $this->Cell($w[1],6,"",'',0,'R',0);
    $this->Cell($w[2],6,"",'',0,'R',0);
    $this->Cell($w[3],6,"+ ".$ust."% MwSt.: ",'',0,'R',0);
    $this->Cell($w[4],6,number_format($gesamtMWST,2,",",".")." ".$waehrung."",'B',0,'R',0);
    $this->Ln();
    
    # Gesamtbrutto
    $this->Cell($w[0],6,"",'',0,'R',0);
    $this->Cell($w[1],6,"",'',0,'R',0);
    $this->Cell($w[2],6,"",'',0,'R',0);
    $this->Cell($w[3],6,"Gesamtsumme: ",'',0,'R',0);
    $this->Cell($w[4],6,number_format($gesamtBrutto,2,",",".")." ".$waehrung."",'B',0,'R',0);
    $this->Ln(7);
    
    $this->Cell($w[0],6,"",'',0,'R',0);
    $this->Cell($w[1],6,"",'',0,'R',0);
    $this->Cell($w[2],6,"",'',0,'R',0);
    $this->Cell($w[3],6,"",'',0,'R',0);
    $this->Cell($w[4],6,"",'T',0,'R',0);
    $this->Ln();
    #$this->Cell(array_sum($w),0,'','T');
    
    }
    
    function Footer()
    {
      #global INVOICE_PDF_FOOTER;
      //Position 1,5 cm von unten
      $this->SetY(-15);
      //Arial kursiv 8
      $this->SetFont('Arial','',8);
      //Seitenzahl
      $this->MultiCell(0,3,INVOICE_PDF_FOOTER,0,'C',0);
    }
    
  }
  
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
  $pdf_data2 = array();
  $pdf_data3 = array();
  $pdf_data4 = array();
  $pdf_data_i = 0;
  $timestamp = time();
  print("</br>");
  foreach($removal_type_items as $item) {
    $removal_type_items_time[$ri] = (int) $item->attributes()->order;
    $ri++;
  }
  foreach($common_items as $item) {
    #echo "Attribute common: " . $item->attributes()->order;
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
    $item_date_out = ($item_date_ts < ($timestamp - 86400) ? "baldm&#246;glichst" : date("d.m.Y", $item_date_ts));
    $pdf_data1[$item_date_out] = $tip;
    print("</br>");
    print("</br>");
    print("</br>");
    print("</br>");
    if ($item_date_ts < $removal_date) {
      $pdf_data2[$item_date_out] = $pdf_data1[$item_date_out];
    }
    if ($item_date_ts >= $removal_date && $item_date_ts <= $removal_week_end_ts) {
      $pdf_data3[$item_date_out] = $pdf_data1[$item_date_out];
    }
    if ($item_date_ts > $removal_week_end_ts) {
      $pdf_data4[$item_date_out] = $pdf_data1[$item_date_out];
    }
    print_r($item_date_out);
    print("</br>");
    print_r($pdf_data1[$item_date_out]);
    
  }
  
}
function getTip($xml, $order, $removal_type) {
  $temp = array();
  $li = 0;
  $links_i = 0;
  $removal_tip = $xml->xpath("removalTipItem[@type='".$removal_type."'][@order='".$order."']");
  if (sizeof($removal_tip) < 1){
    $removal_tip = $xml->xpath("removalTipItem[@type='common'][@order='".$order."']");
  }
  $headline = (string)$removal_tip[0]->headline;
  $text;
  $list = array();
  $link_list = array();
  $link_list_hrefs = array();
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
	  $link_list[$links_i] = (string)$link_item;
      $link_list_hrefs[$links_i] = (string)$link_item[@href];
	  $links_i++;
  	}
  }
  $temp[0] = $headline;
  $temp[1] = $text;
  $temp[2] = $list;
  $temp[3] = $link_list;
  $temp[4] = $link_list_hrefs;
  return $temp;
}
  $currentTab = $_GET['currentTab'];
  $removal_date=$_GET['removal_date'];
  $removal_type=$_GET['removal_type'];
  #1314741600000
  #1314741600000
  print_r(date("d.m.Y", $removal_date));
  print_r($removal_date);
  print("</br>");
if (file_exists('removalTips.xml')) {
    $xml = simplexml_load_file('removalTips.xml');
    prepare_PDF_data($xml, $currentTab, $removal_date, $removal_type);
} else {
    exit('Konnte removalTips.xml nicht öffnen.');
}

  
  #$string = str_replace("<br />","\n",$rgRes->adresse);
  
  #$pdf=new PDF();
  #$pdf->AddPage();
  #$pdf->Output('temp/Umzugswagen-RGNR-'.$_REQUEST['rgnr'].'.pdf','I'); # <- im Browser anzeigen, Name klappt aber nicht.
  
?>
    
   