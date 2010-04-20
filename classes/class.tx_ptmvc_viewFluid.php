<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Fabrizio Branca (mail@fabrizio-branca.de)
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
 * Base class for fluid views
 *
 * @version	$Id: class.tx_ptmvc_view.php 28339 2010-01-04 08:39:33Z fabriziobranca $
 * @package TYPO3
 * @subpackage pt_mvc
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2010-03-08
 */

/**
 * Requiring external classes
 */
require_once t3lib_extMgm::extPath('pt_mvc').'classes/class.tx_ptmvc_viewAbstract.php';
require_once t3lib_extMgm::extPath('pt_mvc').'classes/class.tx_ptmvc_div.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iTemplateable.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php';

/**
 * Base class for fluid views
 *
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2010-03-08
 * @package TYPO3
 * @subpackage pt_mvc
 */
abstract class tx_ptmvc_viewFluid extends tx_ptmvc_viewAbstract {
	
	/**
	 * @var string template file extension
	 */
	protected $templateFileExtension = '.html';
	
	/**
	 * Constructor checking if the fluid extension is loaded
	 * 
	 * @param object|null $controller
	 * @param	string (optional) viewName
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-03-08
	 */
	public function __construct($controller = NULL, $viewName = NULL) {
		if (!t3lib_extMgm::isLoaded('fluid')) {
			throw new tx_pttools_exception('Fluid extensions is needed for fluid views');
		}
		parent::__construct($controller, $viewName);
	}

	/**
	 * Render! 
	 *
	 * @param 	void
	 * @return 	string	HTML output
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2010-03-08
	 */
	public function render() {
		$renderer = t3lib_div::makeInstance('Tx_Fluid_View_TemplateView'); /* @var $renderer Tx_Fluid_View_TemplateView */
		// $renderer->setControllerContext(t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_ControllerContext'));
		$renderer->setTemplatePathAndFilename($this->templateFilePath);
		$renderer->assignMultiple($this->itemsArr);
		return $renderer->render();
	}

}

?>