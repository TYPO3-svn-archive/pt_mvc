<?php 

require_once t3lib_extMgm::extPath('ptmvc').'classes/class.tx_ptmvc_controllerFrontend.php';

class tx_myext_controller_movies extends tx_ptmvc_controllerFrontend {
	
	protected function defaultAction() {
		return 'Default action';
	}
	
	protected function listViewAction() {
		return 'List view';
	}
	
	protected function singleViewAction() {
		return 'Single view';
	}

}

?>
