<?php 
if ($_SERVER['HTTPS'] == '') {
  header("Location: https://register.mission-net.org/staff.php"); 
}
require_once 'HTML/QuickForm/Controller.php'; 
require_once 'HTML/QuickForm/Action/Display.php'; 
require_once 'MDB2.php';
require_once 'Mail.php';
require_once 'Mail/mime.php';
require_once './config.php';
require_once './php-gettext-1.0.7/gettext.inc';
// start a session 
session_start(); 

$locale = "en";
$_GET["locale"] = "en";
require_once 'localization.php';
require_once 'create_pdf.php';

$mdb2 =& MDB2::singleton($dsn);

if (PEAR::isError($mdb2)) {
   die("Error while connecting : " . $mdb2->getMessage());
}
// correct $resi to a two character code
if (is_numeric($_SESSION["resi"])) {
   $resinum = $mdb2->queryOne('SELECT iso_code FROM countries WHERE id = ' . $_SESSION["resi"]);
   if (PEAR::isError($resinum)) {
      die ($resinum->getMessage());
   }
   $_SESSION["resi"] = $resinum;
}

// lets define a rule in a fuction
function nichtleer($felder) 
{
  $wert2 = trim($felder[1]); 
  if ( $felder[0] == "1" && $wert2 == "" ) {
     return false;
  } else { return true; }
} 

  $mdb2 =& MDB2::singleton(); 
  // List der Länder laden
  $work_sql = 'SELECT id, name, iso_code, fee, service_team_fee FROM countries ORDER by name';
  $c_arr = array();
  $fee_arr = array();
  $country = substr(strtoupper($_SESSION["resi"]), -2, 2);
  $erg =& $mdb2->query($work_sql);
  if (PEAR::isError($erg)) {
      die ($erg->getMessage());
  }
  while (($row = $erg->fetchRow())) {
      $c_arr[$row[0]] = $row[1];
      $fee_arr[$row[2]] = array($row[3], $row[4], $row[0]);
  }
  $erg->free();
  $cost_hint = 'Cost for accommodation, food and program (without travel): ' . '<b>' . $fee_arr[$country][0] . ' Euro</b>'; 

