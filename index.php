<?php
	
	define('FPDF_FONTPATH','fpdf16/font/');
	require('fpdf16/fpdf.php');
	
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
		# muss dann diese Spalte überhaupt angezeigt werden?
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
	
	$string = str_replace("<br />","\n",$rgRes->adresse);
	
	$pdf=new PDF();
	$pdf->AddPage();
	
	$header=array('Gültigkeit','Stadt','Fahrzeugklasse','Tarif','Preis');

	#$dataQuery = mysql_query("SELECT * FROM ".$db_table_entries." WHERE anbieter_id='".$_SESSION['umzugswagen_partner_user']."' AND rgnr='".$_REQUEST['rgnr']."' AND deleted='0'");
	$output = ""; 
	$gesamtNetto = 0;
	#while($dataErg = mysql_fetch_object($dataQuery)) {
			
			#'Gültigkeit','Ort','Fzg.-Klasse','Tarif'
			$price = number_format($dataErg->price_for_entry,2,",",".");
			$output .= $dataErg->validity." Monate;".$dataErg->city.";".$arrFzgArt[$dataErg->fzg_art].";".$arrTarifArt[$dataErg->tarif_art].";".$price." ".$rgRes->waehrung."\n";
			$gesamtNetto += $dataErg->price_for_entry;
			
		#}
			
	$gesamtBrutto = (($gesamtNetto / 100) * $kundenErg->ust) + $gesamtNetto;
	
	# das letzte \n rausnehmen
	$output = substr($output,0,-1);
	#echo $output;
	
	#$data = $pdf->LoadData('countries.txt');
	$data = $pdf->FormatData($output);
	#var_dump($data);exit;
	$pdf->SetMargins(14,0,20);
	$pdf->SetFont('Arial','IB',26);
	$pdf->SetTextColor(204,51,150); 
	$pdf->Write(5, " Umzugswagen"); 
	$pdf->SetTextColor(0,169,199); 
	$pdf->Write(5, ".info"); 
	
	$pdf->Ln(-1); 
	$pdf->SetFont('Arial','',8);
	$pdf->SetTextColor(0, 0, 0);
	$pdf->MultiCell( 183, 4, INVOICE_PDF_HEAD_ADDRESS , '0', 'R', 0);
	#$pdf->MultiCell( 180, 5, $head, '', 'R', 0);
	
	$pdf->SetTextColor(0, 0, 0); 
	$pdf->Ln(28); 
	$pdf->SetFont('Arial','',10);
	$pdf->MultiCell( 180, 5, $string , '', 'L', 0);
	
	$pdf->Ln(15);
	$pdf->SetFont('Arial','B',10);
	$pdf->MultiCell( 100, 5, "Ihre Kundennummer: ".$kundenErg->kundennummer."\nRechnungsnummer: ".$_REQUEST['rgnr']."" , '0', 'L', 0);
	$pdf->Ln(-10);
	$pdf->MultiCell( 183, 5, "Hamburg, ".date("d.m.Y",$rgRes->stamp) , '0', 'R', 0);
	#$pdf->Cell(40,10,);
	$pdf->Ln(15);
	$pdf->FancyTable($header,$data,number_format($gesamtNetto,2,",","."),$gesamtBrutto,$kundenErg->ust,$kundenErg->waehrung);
	#$pdf->Footer($footer_text);
	#$pdf->MultiCell( 180, 5, $gesamtPreis , '', 'L', 0);
	
	#$pdf->Output('Umzugswagen-RGNR-'.$_REQUEST['rgnr'].'.pdf','D');
	$pdf->Output('temp/Umzugswagen-RGNR-'.$_REQUEST['rgnr'].'.pdf','I'); # <- im Browser anzeigen, Name klappt aber nicht.
	#$pdf->Output('temp/Umzugswagen-RGNR-'.$_REQUEST['rgnr'].'.pdf','F');
	
	#header("LOCATION: /partner/Umzugswagen-RGNR-".$_REQUEST['rgnr'].".pdf");
?>
    
   