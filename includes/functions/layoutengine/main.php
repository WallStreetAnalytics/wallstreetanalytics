<?php

namespace Stocks;

class LayoutEngine{
	function __constructor($params){
		$this->currentPage = $params['pagename'];
		$this->selectedTheme = $params['theme'];
		$this->allParams = $params;
	}
}

?>