// class for the first page 
class Form_Personal extends HTML_QuickForm_Page 
{ 
   // buildForm defines the form 
   public function buildForm() 
   { 
      $this->_formBuilt = true; 
      // create Form 
      global $c_arr;
      global $fee_arr;
      global $cost_hint;
      global $country;
      $this->addElement('header', null, 'Registration for Staff at Mission-net 2009 - page 1 of 3');
      $cost_hint2 = "<SPAN ID='preis'>" . $cost_hint . "</SPAN>";
      $this->addElement('select', 'parttype', 'I will join the conference as or will work in (please choose):',
        array('10'=>'Supervisor in:', '11'=>'Band', '12'=>'Speaker', '13'=>'Logistics', '14'=>'National Motivator for:', 
	'15'=>'Programme', '16'=>'Others:'));
      $this->addElement('text', 'staff_text', "Details for your job/area:", array('size' => 55, 'maxlength' => 70, 'title'=>'Please give details about your job at Mission-Net'));

      $this->addElement('select', 'country', 'Country of residence:', $c_arr, 
	"title='" . 'Please choose your country of residence' . 
	"' onChange='Gang(document.seite1.country.value);'" );
      $this->setDefaults(array('country' => $fee_arr[$country][2]));

      $this->addElement('static', 'price_hint', 'Price for congress', $cost_hint2);
      $this->addElement('header', null, 'Personal data');
      $this->addElement('text', 'firstname', 'Firstname:', array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'lastname', "Lastname:", array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'preferredname', 'Preferred name:', array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'title', 'Title:', array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'street', 'Street:', array('size' => 40, 'maxlength' => 55));
        $adresse[]=HTML_QuickForm::createElement('text','postcode','Postcode:',array('size' => 7, 'maxlength' => 7));
        $adresse[]=HTML_QuickForm::createElement('text','city','Town:',array('size' => 30, 'maxlength' => 40));
      $this->addGroup($adresse,'plzort', 'Postcode and Town:');

      $this->addElement('text', 'phone', 'Phone incl. country code:', array('size' => 20, 'maxlength' => 20));
      $this->addElement('text', 'handy', 'Mobile incl. country code:', array('size' => 20, 'maxlength' => 20));
      $this->addElement('text', 'email', 'E-mail:', array('size' => 20, 'maxlength' => 40));
	$s_arr = array(1 => 'single', 2 => 'engaged', 
	3 => 'married', 4 => 'divorced', 5 => 'widowed');
      $this->addElement('select', 'maritalstatus', 'Marital Status:', $s_arr);
	unset($s_arr);
      $this->addElement('select', 'gender', 'Gender:', array('0'=>'undefined','f'=>'female','m'=>'male'));

      $this->addElement('header', null, 'Passport details');
      $this->addElement('text', 'passportname', 'Full name if different from above:', array('size' => 40, 'maxlength' => 110));

      $this->addElement('date', 'dateofbirth', 'Date of birth:', array('language' => 'en', 'format' => 'dMY', 'minYear' => 1920, 'maxYear'=>2008, 'addEmptyOption'=>true));
      $this->addElement('text', 'passportno', 'Passport No.:', array('size' => 40, 'maxlength' => 55));
      $this->addElement('date', 'dateofissue', 'Passport date of issue:', array('language' => 'en', 'format' => 'dMY', 'minYear' => 1990, 'maxYear'=>2008));
      $this->addElement('date', 'dateofexpire', 'Passport date of expire:', array('language' => 'en', 'format' => 'dMY', 'minYear' => 2007, 'maxYear'=>2029));

      $this->addElement('select', 'nationality', 'Nationality:', $c_arr);
	unset($s_arr);
      $this->addElement('select', 'invitationletter', 'Do you need a letter of invitation for Germany?', 
	array('0'=>'No','1'=>'Yes'));

      $this->addElement('header', null, 'Emergency Contact');
      $this->addElement('static','emergency_hint','Emergency Information',
	'In case of an emergency case we need the address of a contact person');
      $this->addElement('text', 'emergency_firstname', 'Firstname:', array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'emergency_lastname', 'Lastname:', array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'emergency_phone', 'Phone incl. country code:', array('size' => 30, 'maxlength' => 45));

      $this->addElement('submit', $this->getButtonName('next'), 'Proceed to next page'); 

      // Regel hinzufuegen 
      $this->registerRule('rule_nichtleer', 'callback', 'nichtleer');
      $this->addRule(array('medication', 'what_medication'), 'Please state what medication you require','rule_nichtleer');
      $this->addRule(array('invitationletter', 'passportno'), 'We need to have your passport details if you need a letter of invitation','rule_nichtleer');
      $this->addRule('nationality', 'Please enter your nationality', 'required',null);
      $this->addRule('nationality', 'Please enter your nationality', 'nonzero',null);
      $this->addRule('firstname', 'Please enter your firstname', 'required',null);
      $this->addRule('firstname', 'Please enter letters only', 'nopunctuation', null);
      $this->addRule('lastname', 'Please enter your lastname', 'required',null);
      $this->addRule('lastname', 'Please enter letters only', 'nopunctuation',null);
      $this->addRule('street', 'Please enter street', 'required',null);
      $this->addRule('email', 'Please enter your e-mail', 'required',null);
      $this->addRule('email', 'Please enter a valid e-mail address', 'email',null);
      $this->addGroupRule('dateofbirth', 'Please enter your birth date', 'required');
      $this->addGroupRule('plzort', 'Please enter postcode and town', 'required', 'server', 2);
      $this->addGroupRule('plzort', array('postcode' => array(        // Rules for the postcode
        array('Please enter a postcode','required')
        ),
        'city' => array( //Rules for the town
        array('Please enter town','required'),
        array('The name of the town is too short','minlength',2),
        )
     ));
      $this->addRule('phone', 'Please enter your phone number', 'required',null);
      $this->addRule('gender', 'Please choose your gender', 'required',null);
      $this->addRule('gender', 'Please choose your gender', 'lettersonly');
      $this->addRule('country', 'Please choose your country of residence', 'required');
      $this->addRule('country', 'Please choose your country of residence', 'nonzero');
      $this->addRule('emergency_firstname', 'Please enter firstname', 'required',null);
      $this->addRule('emergency_lastname', 'Please enter lastname', 'required',null);
      $this->addRule('emergency_phone', 'Please enter emergency phone number', 'required',null);

      $this->applyFilter('__ALL__','trim');

      // what happen if the form is not send via the submit button 
      $this->setDefaultAction('upload'); 
   } 
} 

