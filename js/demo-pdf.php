<?php

	session_start();
	
	require('fpdf/fpdf.php');

	include("inc/globals.inc.php"); 
	include("inc/emoticons.plist.php"); 
	include("inc/functions.inc.php"); 
	include("inc/functions.strings.inc.php"); 
	include("inc/functions.calendar.inc.php");
	
	include("classes/class.user.php");
	include("classes/class.merkliste.php");
	
	$which = mb_strtolower($_REQUEST['which']);
	
	$user = new user();
	
	
	$artQuery = mysql_query("SELECT * FROM ".DB_TABLE_CONTENT." WHERE (headline_clean=".quote_smart($which)." OR linkurl=".quote_smart($which).") AND type='head' LIMIT 1",$db1);
	$artRes = mysql_fetch_object($artQuery);
	$text = getContent($artRes->id,true,true);
	$name = $artRes->headline;
	
	
	class myFPDF extends FPDF { 

		var $B;
		var $I;
		var $U;
		var $HREF;
		var $monate;
		
		function PDF($orientation='P',$unit='mm',$format='A4')
		{
			//Call parent constructor
			$this->FPDF($orientation,$unit,$format);
			//Initialization
			$this->B=0;
			$this->I=0;
			$this->U=0;
			$this->HREF='';
		}
		
		function WriteHTML($html)
		{
			//HTML parser
			$html=str_replace("\n",' ',trim($html));
			$a=preg_split('/<(.*)>/U',trim($html),-1,PREG_SPLIT_DELIM_CAPTURE);
			foreach($a as $i=>$e)
			{
				if($i%2==0)
				{
					//Text
					if($this->HREF)	{
						$this->Write(5," ");
						$this->PutLink($this->HREF,$e);
					}
				else
				{
						$this->Write(5,trim($e));
						}
				}
				else
				{
					//Tag
					if($e{0}=='/')
						$this->CloseTag(strtoupper(substr($e,1)));
					else
					{
						//Extract attributes
						$a2=explode(' ',$e);
						$tag=strtoupper(array_shift($a2));
						$attr=array();
						foreach($a2 as $v)
							if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$v,$a3))
								$attr[strtoupper($a3[1])]=$a3[2];
						$this->OpenTag($tag,$attr);
					}
				}
			}
		}
		 
		
		
		function OpenTag($tag,$attr)
		{
			//Opening tag
			if($tag=='B' or $tag=='I' or $tag=='U')
				if($tag=='H2') $tag = 'B';
				$this->SetStyle($tag,true);
			
			if($tag=='A')
				$this->HREF=$attr['HREF'];
			if($tag=='BR')
				$this->Ln(5);
		}
		
		function CloseTag($tag)
		{
			//Closing tag
			if($tag=='B' or $tag=='I' or $tag=='U')
				if($tag=='H2') $tag = 'B';
				$this->SetStyle($tag,false);
			if($tag=='A')
				$this->HREF='';
		}
		
		function SetStyle($tag,$enable)
		{
			//Modify style and select corresponding font
			$this->$tag+=($enable ? 1 : -1);
			$style='';
			foreach(array('B','I','U','H2') as $s)
				if($this->$s>0)
					$style.=$s;
			$this->SetFont('',$style);
		}
		
		function PutLink($URL,$txt)
		{
			//Put a hyperlink
			$this->SetTextColor(0,0,255);
			$this->SetStyle('U',true);
			$this->Write(5,$txt,$URL);
			$this->SetStyle('U',false);
			$this->SetTextColor(0);
		}

		function Header() { 
		
			if($this->PageNo() <= 1) {
			
				$this->Image('images/Umzug-de-Logo-PDF.jpg', 0, 0,210,26); 
				//Select Arial bold 15 
				$this->SetFont('Arial','B',23); 
				$this->SetTextColor(51,51,51);
				//Framed title 
				$this->Ln(0);
				$this->Cell(0,0,'',0,0,'L'); 
				
				//Line break 
				$this->Ln(9);
				$this->SetFont('Arial','B',12); 
				$this->SetTextColor(51,51,51);
				//Framed title 
				$this->Cell(0,0,'',0,0,'L');
			
			}
			
		} 

		//Fusszeile
		function Footer() {
			//Position 1,5 cm von unten
			$this->SetY(-15);
			//Arial kursiv 8
			$this->SetFont('Arial','I',8);
			//Seitenzahl
			//$html = "<span style=\"text-align:center;\">Ein Service von <a href=\"http://www.Umzug-Ratgeber.de\">www.Umzug-Ratgeber.de</a></span>";
			
			$this->Cell(0,10,$this->WriteHTML(PDF_FOOTER),0,0,'L');
			#$this->SetFont('Arial','I',8);
			$this->Cell(0,5,date("d. ",time()).$GLOBALS['monate'][date("n",time())].date(" Y - H:i",time())." Uhr / ".'Seite '.$this->PageNo().'  ',0,0,'R');
		}
		
		
	} 


$text = html_entity_decode(str_replace("<h2>","<B>",$text ));
$text = html_entity_decode(str_replace("</h2>","</B><br />",$text));
$text = html_entity_decode(str_replace("<h1>","<B>",$text ));
$text = html_entity_decode(str_replace("</h1>","</B><br />",$text));

$text = html_entity_decode(str_replace("<p>","",$text ));
$text = html_entity_decode(str_replace("</p>","<br /><br />",$text));

$pdf=new myFPDF('P','mm','A4');

$pdf->AddPage();

$pdf->Ln(22);
$pdf->SetFont('Arial','B',16);
$pdf->MultiCell(0,0,html_entity_decode(trim($res['headline']))); 
$pdf->Ln(10);
$pdf->SetFont('Arial','',12);
$pdf->WriteHTML($text);

#$pdf->SetStyle("h2", "Arial", "B", 13, "255, 255, 255", 0);
#$pdf->WriteTag(0, 10, $text, 1, "C", 1, 5);
#$pdf->MultiCell(0,6,$text,0,1,L);

$pdf->Output(''.$name.'.pdf','D');
?>