<?php
require_once 'MDB2.php';
require_once './config.php';
$mdb2 =& MDB2::singleton($dsn);

if (PEAR::isError($mdb2)) {
   die("Error while connecting : " . $mdb2->getMessage());
}

if (isSet($_GET["regi"])) {
   $regi = $_GET["regi"];
}

if (is_numeric($regi)) {
   $sql = "SELECT p.firstname, p.lastname, c.fee FROM participants p, countries c WHERE p.id=$regi and c.id=p.country";
   $erg =& $mdb2->query($sql);
   if (PEAR::isError($erg)) {
       die ($erg->getMessage());
   }
   $row = $erg->fetchRow();
   $erg->free();
   if (is_numeric($row[2])) {
?>
<html><head><title>Mission-Net 2009 -- Paypal forwarding</title>
<script type="text/javascript">
function Springe() {
	document.PayForm.submit(); 
}
window.setTimeout("Springe()", 5000);
</script>
</head>
<body onload="Springe()">
<form name="PayForm" method="post" action="https://www.paypal.com/cgi-bin/webscr">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="congress-payment@mission-net.org">
<input type="hidden" name="item_name" value="Mission-Net event">
<input type="hidden" name="quantity" value="1">
<input type="hidden" name="bn"  value="ButtonFactory.PayPal.002">
<input type="hidden" name="currency_code" value="EUR">
<input type="hidden" name="on0" value="<?php echo $row[1] . ', ' . $row[0]; ?>">
<input type="hidden" name="os0" value="">
<input type="hidden" name="on1" value="Reference No. M09- <?php echo $regi; ?>">
<input type="hidden" name="os1" value="">
<br>
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_paynow_LG.gif">
<br>
You will be forwarded to PayPal within 5 seconds. Please press the button above if this is not the case.
</form>
<?php
} else { echo "<html><body>Invalid registration number!"; }
}
?>
</body>
</html>