// class for the second page 
class Form_Motivation extends HTML_QuickForm_Page 
{ 
   function buildForm() 
   { 
      $this->_formBuilt = true; 
      $mdb2 =& MDB2::singleton();

      $this->addElement('header', null, 'Registration for Staff at Mission-net 2009 - page 2 of 3');

      $this->addElement('header', null, 'Other personal data');

      $this->addElement('static','dietary_hint','Dietary Information',
        'Wholefood and vegeterian food will be provided, but we can not provide any other diet!');

      $this->addElement('header', null, 'Accommodation');
      $this->addElement('select', 'exhib_acco', 'Will you stay in an accommodation off-site(self-organized)?',
        array('0'=>'No, we stay onsite','1'=>'Yes, we stay offsite'));

      //Buttons hinzufuegen 
      $navi[] = $this->createElement('submit', $this->getButtonName('back'), 'Back to previous page'); 
      $navi[] = $this->createElement('submit', $this->getButtonName('next'), 'Proceed to next page'); 
      $this->addGroup($navi, null, '', '&nbsp;'); 

      $this->applyFilter('__ALL__','trim');
      $this->setDefaultAction('next'); 
   } 
} 
 

// Class for rendering the form
class ActionDisplay extends HTML_QuickForm_Action_Display 
{ 
   // overwrite Methode _renderForm() 
   public function _renderForm($page) 
   { 
      $renderer = $page->defaultRenderer(); 
 
    // RequiredNote setzen; hier koennen auch Templates zugewiesen werden 
	$rq_note = '<span style="color:F00">*</span> Denotes a required field';
    $page->setRequiredNote($rq_note); 
      // Renderer-Objekt an ActionDisplay uebergeben 
      $page->accept($renderer);
      echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">
  	<html>
  	<head>
    	  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";
      echo "<title>" . "Mission-net Staff Online Registration 2009" . "</title>";
	global $fee_arr;
	global $cost_hint;
	reset($fee_arr);
      echo "  <style type=\"text/css\">
                @import url(\"formate.css\");
          </style>
	<script type=\"text/javascript\">
	var cost_hint = \"" . $cost_hint . "\";\n
	var preise = new Array();\n";
	while (list($key, $val) = each($fee_arr)) {
	    echo "preise[$val[2]] = new Object();\n";
	    echo "preise[$val[2]][0] = $val[0];\n";
	    echo "preise[$val[2]][1] = $val[1];\n";
	}
      echo "  function Gang(wert) {
		land = document.seite1.country.value;
		typ = 0;
		var epreis = preise[land][typ] + \" Euro\";
		var cost_hint2 = cost_hint.replace(/\\d+ Euro/g, epreis);
		if (document.all) {
		   document.all(\"preis\").innerHTML = cost_hint2
	        } else {
		document.getElementById(\"preis\").innerHTML = cost_hint2
		}
          }
	</script>
  	</head>
  	<body><div class=\"main\"><div class=\"site\">
  	<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
	<tbody><tr>
  	 <td class=\"white-cell\">
   	 <img src=\"images/MN_Logo_kleiner.png\" alt=\"Mission-net Logo\" width=\"160\" height=\"90\">
   	 </td><td class=\"title-cell\">Mission-Net<br>8. - 13. April 2009<br>Oldenburg<br>Germany
  	 </td><td style=\"color:red\">Registration for Staff</td>
  	 </tr></tbody>
  	</table>"; 
      echo $renderer->toHtml(); 
      echo "</body></html>";
   } 
} 


// Class for the fourth page (payment details)
class Form_Bankdaten extends HTML_QuickForm_Page
{
   function buildForm()
   {
      $this->_formBuilt = true;
      $mdb2 =& MDB2::singleton();
      $this->addElement('header', null, 'Registration for staff at Mission-net 2009 - page 3 of 3');

      $this->addElement('header', null, 'Hints and Conditions');
      $this->addElement('static','dietary_hint','Dietary Information', 
        'Wholefood and vegeterian food will be provided, but we can not provide any other diet!');

      $agb_html='<iframe src="./' . T_('agb_en.html') . '" width="100%" height="250" name="agb_in_a_box">';
      $agb_html.= "<p>Your browser cannot display embedded frames:";
      $agb_html.= " " . "You may view the Terms and Conditions via this Link:";
      $agb_html.= '<a href="./' . T_('agb_en.html') . '">' . 'Terms and Conditions</a></p></iframe>';
      $this->addElement('static','text', 'Terms and Conditions', $agb_html);
      $this->addElement('advcheckbox', 'agb', 'Agreement:', 'I agree to the Terms and Conditions above unconditionally', null, array('No', 'Yes'));
      $this->addRule('agb', 'Your agreement to the terms and conditions is inevitable','regex','/^Yes$/');
      $pay_text = 'There two ways of paying for Mission-Net 2009:' . "<br><ul><li>";
      $pay_text.= 'Wire transfer of money to our bank account' . "</li><li>" . 'Credit card payment';
      $pay_text.= "</li></ul>" . 'If you prefer to pay by credit or debit card, we have to add a supplement of 10 Euro for the transaction.';
      $this->addElement('static', 'pay_hint', 'Payment Instructions', $pay_text);
      $this->addElement('static', 'pay_hint2', 'Note', 'Your registration is only valid as soon as we received your payment.');
      $this->addElement('static', 'pay_hint2', '', 'You have to pay within 2 weeks of completing your registration, otherwise the system will delete your registration automatically.');

      // add the buttons
      $navi[] = $this->createElement('submit', $this->getButtonName('back'), 'Back to previous page');
      $navi[] = $this->createElement('submit', $this->getButtonName('next'), 'submit registration');
      $this->addGroup($navi, null, '', '&nbsp;');
      $this->applyFilter('__ALL__','trim');
      $this->setDefaultAction('next');
   }
}

// Klasse zur Verarbeitung der Formulare 
class ActionProcess extends HTML_QuickForm_Action 
{ 
   // Methode zum Verarbeiten der Daten 
   function perform($page, $actionName) 
   { 
      // Auslesen der Daten 
      global $registrationhandleraddress;
      global $registrationsenderaddress;
      global $infomailaddress;
      // function to correct the date format
      function korr_datum($datum1)
      {
      	$datum = $datum1['Y'];
      	  if (strlen($datum1['M']) < 2) {
            $datum .= "-0" . $datum1['M'];
      	  } else {
            $datum .= "-" . $datum1['M'];
      	  }
      	 if (strlen($datum1['d']) < 2) {
            $datum .= "-0" . $datum1['d'];
      	 } else {
            $datum .= "-" . $datum1['d'];
      	 }
	return $datum;
      }

      $values = $page->controller->exportValues(); 

      $mdb2 =& MDB2::singleton();

      if (PEAR::isError($mdb2)) {
        die("Error while connecting : " . $mdb2->getMessage());
      }

      $sql1 = 'INSERT INTO participants SET firstname = ?, lastname = ?, preferred_name = ?, title = ?, street = ?,
		postcode = ?, city = ?, country = ?, phone = ?, mobile = ?, email = ?, dateofbirth = ?, maritalstatus = ?, gender = ?,
		passport_name = ?, passport_no = ?, passport_dateofissue = ?, passport_dateofexpire = ?,
		nationality = ?, invitation_letter = ?, emergency_firstname = ?, emergency_lastname = ?, emergency_phone = ?,
		special_job = ?, sj_reason = ?, part_type = ?, status = ?';
      $sth = $mdb2->prepare($sql1, $typen, MDB2_PREPARE_RESULT);

      if (PEAR::isError($mdb2)) {
        die("Error while preparing : " . $mdb2->getMessage());
      }

      $daten = array(utf8_decode($values['firstname']),utf8_decode($values['lastname']),
	utf8_decode($values['preferredname']),utf8_decode($values['title']),
	utf8_decode($values['street']),$values['plzort']['postcode'], utf8_decode($values['plzort']['city']), 
	$values['country'], $values['phone'], $values['handy'], $values['email'], 
	korr_datum($values['dateofbirth']), $values['maritalstatus'], 
	$values['gender'], utf8_decode($values['passportname']), utf8_decode($values['passportno']));

       $daten[] = korr_datum($values['dateofissue']);
       $daten[] = korr_datum($values['dateofexpire']);
       $daten[] = $values['nationality'];
       $daten[] = $page->controller->exportValue('seite1','invitationletter');

       $daten[] = utf8_decode($values['emergency_firstname']);
       $daten[] = utf8_decode($values['emergency_lastname']);
       $daten[] = $values['emergency_phone'];

       $daten[] = $values['parttype'];
       $daten[] = utf8_decode($values['staff_text']);
       $daten[] = '5';
       $daten[] = '1';	// status field

      $affRow=$sth->execute($daten);
      if (PEAR::isError($affRow)) {
        die("Error while executing : " . $affRow->getMessage());
      }

      $last_id = $mdb2->lastInsertID('participants', 'id');
      if (PEAR::isError($last_id)) {
           die('failed... Fehler:' . $last_id->getMessage());
         //  echo ($mdb2->getMessage().' - '.$mdb2->getUserinfo());
      }
      $sth->Free();
      $sql1 = "SELECT fee, name FROM countries WHERE id=" . $values['country'];
      $erg =& $mdb2->query($sql1);
        if (PEAR::isError($erg)) {
           die ($erg->getMessage());
        }
        while (($row = $erg->fetchRow())) {
                $preis = $row[0];
		$land_name = $row[1];
        }
      $erg->Free();
      $zufall = rand(100,999);
      $pdffile = 'pdfs/' . $zufall . '_' . $last_id . '_' . date("Ymd") . '.pdf';

      // now let's create a PDF file
      create_pdf($values, $last_id, $pdffile, $preis, $land_name);

      $text = T_("Dear") . " " . $values["firstname"] . "\n\n";
      $text.= T_("we are so excited that you have just registered for Mission-net in Oldenburg, 2009. We are looking forward to meeting you and 6000 others from all over Europe.");
      $text.= "\n \n";
      $text.= "You are in for a great time with great worship, speakers, and of course the chance to meet like minded people from all over Europe.";
      $text.= "\n \n";
      $text.= htmlentities(T_("We have a National Coordinator in your country who this message has also been sent to. Nearer the time they will be in contact with you to give you more information on others from your country that will be attending this exciting event, as well as practical details of things like travel and what you need to bring."));
      $text.= "\n \n";
      $text.= htmlentities(T_("In order for your registration to be processed please ensure that the congress fee is sent to the Bank (see details attached if not paid by credit card) and you send us the signed registration form. On receipt of payment, we will send you another confirmation e-mail that the money has received which will also be sent to the National Coordinator in your country."));
      $text.= "\n \n";
      $text.= T_("Please ensure that this happens so that we can process your registration as quickly as possible.");
      $text.= "\n \n";
      $text.= T_("Please find attached all necessary documents again in the PDF format.") . "\n";
      $text.= T_("You may download this PDF file right here:") . " ";
      $text.= "https://register.mission-net.org/" . $pdffile;
      $text.= "\n \n";
      $text.= "Thanks again for registering for Mission-Net, and we trust that this event will be an exciting new step in your journey in the Christian faith.";
      $text.= "\n \n \n";
      $text.= "Many Blessings";
      $text.= "\n \n";
      $text.= "Mission-Net Congress Management Team";
      $text.= "\n \n";
      $text.= "Mission-Net 2009 -- Alte Neckarelzer Str. 2 -- D-74821 Mosbach";
      $text.= "\n";
      $text.= $infomailaddress;


      $html = "Dear " . $values["firstname"] . "<br><br>\n";
      $html.= htmlentities(T_("we are so excited that you have just registered for Mission-net in Oldenburg, 2009. We are looking forward to meeting you and 6000 others from all over Europe."));
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("You are in for a great time with great worship, speakers, and of course the chance to meet like minded people from all over Europe."));
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("We have a National Coordinator in your country who this message has also been sent to. Nearer the time they will be in contact with you to give you more information on others from your country that will be attending this exciting event, as well as practical details of things like travel and what you need to bring."));
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("In order for your registration to be processed please ensure that the congress fee is sent to the Bank (see details attached if not paid by credit card) and you send us the signed registration form. On receipt of payment, we will send you another confirmation e-mail that the money has received which will also be sent to the National Coordinator in your country."));
      $html.= "\n<br><br>\n";
      $html.= "Please ensure that this happens so that we can process your registration as quickly as possible.";
      $html.= "\n<br><br>\n";
      $html.= "Please find attached all necessary documents again in the PDF format.<br>";
      $html.= "You may download this PDF file right here:" . " ";
      $html.= "<a href='https://register.mission-net.org/" . $pdffile . "'>PDF Document</a>";
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("Thanks again for registering for Mission-Net, and we trust that this event will be an exciting new step in your journey in the Christian faith"));
      $html.= "\n<br><br><br>\n";
      $html.= "Many Blessings";
      $html.= "\n<br><br>\n";
      $html.= "Mission-Net Congress Management Team";
      $html.= "\n<br><br>\n";
      $html.= "Mission-Net 2009 -- Alte Neckarelzer Str. 2 -- D-74821 Mosbach";
      $html.= "\n<br>";
      $html.= $infomailaddress;

      $crlf = "\n";

