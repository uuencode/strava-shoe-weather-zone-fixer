<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require 'config.php';

function debug_die($x){
	// @file_put_contents('debug.log',$x);
	die();
}

function abc123($n,$l){
	$n=preg_replace('/[^\p{L}\p{N} -\.\']/u',' ',$n);
	$n=preg_replace('/([\s])\1+/',' ',$n);
	$n=substr($n,0,$l);
	return trim($n);
}

function curlfgc($u){
$c=curl_init();
curl_setopt($c,CURLOPT_FOLLOWLOCATION,1);
curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
curl_setopt($c,CURLOPT_URL,$u);
$res=curl_exec($c); curl_close($c);
if($res){return $res;}else{return false;}}

function ms2mk($n){
	$el=0; $mk=50/(3*$n);
	$mk=(string)$mk; $mk=explode('.',$mk);

	if(isset($mk[1])){
		$el=floatval('0'.'.'.$mk[1]);
		$el=round($el*60/100,2);}

	$mk=(int)$mk[0]; $mk=$mk+$el;
	return $mk;}

if(!isset($_GET['auth']) || !isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['shoes']) || !isset($_GET['name']) || !isset($_GET['desc'])){debug_die(1);}
$athlete_array = explode('z',$_GET['auth']); 
$athlete_id = (int)$athlete_array[0];
if(!isset($athlete_array[1]) || $athlete_array[1] !== sha1($athlete_id.$secret_salt_hashing)){debug_die(2);}

// get access_token

$db = new SQLite3($store_gpx_files_dir.'/db.sqlite');
$res=$db->query('SELECT * FROM tokens WHERE athlete = '.$athlete_id);
$ath=$res->fetchArray();

if(!isset($ath['expires'])){debug_die(3);}

$exp=(int)$ath['expires'];
if($exp>time()){
	$access_token = $ath['access'];
}

// refresh access_token if expired

if(!isset($access_token)){

	$ch=curl_init();
	curl_setopt_array($ch,[
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => "https://www.strava.com/api/v3/oauth/token",
	CURLOPT_POST => 1,
	CURLOPT_POSTFIELDS => http_build_query([
	    "client_id" => $strava_client_id,
	    "client_secret" => $strava_client_secret,
		"refresh_token" => $ath['refresh'],
		"grant_type" => "refresh_token"])
	]);

	$res=curl_exec($ch);
	curl_close($ch);

	$res=@json_decode($res,true);

	if(!isset($res['access_token']) || !isset($res['refresh_token'])){debug_die(4);}

		$expires_at=(int)$res['expires_at'];
		$access_token=preg_replace("/[^a-z0-9]+/i", '', $res['access_token']);
		$refresh_token=preg_replace("/[^a-z0-9]+/i", '', $res['refresh_token']);

		$db->query('BEGIN');
		$db->query('DELETE FROM tokens WHERE athlete = '.$athlete_id);
		$db->query("INSERT INTO tokens VALUES($athlete_id,'$access_token','$refresh_token',$expires_at)");
		$db->query('COMMIT'); 
		$db->query('VACUUM');

}

if(!isset($access_token)){debug_die(5);}

// DATA PROCESSING ----------------


$res=$db->query('SELECT * FROM zones WHERE athlete = '.$athlete_id);
$ath=$res->fetchArray();

$new_maf=(int)$ath['maf'];
$newzone=json_decode($ath['zones'],true);

$zones=[]; $mafhr=[$new_maf-10,$new_maf];

$zones[1]=$newzone[0];
$zones[2]=$newzone[1];
$zones[3]=$newzone[2];
$zones[4]=$newzone[3];
$zones[5]=$newzone[4];

$json=file_get_contents($store_gpx_files_dir.'/'.$_GET['id'].'.json');
$json=json_decode($json,true);

// HR data processing

$hrate=[]; $hratecnt=0; $hratesum=0; $hrate2prnt=''; $actzones=[0,0,0,0,0,0]; $mafzones=[0,0,0]; $zones2rows=''; $zones_visual=''; $maf_visual=''; $zone_suffix='';

foreach ($json['hr_data'] as $val) {

	$hrate[]=(int)$val; $hratecnt+=1; $hratesum+=$val;
	$g=(int)$val;
	if($g>$zones[1][0] && $g<=$zones[1][1]){$actzones[1]+=1;}
	if($g>$zones[2][0] && $g<=$zones[2][1]){$actzones[2]+=1;}
	if($g>$zones[3][0] && $g<=$zones[3][1]){$actzones[3]+=1;}
	if($g>$zones[4][0] && $g<=$zones[4][1]){$actzones[4]+=1;}
	if($g>$zones[5][0] && $g<=$zones[5][1]){$actzones[5]+=1;}
	if($g>=$mafhr[0] && $g<=$mafhr[1]){$mafzones[1]+=1;}
	else{
		if($g<$mafhr[0]){$mafzones[0]+=1;}
		if($g>$mafhr[1]){$mafzones[2]+=1;}
	}

}

