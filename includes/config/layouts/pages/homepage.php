<?php
namespace Stocks;

//this file defines the layout for the homepage

//add a new page by creating a new .php file in /includes/layouts/pages/{pagename}.php
//Add your new page to the top menu in /includes/layouts/menubar.php, or the side bar at sidebar.php
//add new widgets by creating a new .twig file in /lib/templates/widgets/{widgetname}.twig
//add new base templates by creating a new .twig file in /lib/templates/widgets/{basetplname}.twig

$pagename = 'homepage'; //your pagename variable must be unique, and match the filename (withou .php extension)
$pageLayouts['availableLayouts'][] = $pagename; 
$pageLayouts['page'][$pagename]['pagename'] = 'Home Page';
$pageLayouts['page'][$pagename]['description'] = 'This is the main page of your stonks application.';
$pageLayouts['page'][$pagename]['title'] = 'Home';
$pageLayouts['page'][$pagename]['uri'] = '/';
$pageLayouts['page'][$pagename]['basetpl'] = 'home.twig';
$pageLayouts['page'][$pagename]['hidesidebar'] = false;
$pageLayouts['page'][$pagename]['hidetopbar'] = false;

//These are positioned in order top left -> right
//bootstrap will automatically push them to a new row if the screen runs out of horizontal room.
//To add a new widget, just copy the array and set it to your widget parameters

$pageLayouts[$pagename]['tickertape'][] = [
	"widgetName" => 'ticker'
	//	'customCss' => 'background:black;', //set custom inline styles
	//	'customClass' => 'active', //Set a custom class name
	//  'customAttr' => 'onclick="alert(\'GME to the Moon\');" '; //set custom HTML attributes
];

/* Some samples if you want to force a new line
$pageLayouts[$pagename]['spaces'][] = [
	"widgetName" => 'hr',
];

$pageLayouts[$pagename]['spaces'][] = [
	"widgetName" => 'br',
	"customAttr" => 'clear="all"',
];
*/

$pageLayouts[$pagename]['spaces'][] = [
	"widgetName" => 'folders',
];


//unset the pagename variable after adding this page to the set
unset($pagename);



?>