//######## Mail to registrant ##################
//##############################################
 	$mime = new Mail_mime($crlf);
       	$mime->setTXTBody($text);
        $mime->setHTMLBody($html);
        if (file_exists($pdffile)) {
              $mime->addAttachment($pdffile, 'application/pdf');
        }
        $message = $mime->get();
        $betreff = htmlentities(T_('Mission Net registration 2009')) . ' ' . $values['lastname'] . ', ' . 
		$values['firstname'] . ', ' . $values["nationality"];
        $headers=array( 'From' => $registrationsenderaddress,
            'To' => $registrationhandleraddress,
            'Subject' => $betreff);
        $hdrs = $mime->headers($headers);
        $empfaenger = $values["email"];

       // create the mail object using the Mail::factory method
       $mail_message =& Mail::factory('mail');
       $mail_message->send($empfaenger, $hdrs, $message);

       if (PEAR::isError($mail_message))
       { die ($mail_message->getMessage());
       }

//######## Mail to mission-net #################
//##############################################

      foreach ($daten as &$dval) {
      	$text.= "\n" . $dval;
	$html.= "\n<br>" . $dval;
      }
      $om_mime = new Mail_mime($crlf);
      $om_mime->setTXTBody($text);
      $om_mime->setHTMLBody($html);
      if (file_exists($pdffile)) {
           $om_mime->addAttachment($pdffile, 'application/pdf');
      }
      $om_message = $om_mime->get();
      $om_betreff = $values['firstname'] . ' ' . $values['lastname'] . ' hat sich angemeldet. No.: ' . $last_id;

      $om_headers=array( 'From' => $registrationsenderaddress,
           'To' => $registrationhandleraddress,
           'Subject' => $om_betreff);

      $om_hdrs = $om_mime->headers($om_headers);
      $om_mail_message =& Mail::factory('mail');
      $om_mail_message->send ($registrationhandleraddress, $om_hdrs, $om_message);
      if (PEAR::isError($om_mail_message))
      { die ($om_mail_message->getMessage());
      }

