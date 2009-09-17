<?php

require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php';

/**
 * Generic collection for dbObjects
 *
 * @author fabrizio.branca
 */
class tx_ptmvc_dbObjectCollection extends tx_pttools_objectCollection {

	public function __construct($restrictedClassName) {
		tx_pttools_assert::isNotEmptyString($restrictedClassName);
		$this->restrictedClassName = $restrictedClassName;
	}

	public function get_restrictedClassName() {
		return $this->restrictedClassName;
	}

}

?>