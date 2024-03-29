<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Fabrizio Branca (mail@fabrizio-branca.de)
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
/**
 * Base class for smarty views
 *
 * @version	$Id$
 * @package TYPO3
 * @subpackage pt_mvc
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-10-16
 */

/**
 * Requiring external classes
 */
require_once t3lib_extMgm::extPath('pt_mvc').'classes/class.tx_ptmvc_viewSmarty.php';

/**
 * Base class for views
 *
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2010-03-08
 * @package TYPO3
 * @subpackage pt_mvc
 */
abstract class tx_ptmvc_view extends tx_ptmvc_viewSmarty {
	
	/**
	 * This class is deprecated and does only exist for backwards compatibility.
	 * Back when there was only one view class (that rendered everything using smarty
	 * by default) tx_ptmvc_view was used in the controllers.
	 * 
	 * Now the common view functionality was extracted to tx_ptmvc_viewAbstract and the smarty specific
	 * functionality has been put into tx_ptmvc_viewSmarty.
	 * 
	 * As this class (tx_ptmvc_view) extends the tx_ptmvc_viewSmarty class nothing should change for
	 * you if you don't want to touch your code.
	 */
	
	
	/**
	 * Constructor writing a deprecation log message
	 * 
	 * @param object|null $controller
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-03-08
	 */
	public function __construct($controller = NULL) {
		if (t3lib_div::compat_version('4.3')) {
			t3lib_div::deprecationLog('Class "'.get_class($this).'" inherits from deprecated "tx_ptmvc_view". Please change this to "tx_ptmvc_viewSmarty"!');
		}
		parent::__construct($controller);
	}

}

?>