//#### here comes the resulting screen after the registration #
//#############################################################
      ?>
     <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
        <html>
        <head>
          <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
          <title>
	 <?php echo htmlentities(T_("Mission Net Online Registration")); ?>
	 </title>
        </head>
        <body>
        <table><tr>
         <td>
         <img src="images/MN_Logo_kleiner.png" alt="Mission-Net Logo">
         </td><td>
         <font size="-2">
	 <?php echo htmlentities(T_("Germany")) . "<br>" . htmlentities(T_("8. April to 13. April 2009")) . "<br>Oldenburg</font>"; ?>
         </td>
         </tr>
        </table>
	<h2>
	<?php 
	echo htmlentities(T_("Confirmation of registration for Mission-Net 2009"));
	echo '</h2>';
	$format = htmlentities(T_("Dear")) . " %1\$s";
	printf($format, $values["firstname"]);
	echo ", <br><br>\n";
	echo htmlentities(T_('we are so excited that you have just registered for Mission-net in Oldenburg, 2009. We are looking forward to meeting you and 6000 others from all over Europe.'));
	echo "\n<br><br>\n";
	echo htmlentities(T_('You are in for a great time with great worship, speakers, and of course the chance to meet like minded people from all over Europe.'));
	echo "\n<br><br>\n";
	echo htmlentities(T_("We have a National Coordinator in your country who this message has also been sent to. Nearer the time they will be in contact with you to give you more information on others from your country that will be attending this exciting event, as well as practical details of things like travel and what you need to bring."));
	echo "\n<br><br><br>\n";
	echo htmlentities(T_("In order for your registration to be processed please ensure that the congress fee is paid. On receipt of payment, we will send you another confirmation e-mail that the money has received which will also be sent to the National Coordinator in your country."));
	echo "\n<br><br>\n";
	echo htmlentities(T_("Please ensure that this happens so that we can process your registration as quickly as possible."));
	echo "\n<br><br>\n";
	echo htmlentities(T_("Thanks again for registering for Mission-Net and we trust that this event will be an exciting new step in your journey in the Christian faith."));
	echo "\n<br><br>\n";
	echo htmlentities(T_("Many Blessings"));
	echo "\n<br><br>\n";
	echo htmlentities(T_("Mission-Net Congress Management Team"));
	echo "\n<br><br>\n";
	echo "<b>" . htmlentities(T_("Payment Instructions")) . "</b><br>\n"; 
	echo htmlentities(T_("There are two ways of paying for Mission-Net 2009:")) . "<br>\n";
	echo "<ol><li>" . htmlentities(T_("Wire transfer of money to our bank account"))  . "</li>\n";
	echo "<li>" . htmlentities(T_("Credit Card Payment"))  . "</li></ol><br>\n";
	echo "<b>" . htmlentities(T_("Wire transfer:")) . "</b><br>\n"; 
	echo htmlentities(T_("Please transfer the sum of")) . " " . $preis . " " . T_("Euro"). "<br>\n";
	echo htmlentities(T_("to")) . "<br>" . T_("OM Europa / Mission-Net") . "<br>\n";
	echo htmlentities(T_("Account no:")) .  " 91-479018-6" . "<br>\n";
	global $iban;
	echo $iban . "<br>\n";
	echo T_("SWIFT CODE: POFICHBEXXX") . "<br>\n";
	echo T_("Address of bank:") . "Swiss Post / PostFinance / CH-3030 Bern" . "<br>\n";
	echo htmlentities(T_("and use this reference:")) . "<b> M09-" . $last_id . "</b><br>\n";
	echo "<br><b>" . htmlentities(T_("Credit Card Payment:")) . "</b><br>\n"; 
	echo htmlentities(T_("If you prefer to pay by credit or debit card, we have to add a supplement of 10 Euro for the transaction.")) . "<br>\n";
	$gsumme = $preis + 10;
	echo htmlentities(T_("Please transfer the sum of")) . " " . $preis . " Euro + " . T_("10 Euro"). 
	" = " . money_format('%i', $gsumme) . " " . htmlentities(T_("Euro")) . "<br>\n";
	echo htmlentities(T_("by clicking this link:"));
