<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('memory_limit', '128M');
if (function_exists('set_time_limit')) {
    @set_time_limit(0);
}
$options = getopt("s:");
if (!isset($options['s']) && $options['s']=='') {  
    echo "Testing case  A script\n\n";
    echo "OPTIONS:\n";
    echo "  -s       Select a spell to run this test. like [A]\n";
    exit();
}

$firstcode = isset($options['s']) ? trim($options['s']) : exit('no spell to run');

require_once __DIR__ . '/curl.php';

$curl = new Curl(getcwd()."/test_cookie.txt");

//$city = json_decode(file_get_contents(__DIR__ . '/city.json'),true);

echo $curl->get('http://baogz.com/api/4/company?company=北京&city=北京&page=1');exit;

foreach ($city[$firstcode] as $clist) {
	searchOneCity($clist);
}

function searchOneCity($param) {
	global $curl;
	$page = 1;
	$surl = "http://baogz.com/api/4/company?company=".$param['name']."&city=".$param['name']."&page=";
	
	do{	
		echo $geturl = $surl.$page;exit;
		$companylist = $curl->get($geturl);echo $companylist;exit;
		$companylist = json_decode($companylist,true);
		print_r($companylist);exit;
		$page++;
	} while ($page != false);
	
	
}
