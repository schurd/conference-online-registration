<?php
/**
* This script creates a PDF confirmation page for Mission Net exhibitor applicants
* OM - Germany
* Autor: Samuel Gyger, adapted for Mission Net by Dietmar Schurrr
*
* WARNING: If the variables are coded as UTF 8 the has to be decoded with utf8_decode();
*	   FPDF does not support UTF8
*/


function create_pdf(&$data, $id, $pdfdatei, $preis, $land)
{
//Import der FPDF LIB
require_once('registration.pdf/fpdf.php');

  class myPDF extends FPDF
  {
    function Header()
    {
        $this->SetFont('Arial','',22);
        //Setze Position
        $this->SetX(20);
        // Farbe von Rahmen, Hintergrund und Text
        $this->SetDrawColor(0,0,0);
        $this->SetFillColor(180,180,180);
        $this->SetTextColor(0,0,0);
        // Dicke des Rahmens (1 mm)
        $this->SetLineWidth(0.7);
        // Titel
        $this->Cell(175,10, T_("Registration for mission-net 2009"),1,1,'C',1);
        // Zeilenumbruch
        $this->Ln(10);
    }

    function Footer() 
    { 
        // Ãber dem unteren Seitenrand positionieren 
        $this->SetY(-15); 
        // Schriftart festlegen
        $this->SetFont('Arial','I',8); 
    }  
  } 
  global $iban;
///*DEBUG
//print_r($data);
$var_prename = $data["firstname"];
$var_familyname = $data["lastname"];
$var_street = $data["street"];
$var_plz = $data["plzort"]["postcode"];
$var_city = $data["plzort"]["city"];

// Find out what type of participant we are dealing with (normal=1, Service Team.....)

$pdf=new myPDF('P','mm','A4'); //A4, mm und Hochformat
$pdf->AddPage(); //Neue Seite Hinzufuegen
$pdf->SetLeftMargin(20); //linker Rand 20 mm

$pdf->SetAuthor("Mission-Net");
$pdf->SetCreator("Mission-Net");
$st_pdf_keywords= 'Reg.-No.: ' . $var_registrationnr;
$pdf->SetKeywords($st_pdf_keywords);
$pdf->SetSubject("Mission-Net Registration");
$pdf->SetTitle("Registration for Mission-Net 2009");

//######################HEADER###########
//---------------------------------------
//      Information und Logo
//---------------------------------------
	//y-Position setzten
	$pdf->SetY(25);
	
	//Setzten des Teenstreets Logo
	$pdf->SetX(20);
	$pdf->Image("images/MN_Logo_kleiner.png",$pdf->GetX(), $pdf->GetY(), 60, 20, 'png', 'http://www.mission-net.org');
	
	//Zweites Logo
	$pdf->SetY(25);
	$pdf->SetX(135);
	$pdf->Image("images/MN_Logo_kleiner.png",$pdf->GetX(), $pdf->GetY(), 60, 20, 'png', 'http://www.mission-net.org');
	
	//Linie
	$pdf->setLineWidth(1);
	$pdf->Line(20,45,195,45);
	
	//Cell, Anmeldung gueltig
	$pdf->SetY(50);
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(0,1, "Please print this page, sign it and send it to us, if you are under 18 years old.",0,0,'R');
	
//#################HEADER END############
//#################Mission-Net ADDRESS#######

	$pdf->SetX(20);
	$pdf->SetY(55);
	$pdf->SetFont('Arial','B',12);
	$pdf->Write(5,"Mission-Net",0,'L');
	$pdf->ln();
	$pdf->Write(5,"Registration",0,'L');
	$pdf->ln();
	$pdf->Write(5,"Alte Neckarelzerstr. 2",0,'L');
	$pdf->ln();
	$pdf->Write(5,"D-74821 Mosbach",0,'L');
	$pdf->ln();
	$pdf->Write(5,"Germany",0,'L');

        $pdf->SetY(60);
        $pdf->SetFont('Arial','',12);
        $pdf->SetX(110);
	$pdf->Write(5, "by fax: +49 6261 947 147");
	$pdf->ln();
        $pdf->SetX(110);
	$pdf->Write(5, "by email: registration@mission-net.org");
	
	$pdf->SetX(20);
	$pdf->SetY(100);
	$pdf->Write(5, "The registration is only valid after we received this page (if you are 18 years) and the payment has been made.");
	//Notfalladresse
	$pdf->SetY(110);
	$pdf->SetX(130);
	$pdf->Write(5, "Emergency contact");
	$pdf->ln();
	$pdf->ln();
	$pdf->SetX(130);
	$pdf->Write(5,utf8_decode($data["emergency_firstname"]) . " " . utf8_decode($data["emergency_lastname"]));
	$pdf->ln();
	$pdf->SetX(130);
	$pdf->Write(5,utf8_decode($data["emergency_phone"]));

//################ADDRESS END#####

//################REGISTRATION############
	$pdf->SetY(110);
	$pdf->SetX(20);
	$pdf->Write(5, "Hereby I register for Mission-Net 2009:");
	$pdf->Ln();
	$pdf->Ln();
	//Prename, Familyname- Unterlined
	$pdf->SetFont('Arial','B',14);
	$pdf->Write(5, utf8_decode($var_prename));
	$pdf->Write(5," ");
	$pdf->Write(5, utf8_decode($var_familyname));
	//Address
	$pdf->Ln();
	$pdf->Write(5, utf8_decode($var_street) . "\n");
	$pdf->Write(5,$var_plz);
	$pdf->Write(5," ");
	$pdf->Write(5, utf8_decode($var_city));
	$pdf->Ln();
	$pdf->Write(5, utf8_decode($land));
	$pdf->Ln();
	$pdf->Write(5, date("d.m.Y",time()));
	$pdf->Write(5," / ");
	$pdf->Write(5,"Reg.-No.: " . $id);
	$pdf->Ln(10);
	$pdf->SetFont('Arial','',12);
	$pdf->Ln(10);
	$pdf->Write(5, "With my signature and the signature of my parents (if participant is under 18 years) we ensure that we have made only valid statements in the registration process.");
	$pdf->Ln();
	$pdf->Ln();
	$pdf->Write(5, "We have read the imprint and agree to them.");
	$pdf->Ln();
	$pdf->Ln();
	$pdf->Write(5, "The imprint can be downloaded again by the following link:");
	$pdf->Ln();
	//Link setzen.
	$pdf->Write(5, "www.mission-net.org/register/agb_en.pdf", "www.mission-net.org/register/agb_en.pdf");
	$pdf->ln(15);
	$pdf->Write(5, "Date and signature of participant:   __________________________");
	// if younger than 18 print the following line
	if ( strtotime($values["dateofbirth"]) < strtotime("08.04.1991") ) {
	  $pdf->ln(15);
	  $pdf->Write(5, "Date and signature of parents:       __________________________");
	}
//################SECOND PAGE FOR PAYMENT DETAILS################
	$pdf->AddPage();
	$pdf->SetLeftMargin(20);
        $pdf->SetY(25);
        $pdf->SetX(20);
        $pdf->Image("images/MN_Logo_kleiner.png",$pdf->GetX(), $pdf->GetY(), 60, 20, 'png', 'http://www.mission-net.org');

        $pdf->SetY(60);
        $pdf->SetX(20);
        $pdf->SetFont('Arial','B',15);
        $pdf->Cell(0,1, "Payment Instructions",0,0,'C');
        $pdf->SetFont('Arial','',12);
        $pdf->SetY(70);
        $pdf->Write(5, "There are two way of paying for Mission-Net 2009:");
        $pdf->Ln();
        $pdf->Write(5, " - Wire transfer of money to our bank account");
        $pdf->Ln();
        $pdf->Write(5, " - Credit card payment");
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Arial','B',15);
        $pdf->Write(5, "Wire transfer");
        $pdf->Ln();
        $pdf->SetFont('Arial','',12);
        $pdf->Write(5, "Please transfer the sum of");
        $pdf->SetX(100);
        $pdf->Write(5, $preis . " " . "Euro");
        $pdf->Ln();
        $pdf->Write(5, "to");
        $pdf->SetX(100);
        $pdf->Write(5, "OM Europa / Mission-Net");
        $pdf->Ln();
        $pdf->SetX(100);
        $pdf->Write(5, "Account no: 91-479018-6");
        $pdf->Ln();
        $pdf->SetX(100);
        $pdf->Write(5, $iban);
        $pdf->Ln();
        $pdf->SetX(100);
        $pdf->Write(5, "SWIFT CODE: POFICHBEXXX");
        $pdf->Ln();
        $pdf->SetX(20);
        $pdf->Write(5, "Address of bank:");
        $pdf->SetX(100);
        $pdf->Write(5, "Swiss Post / PostFinance / CH-3030 Bern");
        $pdf->Ln();
        $pdf->Write(5, "and use this reference:");
        $pdf->SetFont('Arial','B',12);
        $pdf->SetX(100);
        $pdf->Write(5, "M09-" . $id);
        $pdf->Ln();
	if ( $data["country"] == 3 ) {
	  if ( $data["part_type"] == 2 ) {
            $pdf->Image("images/EZ_ch_st.jpg",$pdf->GetX(), $pdf->GetY(), 120, 60, 'jpeg');
	  } else {
            $pdf->Image("images/EZ_ch_tn.jpg",$pdf->GetX(), $pdf->GetY(), 120, 60, 'jpeg');
	  }
          $pdf->SetY( $pdf->GetY() + 60 );
	}
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Arial','B',15);
        $pdf->Write(5, "Credit Card Payment:");
        $pdf->Ln();
        $pdf->SetFont('Arial','',12);
        $pdf->Write(5, "If you prefer to pay by credit or debit card card, we have to add a supplement of 10 Euro for the transaction.");
        $pdf->Ln();
        $pdf->Write(5, "Please transfer the sum of");
	$pdf->SetX(80);
	$gesamt = 10 + $preis;
        $pdf->Write(5, $preis . " Euro + 10 Euro = " . money_format('%i', $gesamt) . " "  . "Euro");
        $pdf->Ln();
	$plink = "https://register.mission-net.org/paypal.php?regi=" . $id;
        $pdf->Write(5, "by clicking this link:" . " " . $plink . " ", $plink);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Write(5, "Note: Your registration is only valid as soon as we received your payment.");
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Write(5, "You have to pay within 2 weeks after completing your online registration, otherwise the system will delete your registration automatically.");
//################REGISTRATION END########

//################OUTPUT###################
	//Output Name is $pdfdatei 
	$pdf->Output($pdfdatei,"F");
}
?>
