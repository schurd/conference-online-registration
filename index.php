<?php 
if ($_SERVER['HTTPS'] == '') {
   header("Location: https://register.mission-net.org/");
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
 
// now a rule to force user to enter a country if they choose "other"
function countrytest($value)
{
  if ($value[0] == "99") {
    if (strlen($value[1]) < 3 ) {
	return false;
    } else { return true; }
  } else {
    return true;
  } 
}

  $country = substr(strtoupper($_SESSION["resi"]), -2, 2);
  $c_arr = array();
  $fee_arr = array();
  $nation_arr = array();
  $nation_arr[0] = htmlentities(T_('undefined'));

  // List der Länder laden
  $work_sql = 'SELECT id, name, iso_code, fee, service_team_fee FROM countries ORDER by name';
  $erg =& $mdb2->query($work_sql);
  if (PEAR::isError($erg)) {
      die ($erg->getMessage());
  }
  while (($row = $erg->fetchRow())) {
     $c_arr[$row[0]] = $row[1];
     $nation_arr[$row[0]] = $row[1];
     $fee_arr[$row[2]] = array($row[3], $row[4], $row[0]);
  }
  $erg->free();
  if ($_SESSION["part_type"] == "2") {
     $cost_hint = htmlentities(T_('Cost for accomodation, food and program (without travel): ')) . '<b>' . $fee_arr[$country][1] . ' Euro</b>';
  } else {
     $cost_hint = htmlentities(T_('Cost for accomodation, food and program (without travel): ')) . '<b>' . $fee_arr[$country][0] . ' Euro</b>';
  }
  $parthint_arr = array(
    '0'=>htmlentities(T_('Please choose your role at Mission-Net')),
    '1'=>htmlentities(T_('As a Participant you can simply enjoy the congress and participate in whatever activities you would wish in the Programme')),
    '2'=>htmlentities(T_('As a member of the service team you have the opportunity to serve God and the participants at the Congress. You can work in a number of different areas, e.g. Kitche, mission-nwet shop, InfoDesk, etc.')),
    '3'=>htmlentities(T_('As a group leader you will lead a group of approx. 10 people from your country. The Family Groups will meet each morning after the main session.'))
  );


// class for the first page 
class Form_Personal extends HTML_QuickForm_Page 
{ 
   // buildForm defines the form 
   public function buildForm() 
   { 
      $this->_formBuilt = true; 
      // create Form 

      global $c_arr;
      global $nation_arr;
      global $fee_arr;
      global $country;
      global $cost_hint;
      $part_arr = array('0'=>htmlentities(T_('undefined')),'1'=>htmlentities(T_('Participant')), 
              '2'=>htmlentities(T_('Workforce in the Service Team')),
              '3'=>htmlentities(T_('Family Group Leader')), '5'=>htmlentities(T_('Staff')));
      global $parthint_arr; 

      $this->addElement('header', null, htmlentities(T_('Registration Mission-net 2009 - page 1 of 4')));
      $cost_hint2 = "<SPAN ID='preis'>" . $cost_hint . "</SPAN>";
      $this->addElement('select', 'parttype', htmlentities(T_('I will join the conference as:')),
        $part_arr, "title='" . htmlentities(T_('You must be at least 18 years of age to serve in the Service Team')) .
        "' onChange='Gang(document.forms[0].elements[2].value, this.value);'" );
      $this->setDefaults(array('parttype' => $_SESSION["part_type"]));
      $this->addElement('static', 'parttype_hint', htmlentities(T_('Participant Type Definition')), "<SPAN ID='parthint'>" . $parthint_arr[$_SESSION["part_type"]]. "</SPAN>");

      $landgroup[]=HTML_QuickForm::createElement('select', 'country', 'Country of residence:', $c_arr,
	"title='" . htmlentities(T_('Please choose your country of residence')) . 
	"' onChange='Gang(this.value, document.seite1.parttype.value);'" );
      $landgroup[0]->setSelected($fee_arr[$country][2]);
      $landgroup[]=HTML_QuickForm::createElement('text','othercountry','Country:',array('size' => 30, 'maxlength' => 40),
	"title='" . htmlentities(T_('Please enter here your country if you cannot find it in the list')));
      $this->addGroup($landgroup,'countrygroup', htmlentities(T_('Country:')));

      $this->addElement('static', 'price_hint', htmlentities(T_('Price for congress')), $cost_hint2);
      $this->addElement('header', null, htmlentities(T_('Personal data')));
      $this->addElement('text', 'firstname', htmlentities(T_('Firstname:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'lastname', htmlentities(T_("Lastname:")), array('size' => 40, 'maxlength' => 55));
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

      $options = array('language' => 'en', 'format' => 'dMY', 'minYear' => 1920, 'maxYear'=>1995, 'addEmptyOption'=>true);
      $this->addElement('date', 'dateofbirth', htmlentities(T_('Date of birth:')), $options);
      $this->addElement('text', 'passportno', htmlentities(T_('Passport No.:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('date', 'dateofissue', htmlentities(T_('Passport date of issue:')), array('language' => 'en', 'format' => 'dMY', 'minYear' => 1990, 'maxYear'=>2009));
      $this->addElement('date', 'dateofexpire', htmlentities(T_('Passport date of expire:')), array('language' => 'en', 'format' => 'dMY', 'minYear' => 2007, 'maxYear'=>2029));

      $nationgroup[]=HTML_QuickForm::createElement('select', 'nationality', 'Nationality:', $nation_arr,
         "title='" . htmlentities(T_('Please choose your nationality')) .
	 "' onChange='NationGang(this.value);'" );
      $nationgroup[0]->setSelected(0);
      $nationgroup[]=HTML_QuickForm::createElement('text','othernation','Nationality:',array('size' => 30, 'maxlength' => 40),
         "title='" . htmlentities(T_('Please enter here your nationality if you cannot find it in the list')));
      $this->addGroup($nationgroup,'nationgroup', htmlentities(T_('Nationality:')));

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
      $this->addRule('parttype', htmlentities(T_('Please choose your role')), 'required');
      $this->addRule('parttype', htmlentities(T_('Please choose your role')), 'nonzero');
      $this->addRule('firstname', htmlentities(T_('Please enter your firstname')), 'required',null);
      $this->addRule('firstname', htmlentities(T_('Please enter letters only')), 'nopunctuation', null);
      $this->addRule('lastname', htmlentities(T_('Please enter your lastname')), 'required',null);
      $this->addRule('lastname', htmlentities(T_('Please enter letters only')), 'nopunctuation',null);
      $this->addRule('street', htmlentities(T_('Please enter street')), 'required',null);
      $this->addRule('email', htmlentities(T_('Please enter your e-mail')), 'required',null);
      $this->addRule('email', htmlentities(T_('Please enter a valid e-mail address')), 'email',null);
      $this->addGroupRule('dateofbirth', htmlentities(T_('Please enter your birth date')), 'required');
      $this->addGroupRule('plzort', htmlentities(T_('Please enter postcode and town')), 'required', 'server', 2);
      $this->addGroupRule('plzort', array('postcode' => array(        // Rules for the postcode
        array(htmlentities(T_('Please enter a postcode')),'required')
        ),
        'city' => array( //Rules for the town
        array(htmlentities(T_('Please enter town')),'required'),
        array(htmlentities(T_('The name of the town is too short')),'minlength',2),
        )
     ));
      $this->addRule('phone', htmlentities(T_('Please enter your phone number')), 'required',null);
      $this->addRule('gender', htmlentities(T_('Please choose your gender')), 'required',null);
      $this->addRule('gender', htmlentities(T_('Please choose your gender')), 'lettersonly');

      //$this->registerRule('rule_country', 'callback', 'countrytest');
      //$this->addGroupRule('countrygroup', htmlentities(T_('Please enter the country of residence')),'rule_country');

      //$this->addRule('country', htmlentities(T_('Please choose your country of residence')), 'required');
      //$this->addRule('country', htmlentities(T_('Please choose your country of residence')), 'nonzero');
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

      $this->addElement('header', null, htmlentities(T_('Registration Mission-net 2009 - page 2 of 4')));

      $this->addElement('header', null, htmlentities(T_('Other personal data')));
      $this->addElement('textarea','other_conf', htmlentities(T_('Other mission conferences attended? Which ones?')),array('wrap'=>'soft','rows'=>'3','cols'=>'40'));
      $this->addElement('textarea','leader_exp', htmlentities(T_('If you have already leadership experiences (e.g. house group etc.), please list them here:')),array('wrap'=>'soft','rows'=>'3','cols'=>'40'));

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

      $this->addElement('header', null, htmlentities(T_('Group details (if you come with a group)')));
      $this->addElement('text', 'gl_name', htmlentities(T_('Name of groupleader:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'gl_email', htmlentities(T_('E-mail of your groupleader:')), array('size' => 20, 'maxlength' => 40));

      //Buttons hinzufuegen 
      $navi[] = $this->createElement('submit', $this->getButtonName('back'), utf8_encode(T_('Back to previous page'))); 
      $navi[] = $this->createElement('submit', $this->getButtonName('next'), utf8_encode(T_('Proceed to next page'))); 
      $this->addGroup($navi, null, '', '&nbsp;'); 

      $this->addRule('email', htmlentities(T_('Please enter a valid e-mail address')), 'gl_email',null);
      $this->applyFilter('__ALL__','trim');
      $this->setDefaultAction('next'); 
   } 
} 
 

// class for the third page
class Form_three extends HTML_QuickForm_Page
{
   function buildForm()
   {
      $this->_formBuilt = true;
      $mdb2 =& MDB2::singleton();

      $this->addElement('header', null, htmlentities(T_('Registration Mission-net 2009 - page 3 of 4')));
      $this->addElement('html', '<td style="background-color: #CCCCCC; color:red;" colspan="2"><b>' .
        htmlentities(T_('Additional questions for those interested in joining an IN2 team')) . '</b></td>');

      //Buttons hinzufuegen
      $navi1[] = $this->createElement('submit', $this->getButtonName('back'), utf8_encode(T_('Back to previous page')));
      $navi1[] = $this->createElement('submit', $this->getButtonName('next'), utf8_encode(T_('Proceed to next page')));
      $this->addGroup($navi1, null, '', '&nbsp;');

      $this->addElement('html', '<td style="background-color: #CCCCCC; color:red;" colspan="2"><b>' . 
	htmlentities(T_('If you are not planning to join a IN2 team, please proceed to next page.')) . '</b></td>');
      $this->addElement('link', 'IN2_link', htmlentities(T_('Click on this link to learn more about IN2 teams:')), 
	'http://www.mission-net.org/in2-teams.html', htmlentities(T_('Link to IN2 information')), 'target="_blank"');

      $this->addElement('static', 'in2_hint', htmlentities(T_('IN2 Hint')), htmlentities(T_('If you are planning to join an IN2 Team, please answer these questions as well. We send your request to the organisations organizing this team and they will be in further contact with you. If you wish to go on an IN2 Team after your registration or you need to make changes of your online registration, please send an email to registration@mission-net.org.')));
      $work_sql = 'SELECT id, name FROM IN2_teams ORDER by name';
      unset($s_arr);
      $s_arr[0] = htmlentities(T_('none'));
      $erg =& $mdb2->query($work_sql);
        if (PEAR::isError($erg)) {
           die ($erg->getMessage());
        }
        while (($row = $erg->fetchRow())) {
                $s_arr[$row[0]] = $row[1];
        }
      $erg->Free();
      $this->addElement('select', 'IN2_wish_1', htmlentities(T_('Please select your 1st choice:')), $s_arr);
      $this->addElement('select', 'IN2_wish_2', htmlentities(T_('Please select your 2nd choice:')), $s_arr);
      $this->addElement('textarea','why_IN2', htmlentities(T_('Why do you wish to serve on an IN2 team on a mission trip?')),
        array('wrap'=>'soft','rows'=>'3','cols'=>'50'));
      $this->addElement('textarea','learn_IN2', htmlentities(T_('What do you want to learn / contribute?')),
        array('wrap'=>'soft','rows'=>'3','cols'=>'50'));

      $this->addElement('header','church_hint',htmlentities(T_('Church contact / reference')),htmlentities(T_('Name of the church you attend regularly')));
      $this->addElement('text', 'church_name', htmlentities(T_('Name of the church:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'denomination', htmlentities(T_('Denomination:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'church_address', htmlentities(T_('Adress of the church:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('header','reference_hint',htmlentities(T_('Reference')),
        htmlentities(T_('We need a person we can ask for a reference about you')));
      $this->addElement('select', 'ref_function', htmlentities(T_('Function of the reference in this church:')),
        array('0' => htmlentities(T_('Pastor')), '1'=>htmlentities(T_('Youthworker')),'2'=>htmlentities(T_('Elder')),'3'=>htmlentities(T_('Other'))));
      $this->addElement('text', 'ref_lastname', htmlentities(T_('Lastname:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'ref_firstname', htmlentities(T_('Firstname:')), array('size' => 40, 'maxlength' => 55));
      $this->addElement('text', 'ref_phone', htmlentities(T_('Phone incl. country code:')), array('size' => 30, 'maxlength' => 30));
      $this->addElement('text', 'ref_email', htmlentities(T_('E-mail:')), array('size' => 40, 'maxlength' => 40));

      $this->addElement('header', null, htmlentities(T_('Previous outreaches / camps')));
      $this->addElement('textarea','outreach_org', htmlentities(T_('List and state which organisation facilitated the outreach:')),
        array('wrap'=>'soft','rows'=>'3','cols'=>'50'));
      $this->addElement('textarea','music_abil', htmlentities(T_('Musical abilities:')),array('wrap'=>'soft','rows'=>'3','cols'=>'50'));
      $this->addElement('textarea','outreach_abil', htmlentities(T_('Outreach abilities (outdoor, door to door, mime, drama, youthwork, etc.):')),
        array('wrap'=>'soft','rows'=>'3','cols'=>'50'));
      $this->addElement('header', null, htmlentities(T_('Medical Information')));
      $this->addElement('select', 'vegetarian', htmlentities(T_('Are you a Vegetarian ?')),
        array('0'=>htmlentities(T_('No')),'1'=>htmlentities(T_('Yes'))));
      $this->addElement('select', 'medication', htmlentities(T_('Are you taking on any medication?')),
        array('0'=>htmlentities(T_('No')),'1'=>htmlentities(T_('Yes'))));
      $this->addElement('textarea','what_medication', htmlentities(T_('If yes, which')),array('wrap'=>'soft','rows'=>'3','cols'=>'40'));
      $this->addElement('textarea','disabilities', htmlentities(T_('Do you have any disabilities that need special considerations:')),
        array('wrap'=>'soft','rows'=>'3','cols'=>'40'));
      $this->addElement('textarea','allergies', htmlentities(T_('Allergies:')),array('wrap'=>'soft','rows'=>'3','cols'=>'40'));


      //Buttons hinzufuegen
      $navi[] = $this->createElement('submit', $this->getButtonName('back'), utf8_encode(T_('Back to previous page')));
      $navi[] = $this->createElement('submit', $this->getButtonName('next'), utf8_encode(T_('Proceed to next page')));
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
	$rq_note = '<span style="color:F00">*</span> ' . htmlentities(T_('Denotes a required field'));
    $page->setRequiredNote($rq_note); 
      // Renderer-Objekt an ActionDisplay uebergeben 
      $page->accept($renderer);
      echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">
  	<html>
  	<head>
    	  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";
      echo "<title>" . htmlentities(T_("Mission-net Online Registration 2009")) . "</title>";
      global $fee_arr;
      global $cost_hint;
      global $parthint_arr;
      reset($fee_arr);
      echo "  <style type=\"text/css\">
                @import url(\"formate.css\");
          </style>
	<script type=\"text/javascript\">
	var cost_hint = \"" . $cost_hint . "\";\n
	var preise = new Array();\n";

	while (list($key, $val) = each($fee_arr)) {
	  echo "preise[$val[2]] = new Object();\n";
	  echo "preise[$val[2]][1] = $val[0];\n";
	  echo "preise[$val[2]][2] = $val[1];\n";
	  echo "preise[$val[2]][3] = $val[0];\n";
	}
	echo "parthintarr = new Object();\n";
	while (list($key, $val) = each($parthint_arr)){
	  echo "parthintarr[$key] = \"" . $val . "\";\n";
	}

	echo "  function Gang(landwert, typwert) {
		if (typwert == 5) {
		   var ziel = \"https://register.mission-net.org/staff.php\";
		   window.location.href = ziel;
		}
	  	land = landwert;
		typ = typwert;
		var epreis = preise[land][typ] + \" Euro\";
		var cost_hint2 = cost_hint.replace(/\\d+ Euro/g, epreis);
		if (document.all) {
		   document.all(\"preis\").innerHTML = cost_hint2;
		   document.all(\"parthint\").innerHTML = parthintarr[typ];
		} else {
		  document.getElementById(\"preis\").innerHTML = cost_hint2;
		  document.getElementById(\"parthint\").innerHTML = parthintarr[typ];
		}
		if (landwert == 99) {
		  alert(\"Please enter your country of residence here!\");
		  document.forms[0].elements[3].disabled = false;
		  document.forms[0].elements[3].focus();
		} else {
		  document.forms[0].elements[3].disabled = true;
		  document.forms[0].elements[3].value = \"\";
		}
          }
	  function Ausgrauen(){
	    document.seite1.elements[3].disabled = true;
	    document.seite1.elements[28].disabled = true;
	  }
	  function NationGang(nationwert) {
	    if (nationwert == 99) {
	      alert(\"Please enter your nationality here!\");
	      document.seite1.elements[28].disabled = false;
	      document.seite1.elements[28].focus();
	    } else {
	      document.seite1.elements[28].disabled = true;
	      document.seite1.elements[28].value = \"\";
	    }
	  }
	  function WechsleSprach(sprach) {
	    Erg = document.URL.match(/locale=\\w+/i);
	    if (Erg) {
	      var NeuURL = document.URL.replace(/locale=\\w+/gi, \"locale=\" + sprach);
	    } else {
	      erg2 = document.URL.match(/php$/i);
	      if (erg2) {
	        var NeuURL = document.URL + \"?locale=\" + sprach;
	      } else {
	        var NeuURL = document.URL + \"&locale=\" + sprach;
	      }
	    }
	    window.location.href = NeuURL;
	  }
	</script>
  	</head>
  	<body onload=\"Ausgrauen();\"><div class=\"main\"><div class=\"site\">
  	<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
	<tbody><tr>
  	 <td class=\"white-cell\">
   	 <img src=\"images/MN_Logo_kleiner.png\" alt=\"Mission-net Logo\" width=\"160\" height=\"90\">
   	 </td><td class=\"title-cell\">Mission-Net<br>8. - 13. April 2009<br>Oldenburg<br>Germany
  	 </td>
	 <td class=\"white-cell\" align=\"right\" valign=\"top\" padding=4>
	   <img src=\"images/de.png\" hspace=4 vspace=4 alt=\"Deutsch\" title=Deutsch onClick=\"WechsleSprach('de');\"></td>
	 <td class=\"white-cell\" align=\"right\" valign=\"top\" padding=4>
	   <img src=\"images/it.png\" hspace=4 vspace=4 alt=\"'Italian\" title=Italian onClick=\"WechsleSprach('it');\"></td>
	 <td class=\"white-cell\" align=\"right\" valign=\"top\">
	   <img src=\"images/fr.png\" hspace=4 vspace=4 alt=\"French\" title=French onClick=\"WechsleSprach('fr');\"></td>
	 <td class=\"white-cell\" align=\"right\" valign=\"top\">
	   <img src=\"images/fi.png\" hspace=4 vspace=4 alt=\"Finnish\" title=Finnish onClick=\"WechsleSprach('fi');\"></td>
	 <td class=\"white-cell\" align=\"right\" valign=\"top\">
	   <img src=\"images/gb.png\" hspace=4 vspace=4 alt=\"English\" title=English onClick=\"WechsleSprach('en');\"></td>
	 <td class=\"white-cell\" align=\"right\" valign=\"top\">
	   <img src=\"images/no.png\" hspace=4 vspace=4 alt=\"Norwegian\" title=Norwegian onClick=\"WechsleSprach('no');\"></td>
	 <td class=\"white-cell\" align=\"right\" valign=\"top\">
	   <img src=\"images/nl.png\" hspace=4 vspace=4 alt=\"Dutch\" title=Dutch onClick=\"WechsleSprach('nl');\"></td>
	 <td class=\"white-cell\" align=\"right\" valign=\"top\">
	   <img src=\"images/pt.png\" hspace=4 vspace=4 alt=\"Portugese\" title=Portugese onClick=\"WechsleSprach('pt');\"></td>
         <td class=\"white-cell\" align=\"right\" valign=\"top\">
	   <img src=\"images/es.png\" hspace=4 vspace=4 alt=\"Spanish\" title=Spanish onClick=\"WechsleSprach('es');\"></td>

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
      $this->addElement('header', null, htmlentities(T_('Registration Mission-net 2009 - page 4 of 4')));

      $this->addElement('html', '<td style="background-color: #CCCCCC; color:red;" colspan="2"><b>' . 
	htmlentities(T_('Additional questions for those joining the Service Team')) . '</b></td>');

      $work_sql = 'SELECT id, work_area FROM work_area_list WHERE id <> 5 ORDER BY id';
      unset($s_arr);
      $erg =& $mdb2->query($work_sql);
        if (PEAR::isError($erg)) {
           die ($erg->getMessage());
        }
        while (($row = $erg->fetchRow())) {
                $s_arr[$row[0]] = $row[1];
        }
      $erg->Free();
      $this->addElement('select', 'st_wish_1', htmlentities(T_('Please select your 1st choice:')), $s_arr);
      $this->addElement('select', 'st_wish_2', htmlentities(T_('Please select your 2nd choice:')), $s_arr);
      $this->addElement('textarea','st_comment', htmlentities(T_('Please add here comments for your work in the Service Team')),
        array('wrap'=>'soft','rows'=>'3','cols'=>'50'));
      unset($s_arr);

      $this->addElement('static','arrival_hint',htmlentities(T_('Arrival & Departure')),
              htmlentities(T_('We really need your help during setup days and tear-down days. If possible, we ask to schedule your arrival at Saturday, 4th of April and your departure at Tuesday, 14th of April! If you have any questions, please contact us at serviceteam@mission-net.org')));

      $arr_array = array(
      	'2009-04-04'=>'04. April',
      	'2009-04-05'=>'05. April',
      	'2009-04-06'=>'06. April',
      	'2009-04-07'=>'07. April',
	'2009-04-08'=>'08. April',
	'2009-04-09'=>'09. April',
	'2009-04-10'=>'10. April',
	'2009-04-11'=>'11. April',
	'2009-04-12'=>'12. April');
      $dep_array = array(
	'2009-04-09' => '09. April',
	'2009-04-10' => '10. April',
	'2009-04-11' => '11. April',
	'2009-04-12' => '12. April',
	'2009-04-13' => '13. April',
	'2009-04-14' => '14. April');


      $this->addElement('select', 'arr_date', htmlentities(T_('Estimated day of arrival (in April 2009):')), $arr_array);
      $this->setDefaults(array('arr_date' => '2009-04-08'));

      $this->addElement('select', 'dep_date', htmlentities(T_('Estimated day of departure (in April 2009):')), $dep_array);
      $this->setDefaults(array('dep_date' => '2009-04-13'));

      $this->addElement('html', '<td style="background-color: #CCCCCC; color:red;" colspan="2"><b>' .
        htmlentities(T_('Additional questions for those joining Special Services')) . '</b></td>');
      $s_arr = array('0' => htmlentities(T_('None')), '1'=>htmlentities(T_('Counsellor')),
        '2'=>htmlentities(T_('MAC - Mission Advice Center')),'3'=>htmlentities(T_('Translator')));
      $this->addElement('select', 'counsellor', htmlentities(T_('Please choose here if you are interested in working as:')), $s_arr);
      $this->addElement('static','couns_hint',htmlentities(T_('Information')),
        htmlentities(T_('We will review your application, but can not guarantee cooperation! We will let you know until 2009.')));

      $this->addElement('textarea','counsellor_reason', htmlentities(T_('Please add here why you are qualified for this job')),
        array('wrap'=>'soft','rows'=>'3','cols'=>'50'));

      $this->addElement('header', null, htmlentities(T_('Hints and Conditions')));
      $this->addElement('textarea','bemerkung', htmlentities(T_('Please add other remarks here')),
        array('wrap'=>'soft','rows'=>'3','cols'=>'50'));
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
      $pay_text.= "</li></ul>" . htmlentities(T_('If you prefer to pay by credit or debit card, we have to add a supplement of 10 Euro for the transaction.'));
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
      global $infomailaddress;
      global $registrationsenderaddress;
      global $registrationhandleraddress;
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
		postcode = ?, city = ?, country = ?, countrytext = ?, phone = ?, mobile = ?, email = ?, 
		dateofbirth = ?, maritalstatus = ?, gender = ?,
		passport_name = ?, passport_no = ?, passport_dateofissue = ?, passport_dateofexpire = ?,
		nationality = ?, nationalitytext = ?, invitation_letter = ?, vegeterian = ?, medication = ?, what_medication = ?, 
		medication_reason = ?, disabilities = ?, allergies = ?, emergency_firstname = ?,
		emergency_lastname = ?, emergency_phone = ?, groupleader_name = ?, groupleader_email = ?,
		church_name = ?, church_deno = ?, church_address = ?, ref_person_church_task = ?, ref_person_lastname = ?, 
                ref_person_firstname = ?, ref_person_phone = ?, ref_person_email = ?,
		other_conf= ?, leadership_exp = ?, german_skill = ?, english_skill = ?,
		mother_tongue = ?, IN2_team_1 = ?, IN2_team_2 = ?, part_type = ?, why_trip = ?, learn_trip= ?, 
		outreach_organisation = ?, musical_ability = ?, outreach_ability = ?, story = ?, 
		why_mission = ?, jobwish_1 = ?, jobwish_2 = ?, jobwish_comment = ?, special_job = ?, 
		sj_reason = ?, status = ?, arrival_date =?, departure_date = ?, remarks =?';
      $sth = $mdb2->prepare($sql1, $typen, MDB2_PREPARE_RESULT);

      $daten = array(utf8_decode($values['firstname']),utf8_decode($values['lastname']),
	utf8_decode($values['preferredname']),utf8_decode($values['title']),
	utf8_decode($values['street']),$values['plzort']['postcode'], utf8_decode($values['plzort']['city']), 
	$values['countrygroup']['country'], $values['countrygroup']['othercountry'], $values['phone'], $values['handy'], $values['email'], 
	korr_datum($values['dateofbirth']), $values['maritalstatus'], 
	$values['gender'], utf8_decode($values['passportname']), utf8_decode($values['passportno']));

       $daten[] = korr_datum($values['dateofissue']);
       $daten[] = korr_datum($values['dateofexpire']);
       $daten[] = $values['nationgroup']['nationality'];
       $daten[] = $values['nationgroup']['othernation'];
       $daten[] = $page->controller->exportValue('seite1','invitationletter');
       $daten[] = $page->controller->exportValue('seite3','vegetarian');
       $daten[] = $page->controller->exportValue('seite3','medication');
       $daten[] = utf8_decode($values['what_medication']);
       $daten[] = utf8_decode($values['medication_reason']);
       $daten[] = utf8_decode($values['disabilities']);
       $daten[] = utf8_decode($values['allergies']);

       $daten[] = utf8_decode($values['emergency_firstname']);
       $daten[] = utf8_decode($values['emergency_lastname']);
       $daten[] = $values['emergency_phone'];
       $daten[] = utf8_decode($page->controller->exportValue('seite2','gl_name'));
       $daten[] = utf8_decode($page->controller->exportValue('seite2','gl_email'));

       $daten[] = utf8_decode($page->controller->exportValue('seite3','church_name'));
       $daten[] = utf8_decode($page->controller->exportValue('seite3','denomination'));
       $daten[] = utf8_decode($page->controller->exportValue('seite3','church_address'));
       $daten[] = $page->controller->exportValue('seite3','ref_function');
       $daten[] = utf8_decode($page->controller->exportValue('seite3','ref_firstname'));
       $daten[] = utf8_decode($page->controller->exportValue('seite3','ref_lastname'));
       $daten[] = $page->controller->exportValue('seite3','ref_phone');
       $daten[] = $page->controller->exportValue('seite3','email');

       $daten[] = utf8_decode($page->controller->exportValue('seite2','other_conf'));
       $daten[] = utf8_decode($page->controller->exportValue('seite2','leader_exp'));
       $daten[] = $page->controller->exportValue('seite2','german_skill');
       $daten[] = $page->controller->exportValue('seite2','english_skill');
       $daten[] = $page->controller->exportValue('seite2','mother_tongue');
       $daten[] = $page->controller->exportValue('seite3','IN2_wish_1');
       $daten[] = $page->controller->exportValue('seite3','IN2_wish_2');
       $daten[] = $values['parttype'];
       $daten[] = utf8_decode($page->controller->exportValue('seite3','why_IN2'));
       $daten[] = utf8_decode($page->controller->exportValue('seite3','learn_IN2'));
       $daten[] = utf8_decode($page->controller->exportValue('seite2','story'));
       $daten[] = utf8_decode($page->controller->exportValue('seite2','why_mission'));
       $daten[] = utf8_decode($page->controller->exportValue('seite3','outreach_org'));
       $daten[] = utf8_decode($page->controller->exportValue('seite3','musical_ability'));
       $daten[] = utf8_decode($page->controller->exportValue('seite3','outreach_ability'));
       $daten[] = $page->controller->exportValue('seite4','st_wish_1');
       $daten[] = $page->controller->exportValue('seite4','st_wish_2');
       $daten[] = utf8_decode($page->controller->exportValue('seite4','st_comment'));
       $daten[] = $page->controller->exportValue('seite4','counsellor');
       $daten[] = utf8_decode($page->controller->exportValue('seite4','counsellor_reason'));
       $daten[] = '1';	// status field
       $daten[] = $page->controller->exportValue('seite4','arr_date');
       $daten[] = $page->controller->exportValue('seite4','dep_date');
       $daten[] = utf8_decode($page->controller->exportValue('seite4','bemerkung'));

      $affRow=$sth->execute($daten);
      $last_id = $mdb2->lastInsertID('participants', 'id');
      if (PEAR::isError($affRow)) {
           die('failed... Fehler:' . $affRow->getMessage());
           echo ($mdb2->getMessage().' - '.$mdb2->getUserinfo());
      }
      $sth->Free();
      if ( $values['parttype'] == '2' ) {
        $sql1 = "SELECT service_team_fee, name FROM countries WHERE id=" . $values['countrygroup']['country'];
      } else {
        $sql1 = "SELECT fee, name FROM countries WHERE id=" . $values['countrygroup']['country'];
      }
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
      $text.= htmlentities(T_("You are in for a great time with great worship, speakers, and of course the chance to meet like minded people from all over Europe."));
      $text.= "\n \n";
      $text.= htmlentities(T_("We have a National Coordinator in your country who this message has also been sent to. Nearer the time they will be in contact with you to give you more information on others from your country that will be attending this exciting event, as well as practical details of things like travel and what you need to bring."));
      $text.= "\n \n";
      $text.= htmlentities(T_("In order for your registration to be processed please ensure that the congress fee is sent to the Bank (see details attached if not paid by credit card) and you send us the signed registration form, if you are under 18 years. On receipt of payment, we will send you another confirmation e-mail that the money has received which will also be sent to the National Coordinator in your country."));
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
      $html.= htmlentities(T_("In order for your registration to be processed please ensure that the congress fee is sent to the Bank (see details attached if not paid by credit card) and you send us the signed registration form, if you are under 18 years. On receipt of payment, we will send you another confirmation e-mail that the money has received which will also be sent to the National Coordinator in your country."));
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
		$values['firstname'] . ', ' . $values["nationality"] . ' ' . $last_id ;
        $headers=array( 'From' => $registrationsenderaddress,
            'To' => $registrationhandleraddress,
            'Cc' => 'Eva-Maria.Walter@mission-net.org',
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

	$sql= "SELECT p.id, firstname, lastname, preferred_name, title, street, postcode, city, c.name as country, countrytext, phone, mobile,";
	$sql.= " email, t.type as part_type, dateofbirth, m.status as maritalstatus, gender, passport_name, passport_no, ";
	$sql.= "passport_dateofissue, passport_dateofexpire, n.name as nationality, nationalitytext, IF(invitation_letter=0,'No', 'Yes') as ";
	$sql.= "invitation_letter_required, IF(vegeterian=0,'No','Yes') as vegetarian, IF(medication=0,'No','Yes') as medication, ";
	$sql.= "what_medication, disabilities, allergies, dietary, emergency_firstname, emergency_lastname, emergency_phone, ";
	$sql.= "church_name, church_deno, church_address, groupleader_name, ref_person_firstname, ref_person_lastname, ";
	$sql.= "ref_person_phone, ref_person_email, ref_person_church_task, groupleader_email, other_conf, team_conf, ";
	$sql.= "leadership_exp, gl.level_desc as german_skill, el.level_desc as english_skill, l.name as mothertongue, ";
	$sql.= "p.IN2_team_1, p.IN2_team_2, why_trip, learn_trip, story, why_mission, outreach_organisation, ";
	$sql.= "musical_ability, p.outreach_ability, w1.work_area as jobwish_1, w2.work_area as jobwish_2, jobwish_comment, ";
	$sql.= "special_job, sj_reason, p.arrival_date, p.departure_date, p.remarks, p.status, p.appdate";
	$sql.= ' FROM participants p, countries c, marital_status m, part_type t, countries n, ';
	$sql.= 'languages l, work_area_list w1, work_area_list w2, english_level el, english_level gl';
	$sql.= ' where p.id = '. $last_id . ' and c.id = p.country and m.id = p.maritalstatus and t.id = p.part_type ';
	$sql.= 'and n.id=p.nationality and l.id=p.mother_tongue and ';
	$sql.= 'w1.id=p.jobwish_1 and w2.id=p.jobwish_2 and el.id=p.english_skill and gl.id=german_skill';
      $row = $mdb2->queryRow($sql);
      if (PEAR::isError($row)) {
         die ($row->getMessage());
      }

      foreach ($row as $k => $wert) {
      	$text.= "\n" . $k . " : " . $wert;
	$html.= "\n<br>" . $k . " : " . $wert;
      }
      $text.= "\nPreis: " . $preis; 
      $html.= "\n<br>Preis: " . $preis; 
       
      $sql = "SELECT p.country as LandID, n.email AS nat_email FROM participants p, national_motivators n";
      $sql.= ' WHERE p.id = '. $last_id . ' and n.countryid = p.country';
      $erg =& $mdb2->query($sql);
      if (PEAR::isError($erg)) {
          die ($erg->getMessage());
      }
      $cc_empf = '';
      while (($row = $erg->fetchRow())) {
          if ( $cc_empf == '' ) {
            $cc_empf.=  $row[1];
	  } else {
            $cc_empf.=  ', ' . $row[1];
	  }
	  $landid = $row[0]; 
      }
      $erg->free();

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
	   'Cc' => $cc_empf,
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
	$format = htmlentities(T_("Hello")) . " %1\$s";
	printf($format, $values["firstname"]);
	echo ", <br><br>\n";
	echo htmlentities(T_('we are so excited that you have just registered for Mission-net in Oldenburg, 2009. We are looking forward to meeting you and 6000 others from all over Europe.'));
	echo "\n<br><br>\n";
	echo htmlentities(T_('You are in for a great time with great worship, speakers, and of course the chance to meet like minded people from all over Europe.'));
	echo "\n<br><br>\n";
	echo htmlentities(T_("We have a National Coordinator in your country who this message has also been sent to. Nearer the time they will be in contact with you to give you more information on others from your country that will be attending this exciting event, as well as practical details of things like travel and what you need to bring."));
	echo "\n<br><br><br>\n";
	echo htmlentities(T_("In order for your registration to be processed please ensure that the congress fee is paid. If you are under 18 years old, you are also required to send in the PDF form sent with the confirmation e-mail, signed by your parents and you. On receipt of payment, we will send you another confirmation e-mail that the money has received which will also be sent to the National Coordinator in your country."));
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
        echo htmlentities(T_("Account no:")) .  " 5010802" . "<br>\n";
        global $iban;
        global $bank;
        global $swiftcode;
        echo $iban . "<br>\n";
        echo $swiftcode . "<br>\n";
        echo T_("Address of bank:") . " " . $bank . "<br>\n";

	if ($landid == 3) {
	  echo htmlentities(T_("Participants from Switzerland might use the following Swiss account to avoid banking fees:")) . "<br>\n";
	  echo htmlentities(T_("Account no:")) .  " 91-479018-6" . "<br>\n";
	  global $swiss_iban;
	  global $swiss_bank;
	  global $swiss_swiftcode;
	  echo $swiss_iban . "<br>\n";
	  echo $swiss_swiftcode . "<br>\n";
	  echo T_("Address of bank:") . $swiss_bank . "<br>\n";
	} 
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
//	echo "<a href='http://www.paypal.com?func=missionnet09'>http://www.paypal.com?func=missionnet09</a><br>";
	echo htmlentities(T_("Note:")) . " " . htmlentities(T_("Your registration is only valid as soon as we received your payment and if you are under 18 years the signed PDF form.")) . "<br>\n";
	echo htmlentities(T_("You have to pay within 2 weeks after completing your online registration (this is now), otherwise the system will delete your registration automatically")) . "<br>\n";
	session_destroy();
         } 
	} 
	 
	// Neue Formular-Objekte mit eindeutigem Namen ableiten 
	$seite1 = new Form_Personal('seite1'); 
	$seite2 = new Form_Motivation('seite2'); 
	$seite3 = new Form_three('seite3'); 
	$seite4 = new Form_Bankdaten('seite4'); 
	 
	// Neues Controller-Objekt ableiten 
	$controller = new HTML_QuickForm_Controller('mnformular', true); 
	 
	// Formularseiten hinzufuegen 
	$controller->addPage($seite1); 
 	$controller->addPage($seite2); 
	$controller->addPage($seite3); 
 	$controller->addPage($seite4); 

// add the actions 
$controller->addAction('display', new ActionDisplay()); 
$controller->addAction('process', new ActionProcess()); 
 
// Controller ausfuehren 
$controller->run(); 

$mdb2->disconnect();
?>