if($hratesum>0 && $hratecnt>0){
$hrate2prnt=round($hratesum/$hratecnt);

$zonetotal=$actzones[1]+$actzones[2]+$actzones[3]+$actzones[4]+$actzones[5];
$maftotal=$mafzones[0]+$mafzones[1]+$mafzones[2];
$zone_prts=[]; $maf_prts=[]; $zone_rows=[]; $mplyer=7;
$zone_prts[1] = round(round($actzones[1]*100/$zonetotal)/$mplyer); 
$zone_rows[1] = round(round($actzones[1]*100/$zonetotal)/10); $zr1=round($actzones[1]*100/$zonetotal,1);
$zone_prts[2] = round(round($actzones[2]*100/$zonetotal)/$mplyer);
$zone_rows[2] = round(round($actzones[2]*100/$zonetotal)/10); $zr2=round($actzones[2]*100/$zonetotal,1);
$zone_prts[3] = round(round($actzones[3]*100/$zonetotal)/$mplyer);
$zone_rows[3] = round(round($actzones[3]*100/$zonetotal)/10); $zr3=round($actzones[3]*100/$zonetotal,1);
$zone_prts[4] = round(round($actzones[4]*100/$zonetotal)/$mplyer);
$zone_rows[4] = round(round($actzones[4]*100/$zonetotal)/10); $zr4=round($actzones[4]*100/$zonetotal,1);
$zone_prts[5] = round(round($actzones[5]*100/$zonetotal)/$mplyer);
$zone_rows[5] = round(round($actzones[5]*100/$zonetotal)/10); $zr5=round($actzones[5]*100/$zonetotal,1);

$maf_prts[0] = round(round($mafzones[0]*100/$maftotal)/$mplyer); 
$maf_prts[1] = round(round($mafzones[1]*100/$maftotal)/$mplyer); 
$maf_prts[2] = round(round($mafzones[2]*100/$maftotal)/$mplyer); 

if($hrate2prnt>$zones[1][0] && $hrate2prnt<=$zones[1][1]){$zone_suffix='Z1';}
if($hrate2prnt>$zones[2][0] && $hrate2prnt<=$zones[2][1]){$zone_suffix='Z2';}
if($hrate2prnt>$zones[3][0] && $hrate2prnt<=$zones[3][1]){$zone_suffix='Z3';}
if($hrate2prnt>$zones[4][0] && $hrate2prnt<=$zones[4][1]){$zone_suffix='Z4';}
if($hrate2prnt>$zones[5][0] && $hrate2prnt<=$zones[5][1]){$zone_suffix='Z5';}

for($i=0;$i<$zone_prts[1];$i++){$zones_visual.='💙';}
for($i=0;$i<$zone_prts[2];$i++){$zones_visual.='💚';}
for($i=0;$i<$zone_prts[3];$i++){$zones_visual.='💛';}
for($i=0;$i<$zone_prts[4];$i++){$zones_visual.='❤️';}
for($i=0;$i<$zone_prts[5];$i++){$zones_visual.='❤️';}

for($i=0;$i<$maf_prts[0];$i++){$maf_visual.='🤍';}
for($i=0;$i<$maf_prts[1];$i++){$maf_visual.='💚';}
for($i=0;$i<$maf_prts[2];$i++){$maf_visual.='🤍';}


$zones_r1='🇿1️⃣ ';$zones_r2='🇿2️⃣ ';$zones_r3='🇿3️⃣ ';$zones_r4='🇿4️⃣ ';$zones_r5='🇿5️⃣ ';
$zones_n1='';     $zones_n2='';    $zones_n3='';     $zones_n4='';    $zones_n5='';

for($i=0;$i<$zone_rows[1];$i++){$zones_r1.='🟦';}
for($i=0;$i<10-$zone_rows[1];$i++){$zones_n1.='⬜';}
for($i=0;$i<$zone_rows[2];$i++){$zones_r2.='🟩';}
for($i=0;$i<10-$zone_rows[2];$i++){$zones_n2.='⬜';}
for($i=0;$i<$zone_rows[3];$i++){$zones_r3.='🟨';}
for($i=0;$i<10-$zone_rows[3];$i++){$zones_n3.='⬜';}
for($i=0;$i<$zone_rows[4];$i++){$zones_r4.='🟧';}
for($i=0;$i<10-$zone_rows[4];$i++){$zones_n4.='⬜';}
for($i=0;$i<$zone_rows[5];$i++){$zones_r5.='🟥';}
for($i=0;$i<10-$zone_rows[5];$i++){$zones_n5.='⬜';}

$zones2rows="\n\n".$zones_r1.$zones_n1.' '.$zr1."%\n".$zones_r2.$zones_n2.' '.$zr2."%\n".$zones_r3.$zones_n3.' '.$zr3."%\n".$zones_r4.$zones_n4.' '.$zr4."%\n".$zones_r5.$zones_n5.' '.$zr5."%";
}

