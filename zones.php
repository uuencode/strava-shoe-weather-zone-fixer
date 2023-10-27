<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require 'config.php';

function debug_die($x){
	// @file_put_contents('debug.log',$x);
	die();
}

if(!isset($_GET['auth'])){debug_die(1);}
$athlete_array = explode('z',$_GET['auth']); 
$athlete_id = (int)$athlete_array[0];
if(!isset($athlete_array[1]) || $athlete_array[1] !== sha1($athlete_id.$secret_salt_hashing)){debug_die(2);}

$db = new SQLite3($store_gpx_files_dir.'/db.sqlite');

if(isset($_POST['zone2b']) && is_numeric($_POST['zone2b']) && isset($_POST['zone3b']) && is_numeric($_POST['zone3b']) && isset($_POST['zone4b']) && is_numeric($_POST['zone4b']) && isset($_POST['zone5b']) && is_numeric($_POST['zone5b']) && isset($_POST['mafbpm']) && is_numeric($_POST['mafbpm'])){

$z2b=(int)$_POST['zone2b']; $z1e=$z2b;
$z3b=(int)$_POST['zone3b']; $z2e=$z3b;
$z4b=(int)$_POST['zone4b']; $z3e=$z4b;
$z5b=(int)$_POST['zone5b']; $z4e=$z5b;
$maf=(int)$_POST['mafbpm']; if($maf<80 || $maf>180){$maf=140;}

if($z2b>=$z3b || $z3b>=$z4b || $z4b>=$z5b){header('location:zones.php?auth='.$_GET['auth']);die();}

$new_arr="[[0,$z1e],[$z2b,$z2e],[$z3b,$z3e],[$z4b,$z4e],[$z5b,300]]";
$db->query('BEGIN');
$db->query("UPDATE zones SET maf=$maf, zones='$new_arr' WHERE athlete=$athlete_id");
$db->query('COMMIT');
}


$res=$db->query('SELECT * FROM zones WHERE athlete = '.$athlete_id);
$ath=$res->fetchArray();

if(!is_array($ath)){
	$db->query('BEGIN');
	$db->query("INSERT INTO zones VALUES ($athlete_id,140,'[[0,119],[120,139],[140,159],[160,179],[180,300]]')");
	$db->query('COMMIT');
	$res=$db->query('SELECT * FROM zones WHERE athlete = '.$athlete_id);
	$ath=$res->fetchArray();
}

$mafrt=(int)$ath['maf'];
$zones=json_decode($ath['zones'],true);



?>
<!DOCTYPE html><html lang="en"><head><title>...</title><meta charset="utf-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<style>
body{font-family:monospace}
input{padding:10px;text-align:center;color:#000;background-color:#eee;border-width:0;width:50px;margin-right:10px}</style>
</head><body>
<div style="margin:auto;width:360px;max-width:100%;background-color:#fff;padding:10px 0">
<h1 style="text-align:right">MAF &amp; HR ZONES</h1>
<hr style="border-width:0;color:transparent;background-color:transparent;border-bottom:1px solid #a00">
<form action="zones.php?auth=<?php print $_GET['auth'];?>" method="post">
<div style="text-align:right;padding:10px 5px"><b>Zone 1</b> = (<?php print '000-'.$zones[0][1];?>)<i style="display:inline-block;width:10px"></i></div>
<div style="text-align:right;padding:10px 5px"><b>Zone 2</b> (<?php print $zones[1][0].'-'.$zones[1][1];?>) begins at <input type="text" name="zone2b" value="<?php print $zones[1][0];?>"></div>
<div style="text-align:right;padding:10px 5px"><b>Zone 3</b> (<?php print $zones[2][0].'-'.$zones[2][1];?>) begins at <input type="text" name="zone3b" value="<?php print $zones[2][0];?>"></div>
<div style="text-align:right;padding:10px 5px"><b>Zone 4</b> (<?php print $zones[3][0].'-'.$zones[3][1];?>) begins at <input type="text" name="zone4b" value="<?php print $zones[3][0];?>"></div>
<div style="text-align:right;padding:10px 5px"><b>Zone 5</b> (<?php print $zones[4][0].'-MAX';?>) begins at <input type="text" name="zone5b" value="<?php print $zones[4][0];?>"></div>
<div style="text-align:right;padding:10px 5px;color:#fff;background-color:#1976D2">Your <b>MAF</b> rate (180-age) is <input type="text" name="mafbpm" value="<?php print $mafrt;?>"></div>
<input type="submit" value="ADJUST" style="width:100%;border-width:0;color:#fff;background-color:#444;margin:10px 0;padding:15px 0">
</form>
<pre style="font-size:120%;text-align:center;background-color:#eee;padding:10px 0">
Zone 1 <?php print '000 🟦🟦🟦🟦🟦🟦🟦🟦🟦🟦 '.$zones[0][1];?>

Zone 2 <?php print $zones[1][0].' 🟩🟩🟩🟩🟩🟩🟩🟩🟩🟩 '.$zones[1][1];?>

Zone 3 <?php print $zones[2][0].' 🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨 '.$zones[2][1];?>

Zone 4 <?php print $zones[3][0].' 🟧🟧🟧🟧🟧🟧🟧🟧🟧🟧 '.$zones[3][1];?>

Zone 5 <?php print $zones[4][0].' 🟥🟥🟥🟥🟥🟥🟥🟥🟥🟥 MAX';?>

</pre>
<input type="button" value="BACK TO APP" style="width:100%;border-width:0;color:#fff;background-color:#D81B60;margin:5px 0;padding:15px 0" onclick="self.location.href='./?<?php print $_GET['auth'];?>'">
</div></body></html>