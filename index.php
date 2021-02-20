<?php
namespace Stocks;

//Get rid of this fucking ini_set shit (or set it to false) in prod
ini_set('display_errors',1);

//This is the main script. Do not edit this.
//To make changes to this webapp, you can:
//  REQUIRED: Configure your installation in /includes/config/config.php
//  Add custom algorithms in /includes/algos/ -> see example.algo
//  Re-arrange your layouts in /includes/layouts/*.php
//  Customize the templates in /lib/templates/*/
//  Add custom widgets to /lib/templates/*/widgets/ (then go to /includes/config/dashboard.php to place it)
//  Add API connectors and keys in /includes/api/{apiname}/api.php -> See polygonio for required data format and function names

//If you touch anything below this line, you're on your own

require_once __DIR__.'/includes/maininclude.php';

if(is_dir(__DIR__.'/'.$GLOBALS['selected_theme'])){
	$themedir = __DIR__.'/lib/templates/'.$GLOBALS['selected_theme'];
}else{
	$themedir = __DIR__.'/lib/templates/default/';
}
$loader = new \Twig\Loader\FilesystemLoader($themedir);

//set the basepath from /includes/config/config.php
$data['basepath'] = $basepath;

$valid_layoutfiles = scandir(__DIR__.'/includes/config/layouts/');
unset($valid_layoutfiles[0],$valid_layoutfiles[1]); //[0]=. and [1]=.. so unset them since they're not valid layout files
if(in_array($_REQUEST['page'].'.php', $valid_layoutfiles)){
	$page = $_REQUEST['page'];
	require_once __DIR__.'/includes/config/layouts/'.$page.'.php';
	if(!in_array($page, $GLOBALS['pageLayouts']['availableLayouts'])){
		echo 'Layout engine error! Could not validate layout file. Please make sure this page name is properly declared in $pageLayouts[availableLayouts].';
		exit;
	}
	if(!is_file($themedir.'/'.$GLOBALS['pageLayouts']['page'][$page]['basetpl'])){
		echo 'Layout engine error! Could not find base template file for this page. Please check that it is properly declared in $pageLayouts[page][{pagename}][basetpl]';
		exit;
	}
}else{
	unset($page);
}
exit;
switch($page){
	default:
		$twig = new \Twig\Environment($loader, [
			'cache' => false
		]);
		$data['title'] = 'Stonks Home Page';
		echo $twig->render('home.twig', $data);
	break;
}

?>