// weather data

$weather='';

$d1=substr($json['start_date'],0,10);
$d2=gmdate("Y-m-d",time());

if($d1==$d2 && isset($json['start_latlng'][0]) && isset($json['start_latlng'][1])){
$lat=$json['start_latlng'][0]; $lon=$json['start_latlng'][1];
$res=curlfgc('https://api.openweathermap.org/data/2.5/weather?units=metric&lat='.urlencode($lat).'&lon='.urlencode($lon).'&appid='.$openweathermap_api_key);
$res=@json_decode($res,true);

if(isset($res['weather']['0']['description']) && isset($res['weather']['0']['icon']) && isset($res['main']['temp']) && isset($res['main']['feels_like']) && isset($res['sys']['country'])){

	$t_temp=round($res['main']['temp'],1).'°C';
	$t_like=round($res['main']['feels_like'],1).'°C';

	switch($res['weather']['0']['icon']){
		case '01d': $ico='☀️';break;
		case '01n': $ico='☀️';break;
		case '02d': $ico='⛅';break;
		case '02n': $ico='⛅';break;
		case '03d': $ico='☁️';break;
		case '03n': $ico='☁️';break;
		case '04d': $ico='☁️';break;
		case '04n': $ico='☁️';break;
		case '09d': $ico='🌧️';break;
		case '09n': $ico='🌧️';break;
		case '10d': $ico='🌦️';break;
		case '10n': $ico='🌦️';break;
		case '11d': $ico='⛈️';break;
		case '11n': $ico='⛈️';break;
		case '13d': $ico='❄️';break;
		case '13n': $ico='❄️';break;
		case '50d': $ico='🌫️';break;
		case '50n': $ico='🌫️';break;
		default   : $ico='';break;
		// $all_open_weathermap_icons='☀️ ⛅ ☁️ 🌧️ 🌦️ ⛈️ ❄️ 🌫️';
	}

	$weather = $ico.' '.$t_temp.' feels '.$t_like.'. '.ucfirst($res['weather']['0']['description']).'.';

}}

$desc=''; if(strlen(trim($_GET['desc']))>0){$desc=abc123($_GET['desc'],500)."\n";}
$desc.=$weather."\n"; $pace=ms2mk($json['average_speed']);

$desc.='🛣️ '.$json['distance'].'km ⌚ '.$json['elapsed_time'].' 🏃 '.$pace.'m/km ❤️ '.$hrate2prnt.'bpm'."\n";
$desc.=$maf_visual.$zones2rows."\n".$strava_descr_suffix;

$name='🏃 '.abc123($_GET['name'],50);
$shoes=abc123($_GET['shoes'],50);
if(strlen($shoes)<5){$shoes='none';}

$ch=curl_init();
curl_setopt($ch, CURLOPT_URL,'https://www.strava.com/api/v3/activities/'.$json['id']);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $access_token]);
curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, ["description" => $desc,"name" => $name,"gear_id" => $shoes]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
$res=curl_exec($ch);
curl_close($ch);

?>
<!DOCTYPE html><html lang="en"><head><title>...</title><meta charset="utf-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<style>body{font-family:monospace}</style>
</head><body>
<div style="margin:auto;width:360px;max-width:100%;background-color:#fff;padding:10px 0">
<input type="button" value="DONE! GO BACK!" style="width:100%;border-width:0;color:#000;background-color:#eee;margin:5px 0;padding:10px 0" onclick="self.location.href='./?<?php print $_GET['auth'];?>'"><input type="button" value="STRAVA" style="width:100%;border-width:0;color:#fff;background-color:#D81B60;margin:5px 0;padding:10px 0" onclick="self.location.href='https://www.strava.com/dashboard'">
</div></body></html>