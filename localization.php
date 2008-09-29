<?php
if (isSet($_SESSION["locale"])) {
   $locale = $_SESSION["locale"];
} else {
   $locale = "de_DE";
}

if (isSet($_GET["locale"])) {
   $_SESSION["locale"] = $_GET["locale"];
} else {
  if (isSet($_GET["resi"])) {
	$_SESSION["locale"] = $_GET["resi"];
  } else {
  	$_SESSION["locale"] = $locale;
  }
}

if (isSet($_SESSION["locale"])) {
   $locale = $_SESSION["locale"];
}

if (isSet($_SESSION["part_type"])) {
   $part_type = $_SESSION["part_type"];
} else {
   $part_type = "1";
}

if (isSet($_GET["part_type"])) {
   $_SESSION["part_type"] = $_GET["part_type"];
} else {
   $_SESSION["part_type"] = $part_type;
}

if (isSet($_SESSION["resi"])) {
   $resi = $_SESSION["resi"];
} else {
   $resi = "de";
}

if (isSet($_GET["resi"])) {
   $_SESSION["resi"] = $_GET["resi"];
} else {
  $_SESSION["resi"] = $resi;
}

setlocale(LC_MONETARY, $locale);
setlocale(LC_ALL, $locale);
T_setlocale(LC_ALL, $locale);
T_setlocale(LC_MESSAGES, $locale);
putenv("LANGUAGE=$locale");
T_bindtextdomain("messages", "./locale");
T_textdomain("messages");
?>
