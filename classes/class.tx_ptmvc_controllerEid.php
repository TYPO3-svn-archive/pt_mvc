<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Fabrizio Branca <mail@fabrizio-branca.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once t3lib_extMgm::extPath('pt_mvc').'classes/class.tx_ptmvc_controller.php';


/**
 * Base class for all eid controller classes with some initialization and a controller
 *
 * @version 	$Id$
 * @author		Fabrizio Branca <mail@fabrizio-branca.de>
 * @package		TYPO3
 * @subpackage	pt_mvc
 */
abstract class tx_ptmvc_controllerEid extends tx_ptmvc_controller {
	
	
	/**
	 * Loads the parameters (known as "piVars" is in tslib_pibase)
	 * 
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-23
	 */
	protected function getParameters() {
		
		// simply gets all post and get parameters prefixed with the controllers prefixId
		if (!empty($this->prefixId)) {
			if (t3lib_div::compat_version('4.3')) {
				$this->params = t3lib_div::_GPmerged($this->prefixId);	
			} else {
				$this->params = t3lib_div::GParrayMerged($this->prefixId);	
			}
		} else {
			$this->params = t3lib_div::array_merge_recursive_overrule(t3lib_div::_POST(), t3lib_div::_GET());
		}
		
		// kept for backwards compatibility (this is needed as long the controller class inherits from tslib_pibase and pi_* methods are used
		$this->piVars =& $this->params;
	}
	
	
	
	/**
	 * This method will be called if an exception was catched. It can be used to display debugging information
	 * while developing
	 *
	 * @param 	Exception $excObj
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-15
	 */
	protected function outputException(Exception $excObj) {
		if (tx_pttools_debug::inDevContext()) {
			echo tx_pttools_debug::exceptionToHTML($excObj);	
		} else {
			echo $excObj->getMessage();
		}
	}
	
		
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_mvc/classes/class.tx_ptmvc_controllerEid.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_mvc/misc/class.tx_ptmvc_controllerEid.php']);
}

?>