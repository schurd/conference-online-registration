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

// class for the first page 
class Form_Personal extends HTML_QuickForm_Page 
{ 
   // buildForm defines the form 
   public function buildForm() 
   { 
      $this->_formBuilt = true; 
      $mdb2 =& MDB2::singleton(); 
      // create Form 

	// List der Länder laden
      $work_sql = 'SELECT id, name, iso_code, fee, service_team_fee FROM countries ORDER by name';
      $c_arr['0'] = htmlentities(T_('undefined'));
      $fee_arr['0'] = htmlentities(T_('undefined'));
      $erg =& $mdb2->query($work_sql);
        if (PEAR::isError($erg)) {
           die ($erg->getMessage());
        }
        while (($row = $erg->fetchRow())) {
                $c_arr[$row[0]] = $row[1];
                $fee_arr[$row[2]] = array($row[3], $row[4], $row[0]);
        }
      $erg->free();
	
      $this->addElement('header', null, htmlentities(T_('Registration Mission-net 2009 - page 1 of 3')));
      $country = substr(strtoupper($_SESSION["resi"]), -2, 2);
      $cost_hint = htmlentities(T_('Cost for accomodation, food and program (without travel): ')) . '<b>' . $fee_arr[$country][0] . ' Euro</b>'; 
      $this->addElement('select', 'parttype', htmlentities(T_('I will join the conference as:')),
        array('4'=>htmlentities(T_('Staff'))),
        "title='" . htmlentities(T_('This registration page is for staff only')) .
        "' onChange='GehZu(document.seite1.parttype.value);'" );
      $this->setDefaults(array('parttype' => $_SESSION["part_type"]));

      $this->addElement('select', 'country', htmlentities(T_('Country:')), $c_arr, 
	"title='" . htmlentities(T_('Please choose your country of residence')) . 
	"' onChange='Gang(document.seite1.country.value);'" );
      $this->setDefaults(array('country' => $fee_arr[$country][2]));

      $this->addElement('static', 'price_hint', htmlentities(T_('Price for congress')), $cost_hint);
      $this->addElement('header', null, htmlentities(T_('Personal data')));
      $this->addElement('text', 'lastname', htmlentities(T_("Lastname:")), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'firstname', htmlentities(T_('Firstname:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'preferredname', htmlentities(T_('Preferred name:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'title', htmlentities(T_('Title:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'street', htmlentities(T_('Street:')), array('size' => 40, 'maxlength' => 55));
        $adresse[]=HTML_QuickForm::createElement('text','postcode','Postcode:',array('size' => 7, 'maxlength' => 7));
        $adresse[]=HTML_QuickForm::createElement('text','city','Town:',array('size' => 30, 'maxlength' => 40));
      $this->addGroup($adresse,'plzort', htmlentities(T_('Postcode and Town:')));

      $this->addElement('text', 'phone', htmlentities(T_('Phone incl. country code:')), array('size' => 20, 'maxlength' => 20));
      $this->addElement('text', 'handy', htmlentities(T_('Mobile incl. country code:')), array('size' => 20, 'maxlength' => 20));
      $this->addElement('text', 'email', htmlentities(T_('E-mail:')), array('size' => 20, 'maxlength' => 40));
	$s_arr = array(1 => htmlentities(T_('single')), 2 => htmlentities(T_('engaged')), 
	3 => htmlentities(T_('married')), 4 => htmlentities(T_('divorced')), 5 => htmlentities(T_('widowed')));
      $this->addElement('select', 'maritalstatus', htmlentities(T_('Marital Status:')), $s_arr);
	unset($s_arr);
      $this->addElement('select', 'gender', htmlentities(T_('Gender:')), array('0'=>htmlentities(T_('undefined')),
	'f'=>htmlentities(T_('female')),'m'=>htmlentities(T_('male'))));

      $this->addElement('header', null, htmlentities(T_('Passport details')));
      $this->addElement('text', 'passportname', htmlentities(T_('Full name if different from above:')), array('size' => 40, 'maxlength' => 110));

      $this->addElement('date', 'dateofbirth', htmlentities(T_('Date of birth:')), array('language' => 'en', 'format' => 'dMY', 'minYear' => 1920, 'maxYear'=>2008));
      $this->addElement('text', 'passportno', htmlentities(T_('Passport No.:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('date', 'dateofissue', htmlentities(T_('Passport date of issue:')), array('language' => 'en', 'format' => 'dMY', 'minYear' => 1990, 'maxYear'=>2008));
      $this->addElement('date', 'dateofexpire', htmlentities(T_('Passport date of expire:')), array('language' => 'en', 'format' => 'dMY', 'minYear' => 2007, 'maxYear'=>2029));

      $this->addElement('select', 'nationality', htmlentities(T_('Nationality:')), $c_arr);
	unset($s_arr);
      $this->addElement('select', 'invitationletter', htmlentities(T_('Do you need a letter of invitation for Germany?')), 
	array('0'=>htmlentities(T_('No')),'1'=>htmlentities(T_('Yes'))));

      $this->addElement('header', null, htmlentities(T_('Emergency Contact')));
      $this->addElement('static','emergency_hint',htmlentities(T_('Emergency Information')),
	htmlentities(T_('In case of an emergency case we need the address of a contact person')));
      $this->addElement('text', 'emergency_firstname', htmlentities(T_('Firstname:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'emergency_lastname', htmlentities(T_('Lastname:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'emergency_phone', htmlentities(T_('Phone incl. country code:')), array('size' => 30, 'maxlength' => 45));

      $this->addElement('submit', $this->getButtonName('next'), utf8_encode(T_('Proceed to next page'))); 

      // Regel hinzufuegen 
      $this->registerRule('rule_nichtleer', 'callback', 'nichtleer');
      $this->addRule(array('medication', 'what_medication'), htmlentities(T_('Please state what medication you require')),'rule_nichtleer');
      $this->addRule(array('invitationletter', 'passportno'), htmlentities(T_('We need to have your passport details if you need a letter of invitation')),'rule_nichtleer');
      $this->addRule('nationality', htmlentities(T_('Please enter your nationality')), 'required',null);
      $this->addRule('nationality', htmlentities(T_('Please enter your nationality')), 'nonzero',null);
      $this->addRule('firstname', htmlentities(T_('Please enter your firstname')), 'required',null);
      $this->addRule('firstname', htmlentities(T_('Please enter letters only')), 'nopunctuation', null);
      $this->addRule('lastname', htmlentities(T_('Please enter your lastname')), 'required',null);
      $this->addRule('lastname', htmlentities(T_('Please enter letters only')), 'nopunctuation',null);
      $this->addRule('street', htmlentities(T_('Please enter street')), 'required',null);
      $this->addRule('email', htmlentities(T_('Please enter your e-mail')), 'required',null);
      $this->addRule('email', htmlentities(T_('Please enter a valid e-mail address')), 'email',null);
      $this->addRule('dateofbirth', htmlentities(T_('Please enter your birth date')), 'required',null);
      $this->addGroupRule('plzort', htmlentities(T_('Please enter postcode and town')), 'required', 'server', 2);
      $this->addGroupRule('plzort', array('postcode' => array(        // Rules for the postcode
        array(htmlentities(T_('Please enter a postcode')),'required')
        ),
        'city' => array( //Rules for the town
        array(htmlentities(T_('Please enter town')),'required'),
        array(htmlentities(T_('The name of the town is too short')),'minlength',2),
        // array(htmlentities(T_('The town contains invalid characters')),'nopunctuation',null),
        )
     ));
      $this->addRule('phone', htmlentities(T_('Please enter your phone number')), 'required',null);
      $this->addRule('gender', htmlentities(T_('Please choose your gender')), 'required',null);
      $this->addRule('gender', htmlentities(T_('Please choose your gender')), 'lettersonly');
      $this->addRule('country', htmlentities(T_('Please choose your country of residence')), 'required');
      $this->addRule('country', htmlentities(T_('Please choose your country of residence')), 'nonzero');
      $this->addRule('emergency_firstname', htmlentities(T_('Please enter firstname')), 'required',null);
      $this->addRule('emergency_lastname', htmlentities(T_('Please enter lastname')), 'required',null);
      $this->addRule('emergency_phone', htmlentities(T_('Please enter emergency phone number')), 'required',null);

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

      $this->addElement('header', null, htmlentities(T_('Registration Mission-net 2009 - page 2 of 3')));

      $this->addElement('header', null, htmlentities(T_('Other personal data')));
      $this->addElement('textarea','other_conf', htmlentities(T_('Other mission conferences attended? Which ones?')),array('wrap'=>'soft','rows'=>'3','cols'=>'40'));

      $work_sql = 'SELECT id, name FROM languages ORDER by id';
      $erg =& $mdb2->query($work_sql);
        if (PEAR::isError($erg)) {
           die ($erg->getMessage());
        }
        while (($row = $erg->fetchRow())) {
                $s_arr[$row[0]] = $row[1];
        }

	$erg->Free();
      $this->addElement('select', 'mother_tongue', htmlentities(T_('Please select your mother tongue:')), $s_arr);
      unset($s_arr);
      $s_arr = array('0' => htmlentities(T_('brilliant')), '1'=>htmlentities(T_('fluent')),
      	'2'=>htmlentities(T_('basic')),'3'=>htmlentities(T_('non-existing')));
      $this->addElement('select', 'german_skill', htmlentities(T_('My german skills:')), $s_arr);
      $this->addElement('select', 'english_skill', htmlentities(T_('My english skills:')), $s_arr);
      $this->addElement('static','dietary_hint',htmlentities(T_('Dietary Information')),
        htmlentities(T_('Wholefood and vegeterian food will be provided, but we can not provide any other diet!')));

      $this->addElement('header', null, htmlentities(T_('Organisation details')));
      $this->addElement('text', 'exhib_name', htmlentities(T_('Name of Organisation:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'exhib_code', htmlentities(T_('Organisation ID Code:')), array('size' => 7, 'maxlength' => 20));
      $this->addElement('select', 'exhib_pay', 'Does your organisation pay one bill for all delegates?',
        array('0'=>'No, we pay individually','1'=>'Yes, one bill only'));

      $this->addElement('header', null, htmlentities(T_('Accomodation')));
      $this->addElement('select', 'exhib_acco', 'Will you stay in an accomodation off-site(self-organized)?',
        array('0'=>'No, we stay onsite','1'=>'Yes, we stay offsite'));


      //Buttons hinzufuegen 
      $navi[] = $this->createElement('submit', $this->getButtonName('back'), utf8_encode(T_('Back to previous page'))); 
      $navi[] = $this->createElement('submit', $this->getButtonName('next'), utf8_encode(T_('Proceed to next page'))); 
      $this->addGroup($navi, null, '', '&nbsp;'); 

      $this->addRule('exhib_name', htmlentities(T_('Please enter a name for your Organisation')), 'required');
      $this->addRule('exhib_code', htmlentities(T_('Please enter the Organisation ID Code')), 'required');
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
	$rq_note = '<span style="color:F00">*</span> ' . htmlentities(T_('Denotes a required field'));
    $page->setRequiredNote($rq_note); 
      // Renderer-Objekt an ActionDisplay uebergeben 
      $page->accept($renderer);
      echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">
  	<html>
  	<head>
    	  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";
      echo "<title>" . htmlentities(T_("Mission-net Online Registration 2009")) . "</title>";
      echo "  <style type=\"text/css\">
                @import url(\"formate.css\");
          </style>
	<script type=\"text/javascript\">
	  function GehZu(wert) {
		var ziel = \"https://register.mission-net.org/" . $_SERVER['PHP_SELF'] . "?part_type=\" + wert;
		window.location.href = ziel;
	  }
          function Gang(wert) {
                var ziel = \"https://register.mission-net.org/" . $_SERVER['PHP_SELF'] . "?resi=\" + wert;
                window.location.href = ziel;
          }
	</script>
  	</head>
  	<body><div class=\"main\"><div class=\"site\">
  	<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
	<tbody><tr>
  	 <td class=\"white-cell\">
   	 <img src=\"images/MN_Logo_kleiner.png\" alt=\"Mission-net Logo\" width=\"160\" height=\"90\">
   	 </td><td class=\"title-cell\">Mission-Net<br>8. - 13. April 2009<br>Oldenburg<br>Germany
  	 </td>
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
      $this->addElement('header', null, htmlentities(T_('Registration Mission-net 2009 - page 3 of 3')));

      $this->addElement('static','couns_hint',htmlentities(T_('Information')),
        htmlentities(T_('We will review your application, but can not guarantee cooperation! We will let you know until 2009.')));

      $this->addElement('header', null, htmlentities(T_('Hints and Conditions')));
      $this->addElement('static','dietary_hint',htmlentities(T_('Dietary Information')), 
        htmlentities(T_('Wholefood and vegeterian food will be provided, but we can not provide any other diet!')));

      $agb_html='<iframe src="./' . T_('agb_en.html') . '" width="100%" height="250" name="agb_in_a_box">';
      $agb_html.= "<p>" . htmlentities(T_("Your browser cannot display embedded frames:"));
      $agb_html.= " " . htmlentities(T_("You may view the Terms and Conditions via this Link:"));
      $agb_html.= '<a href="./' . T_('agb_en.html') . '">' . htmlentities(T_('Terms and Conditions')) . '</a></p></iframe>';
      $this->addElement('static','text', htmlentities(T_('Terms and Conditions')), $agb_html);
      $this->addElement('advcheckbox', 'agb', htmlentities(T_('Agreement:')), htmlentities(T_('I agree to the Terms and Conditions above unconditionally')), null, array('No', 'Yes'));
      $this->addRule('agb', htmlentities(T_('Your agreement to the terms and conditions is inevitable')),'regex','/^Yes$/');
      $pay_text = htmlentities(T_('There two ways of paying for Mission-Net 2009:')) . "<br><ul><li>";
      $pay_text.= htmlentities(T_('Wire transfer of money to our bank account')) . "</li><li>" . htmlentities(T_('Credit card payment'));
      $pay_text.= "</li></ul>" . htmlentities(T_('If you prefer to pay by credit or debit card, we have to add a supplement of 8.50 Euro for the transaction.'));
      $this->addElement('static', 'pay_hint', htmlentities(T_('Payment Instructions')), $pay_text);
      $this->addElement('static', 'pay_hint2', htmlentities(T_('Note')), htmlentities(T_('Your registration is only valid as soon as we received your payment.')));
      $this->addElement('static', 'pay_hint2', '', htmlentities(T_('You have to pay within 2 weeks of completing your registration, otherwise the system will delete your registration automatically.')));

      // add the buttons
      $navi[] = $this->createElement('submit', $this->getButtonName('back'), utf8_encode(T_('Back to previous page')));
      $navi[] = $this->createElement('submit', $this->getButtonName('next'), utf8_encode(T_('submit registration')));
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
		other_conf= ?, german_skill = ?, english_skill = ?,
		mother_tongue = ?, part_type = ?, church_name = ?, church_deno = ?, jobwish_1 = ?, jobwish_2 = ?, status = ?';
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

       $daten[] = utf8_decode($page->controller->exportValue('seite2','other_conf'));
       $daten[] = $page->controller->exportValue('seite2','german_skill');
       $daten[] = $page->controller->exportValue('seite2','english_skill');
       $daten[] = $page->controller->exportValue('seite2','mother_tongue');
       $daten[] = $values['parttype'];
       $daten[] = utf8_decode($values['exhib_name']);
       $daten[] = $values['exhib_code'];
       $daten[] = $page->controller->exportValue('seite2','exhib_pay');
       $daten[] = $page->controller->exportValue('seite2','exhib_acco');
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

      $options = array ( 'host'  => '10.27.1.200',
              'auth'      => false,
              'username'  => 'user',
              'password'  => 'geheim'
      );
      $text = T_("Dear") . " " . $values["firstname"] . "\n\n";
      $text.= T_("we are so excited that you have just registered for Mission-net in Oldenburg, 2009. We are looking forward to meeting you and 6000 others from all over Europe.");
      $text.= "\n \n";
      $text.= htmlentities(T_("You are in for a great time with great worship, speakers, and of course the chance to meet like minded people from all over Europe."));
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
      $text.= htmlentities(T_("Thanks again for registering for Mission-Net, and we trust that this event will be an exciting new step in your journey in the Christian faith."));
      $text.= "\n \n \n";
      $text.= htmlentities(T_("Many Blessings"));
      $text.= "\n \n";
      $text.= htmlentities(T_("Mission-Net Congress Management Team"));
      $text.= "\n \n";
      $text.= htmlentities(T_("Mission-Net 2009 -- Alte Neckarelzer Str. 2 -- D-74821 Mosbach"));
      $text.= "\n";
      $text.= $infomailaddress;


      $html = htmlentities(T_("Dear")) . " " . $values["firstname"] . "<br><br>\n";
      $html.= htmlentities(T_("we are so excited that you have just registered for Mission-net in Oldenburg, 2009. We are looking forward to meeting you and 6000 others from all over Europe."));
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("You are in for a great time with great worship, speakers, and of course the chance to meet like minded people from all over Europe."));
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("We have a National Coordinator in your country who this message has also been sent to. Nearer the time they will be in contact with you to give you more information on others from your country that will be attending this exciting event, as well as practical details of things like travel and what you need to bring."));
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("In order for your registration to be processed please ensure that the congress fee is sent to the Bank (see details attached if not paid by credit card) and you send us the signed registration form. On receipt of payment, we will send you another confirmation e-mail that the money has received which will also be sent to the National Coordinator in your country."));
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("Please ensure that this happens so that we can process your registration as quickly as possible."));
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("Please find attached all necessary documents again in the PDF format.")) . "<br>";
      $html.= htmlentities(T_("You may download this PDF file right here:")) . " ";
      $html.= "<a href='https://register.mission-net.org/" . $pdffile . "'>" . htmlentities(T_("PDF Document")) . "</a>";
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("Thanks again for registering for Mission-Net, and we trust that this event will be an exciting new step in your journey in the Christian faith"));
      $html.= "\n<br><br><br>\n";
      $html.= htmlentities(T_("Many Blessings"));
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("Mission-Net Congress Management Team"));
      $html.= "\n<br><br>\n";
      $html.= htmlentities(T_("Mission-Net 2009 -- Alte Neckarelzer Str. 2 -- D-74821 Mosbach"));
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
            'Cc' => 'Beth.Mueller@mission-net.org',
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
	   'Cc' => 'Beth.Mueller@mission-net.org',
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
	echo T_("IBAN: CH41 0900 0000 9147 9018 6") . "<br>\n";
	echo T_("SWIFT CODE: POFICHBEXXX") . "<br>\n";
	echo T_("Address of bank:") . "Swiss Post / PostFinance / CH-3030 Bern" . "<br>\n";
	echo htmlentities(T_("and use this reference:")) . "<b> M09-" . $last_id . "</b><br>\n";
	echo "<br><b>" . htmlentities(T_("Credit Card Payment:")) . "</b><br>\n"; 
	echo htmlentities(T_("If you prefer to pay by credit or debit card, we have to add a supplement of 8.50 Euro for the transaction.")) . "<br>\n";
	$gsumme = $preis + 8.5;
	echo htmlentities(T_("Please transfer the sum of")) . " " . $preis . " Euro + " . T_("8.50 Euro"). 
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
