<?php
namespace Stocks;

//this file defines the layout for the top menu bar
//add a new page by creating a new .php file in /includes/layouts/pages/{pagename}.php
//Add your new page to the top menu in /includes/layouts/menubar.php, or the side bar at sidebar.php
//add new widgets by creating a new .twig file in /lib/templates/widgets/{widgetname}.twig
//add new base templates by creating a new .twig file in /lib/templates/widgets/{basetplname}.twig

$pagename = 'menubar'; //your pagename variable must be unique, and match the filename (withou .php extension)
$pageLayouts['availableMenus'][] = $pagename; 

$pageLayouts['topmenu']['links']['home'] = array[
	'textContent' => 'Home',
	'type' => 'link' //dropdown, link, button, input, widget
	'href' => $GLOBALS['basepath'],
];

$pageLayouts['topmenu']['links']['data'] = array[
	'textContent' => 'Data'
	'type' => 'dropdown', //dropdown, link, button, input, widget
	'children' => [
		'ticker' => [
			'textContent' => 'Ticker',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/ticker',
		],
		'financials' => [
			'textContent' => 'Financials',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/fins',
		],
		'gainers' => [
			'textContent' => 'Gainers',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/gainers',
		],
		'losers' => [
			'textContent' => 'Losers',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/losers',
		],
		'groups' => [
			'textContent' => 'Stock Groups',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/groups',
		]
	]
	'href' => '#/',
];

$pageLayouts['topmenu']['links']['screening'] = array[
	'textContent' => 'Screening'
	'type' => 'dropdown', //dropdown, link, button, input, widget
	'children' => [
		'algos' => [
			'textContent' => 'Algorithms',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/algos',
		],
		'reports' => [
			'textContent' => 'Reports',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/reports',
		],
		'calculations' => [
			'textContent' => 'Run Calculations',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/calculations',
		]
	]
	'href' => '#/',
];

$pageLayouts['topmenu']['links']['trading'] = array[
	'textContent' => 'Trade'
	'type' => 'dropdown', //dropdown, link, button, input, widget
	'children' => [
		'portfolio' => [
			'textContent' => 'Portfolio',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/portfolio',
		],
		'trade' => [
			'textContent' => 'Trade',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/trade',
		]
	]
	'href' => '#/',
];

$pageLayouts['topmenu']['links']['admin'] = array[
	'textContent' => 'Admin'
	'type' => 'dropdown', //dropdown, link, button, input, widget
	'children' => [
		'admin' => [
			'textContent' => 'Administration',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/admin',
		],
		'config' => [
			'textContent' => 'Configuration',
			'type' => 'link' //dropdown, link, button, input, widget
			'href' => '/configuration',
		]
	]
	'href' => '#/',
];

$pageLayouts['topmenu']['links']['tickerform'] = array[
	'type' => 'form' //dropdown, link, button, form, widget
	'formAction' => $GLOBALS['basepath'].'/ticker',
	'formMethod' => 'GET'
	'formElements' => [ //positioned from left to right in the top navbar, top to bottom in the left navbar, but to prevent breaks can declare customClass ='form-group'
		'name' => 'ticker',
		'id' => 'tftickerinput',
		'placeholder' => 'AAPL',
		'type' => 'text',
		'value' => '',
		'customClass' => '',
		'customStyle' => ''
	],
	[
		'name' => 'ticksubmit',
		'id' => 'ticksubmit',
		'type' => 'submit',
		'value' => 'Go',
		'customClass' => '',
		'customStyle' => ''
	]
	'customClass' => 'float-right',
];


//unset the pagename variable after adding this page to the set
unset($pagename);



?>