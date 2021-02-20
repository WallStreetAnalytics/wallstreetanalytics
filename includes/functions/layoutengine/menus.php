<?php
namespace Stocks;

public class menu extends LayoutEngine{
	
	function __constructor($params){
		$this->currentPage = $params['pagename'];
		$this->selectedTheme = $params['theme'];
		$this->allParams = $params;
	}
	
	function renderTopMenu($params = null){
		
	}
	
}

?>