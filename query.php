<?php
require_once 'workflow.php';
require_once 'City.php';
require_once 'Client/BaiduWeather.php';
$query = '';
isset ( $argv [1] ) && $query = trim ( $argv [1] );
$wl = new Workflows ();
$city = City::instance ();
$query = $city->getName ( $query );
try {
	$baidu = BaiduWeather::instance ( $wl, $query );
	$baidu->current ();
	$baidu->days ();
} catch ( Exception $e ) {
	$wl->result ( 'response_error', null, '找不到对应城市的天气信息', '请求失败，请重试', '' );
}
print $wl->toxml ();