?>
<form method="post" action="https://www.paypal.com/cgi-bin/webscr" target="neu">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="congress-payment@mission-net.org">
<input type="hidden" name="item_name" value="Mission-Net event">
<input type="hidden" name="quantity" value="1">
<input type="hidden" name="bn"  value="ButtonFactory.PayPal.002">
<input type="hidden" name="currency_code" value="EUR">
<input type="hidden" name="on0" value="<?php echo $values['lastname'] . ', ' . $values['firstname']; ?>">
<input type="hidden" name="os0" value="">
<input type="hidden" name="on1" value="Reference No: <?php echo 'M09-' . $last_id; ?> ">
<input type="hidden" name="os1" value="">
<br>
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_paynow_LG.gif">
</form>
<?php
	echo htmlentities(T_("Note:")) . " " . htmlentities(T_("Your registration is only valid as soon as we received your payment.")) . "<br>\n";
	echo htmlentities(T_("You have to pay within 2 weeks of completing your registration (this is now), otherwise the system will delete your registration automatically")) . "<br>\n";
	session_destroy();
         } 
	} 
	 
	// Neue Formular-Objekte mit eindeutigem Namen ableiten 
	$seite1 = new Form_Personal('seite1'); 
	$seite2 = new Form_Motivation('seite2'); 
	$seite4 = new Form_Bankdaten('seite4'); 
	 
	// Neues Controller-Objekt ableiten 
	$controller = new HTML_QuickForm_Controller('mnformular', true); 
	 
	// Formularseiten hinzufuegen 
	$controller->addPage($seite1); 
 	$controller->addPage($seite2); 
 	$controller->addPage($seite4); 

// add the actions 
$controller->addAction('display', new ActionDisplay()); 
$controller->addAction('process', new ActionProcess()); 
 
// Controller ausfuehren 
$controller->run(); 

$mdb2->disconnect();
?>
