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
 * Base class for views
 *
 * @version	$Id: class.tx_ptmvc_view.php 28339 2010-01-04 08:39:33Z fabriziobranca $
 * @package TYPO3
 * @subpackage pt_mvc
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-10-16
 */

/**
 * Requiring external classes
 */
require_once t3lib_extMgm::extPath('pt_mvc').'classes/class.tx_ptmvc_viewAbstract.php';
require_once t3lib_extMgm::extPath('pt_mvc').'classes/class.tx_ptmvc_div.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_smartyAdapter.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iTemplateable.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php';

/**
 * Base class for views
 *
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-10-16
 * @package TYPO3
 * @subpackage pt_mvc
 */
abstract class tx_ptmvc_viewSmarty extends tx_ptmvc_viewAbstract {

	/**
	 * @var tx_smarty_wrapper
	 */
	protected $smarty;

	/**
	 * @var array	additional local smarty congiruation
	 */
	protected $smartyLocalConfiguration = array();
	
	
	
	/**
	 * Constructor checking if the smarty extension is loaded
	 * 
	 * @param object|null $controller
	 * @param	string (optional) viewName
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-03-08
	 */
	public function __construct($controller = NULL, $viewName = NULL) {
		if (!t3lib_extMgm::isLoaded('smarty')) {
			throw new tx_pttools_exception('Smarty extensions is needed for smarty views');
		}
		parent::__construct($controller, $viewName);
	}

	
	
	/**
	 * Render!
	 *
	 * @param 	void
	 * @return 	string	HTML output
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-10
	 */
	public function render() {

		// setting up smarty
		$this->smarty = new tx_pttools_smartyAdapter($this, $this->smartyLocalConfiguration);

		if (!$this->runInNoConfigurationMode) {
        	$this->smarty->assign('conf', t3lib_div::removeDotsFromTS($this->_extConf));
		}
		
		// provide some additional variables if in frontend context
		$presetVariables = array();
        if ($GLOBALS['TSFE'] instanceof tslib_fe) {
        	$presetVariables['currentPage'] = $GLOBALS['TSFE']->id; 
        	$presetVariables['baseURL'] = $GLOBALS['TSFE']->tmpl->setup['config.']['baseURL'];
        	$presetVariables['feUserUid'] = ($GLOBALS['TSFE']->loginUser) ? $GLOBALS['TSFE']->fe_user->user['uid'] : 0;
        }
        if (is_object($this->controller) && property_exists($this->controller, 'prefixId') && !empty($this->controller->prefixId)) {
        	$presetVariables['prefixId'] = $this->controller->prefixId;
        	$presetVariables['cn'] = t3lib_extMgm::getCN($this->controller->extKey);
        }
        $this->smarty->assign($presetVariables);

        // append individual markers before rendering the template here.
        $this->beforeRendering();
        
        // add some markers defined by typoscript (if in frontend)
        if (is_array($this->viewConf['additionalMarkers.']) && ($GLOBALS['TSFE'] instanceof tslib_fe)) {
        	$additionalMarkers = tx_pttools_div::stdWrapArray($this->viewConf['additionalMarkers.']);
			$this->smarty->assign($additionalMarkers);
        }

        // generic "marker array hook"
        if (is_array($this->viewConf['markerArrayHooks.'])) {
        	foreach ($this->viewConf['markerArrayHooks.'] as $tsKey => $hook) {
        		if (substr($tsKey, -1) != '.') {
		        	$params = array(
		        		'conf' => $this->viewConf['markerArrayHooks.'][$tsKey.'.'],
		        	);
		        	t3lib_div::callUserFunction($hook, $params, $this);
        		}
        	}
        }

		// assigning all elements of "$this" (as $this is a collection object) to the template
		$this->smarty->assign($this->itemsArr);

		// render!
		$output = $this->smarty->fetch('file:'.$this->templateFilePath);

		// post-processing: reuse the variables as markers to replace
		$replace = array();
		foreach ($presetVariables as $key => $value) {
			$replace['###' . strtoupper($key) . '###'] = $value;
		}
        $output = str_replace(array_keys($replace), array_values($replace), $output);

        $output = $this->afterRendering($output);
        
        // stdWrap
        if (($GLOBALS['TSFE'] instanceof tslib_fe) && ($GLOBALS['TSFE']->cObj instanceof tslib_cObj)) {
        	$output = $GLOBALS['TSFE']->cObj->stdWrap($output, $this->viewConf['outputWrap.']);
        }

		return $output;
	}

	
	
	/**
	 * Set the local configuration for smarty
	 *
	 * @param 	array	smartyLocalConfiguration
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-11-06
	 */
	public function set_smartyLocalConfiguration(array $smartyLocalConfiguration) {
		$this->smartyLocalConfiguration = $smartyLocalConfiguration;
	}

}

?>