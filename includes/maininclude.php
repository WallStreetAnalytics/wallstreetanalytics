<?php
//include master file

//autoincludes from composer
//see composer.json for requirements
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/config/config.php';

//this has to be included first, so do it outside of the loop below
require_once __DIR__.'/functions/apis/base/api.php';

//OK now include the rest of our API.php files
$dir = new \RecursiveDirectoryIterator(__DIR__.'/functions/apis/');
foreach(new \RecursiveIteratorIterator($dir) as $filename => $file){
	if($file->isDir() && $file->getFilename() != '..'){
		if(file_exists($filename.'/api.php')){
			require_once $filename.'/api.php';
		}
	}
}
/*
include(__DIR__.'/functions/apis/base/api.php');
include(__DIR__.'/functions/apis/polygon/api.php');
include(__DIR__.'/functions/apis/fintel/api.php');
include(__DIR__.'/functions/apis/lowfloatdotcom/api.php');
*/


//Don't you dare touch this
$rocketship[] = "                           *     .--.";
$rocketship[] = "                                / /  `";
$rocketship[] = "               +               | |";
$rocketship[] = "                      '        \ \__,";
$rocketship[] = "                  *          +   '--'  *";
$rocketship[] = "                      +   /\ ";
$rocketship[] = "         +              .'  '.   *";
$rocketship[] = "                *      /======\      +";
$rocketship[] = "                      ;:.  _   ;";
$rocketship[] = "                      |:. (_)  |";
$rocketship[] = "                      |:.  _   |";
$rocketship[] = "            +         |:. (_)  |          *";
$rocketship[] = "                      ;:.      ;";
$rocketship[] = "                    .' \:.    / `.";
$rocketship[] = "                   / .-'':._.'`-. \ ";
$rocketship[] = "            jgs   |/     /||\      \|";
$rocketship[] = "           wsb _..--\"\"\"````\"\"\"--.._";
$rocketship[] = "          _.-'``                    ``'-._";
$rocketship[] = "        -'                                '-";

?>