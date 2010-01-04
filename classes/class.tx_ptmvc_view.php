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
 * @version	$Id$
 * @package TYPO3
 * @subpackage pt_mvc
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-10-16
 */

/**
 * Requiring external classes
 */
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
abstract class tx_ptmvc_view extends tx_pttools_collection {

	/**
	 * @var string 	path to the template file
	 */
	protected $templateFilePath;

	/**
	 * @var string	path to the language file
	 */
	protected $languageFilePath;

	/**
	 * @var tx_smarty_wrapper
	 */
	protected $smarty;

	/**
	 * @var string	view name
	 */
	protected $viewName;

	/**
	 * @var array	extension configuration from the registry
	 */
	protected $_extConf = array();

	/**
	 * @var array	view configuration
	 */
	protected $viewConf = array();

	/**
	 * @var tx_ptmvc_controller	reference to the controller
	 */
	protected $controller;

	/**
	 * @var string extension key
	 */
	public $extKey = '';

	/**
	 * @var bool	if in "noConfigurationMode" the view does not try to read any configuration and does not complain when no configuration is found. All settings will be auto-generated regarding conventions
	 */
	protected $runInNoConfigurationMode = false;

	/**
	 * @var array	additional local smarty congiruation
	 */
	protected $smartyLocalConfiguration = array();


	/**
	 * Constructor
	 *
	 * @param	object|NULL	(optional) reference to the calling controller
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-18
	 */
	public function __construct($controller = NULL) {

		if (empty($this->extKey)) {
			$this->extKey = tx_ptmvc_div::getExtKeyFromCondensendExtKey(tx_ptmvc_div::getCondensedExtKeyFromClassName(get_class($this)));
		}
		tx_pttools_assert::isNotEmptyString($this->extKey, array('message' => 'No extKey set!'));

		if (!is_null($controller)) {
			$this->controller = $controller;
		}

		// Load extension configuration from registry
		if (!$this->runInNoConfigurationMode) {
			$this->getConfiguration();
			tx_pttools_assert::isNotEmptyArray($this->_extConf, array('message' => 'No extension configuration found after executing the getConfiguration method!'));
		}

		$this->getTemplateFilePath();

	}



	/**
	 * Render! (By default smarty is used to render the view. Overwrite this in your inheriting class for different renderings)
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
	 * This method will be called before rendering the view. Overwrite this in your inheriting view class if needed.
	 * e.g.: append items to "$this"
	 *
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-01-19
	 */
	public function beforeRendering() {
	}

	/**
	 * This method will be called after rendering the view. Overwrite this in your inheriting view class if needed.
	 * e.g.: replace additional "global" markers (###MARKERS###)
	 *
	 * @param 	string	rendered view output
	 * @return 	string	rendered view output
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-01-19
	 */
	public function afterRendering($output) {
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



	/**
	 * Returns the name of the view (all parts after tx_<condensedExtKey>_view_)
	 *
	 * @return 	string	view name
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-10
	 */
	public function getViewName() {
		if (empty($this->viewName)) {
			$parts = t3lib_div::trimExplode('_', get_class($this));
			$this->viewName = implode('_', array_slice($parts, 3)); // throw away "tx", "<condensedExtKey>", "view"
		}
		return $this->viewName;
	}



	/**
	 * Gets the template file path and sets it to $this->templateFilePath
	 *
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-02-15
	 */
	public function getTemplateFilePath() {
		if (!empty($this->templateFilePath)) {
			/**
			 * Check if the property is set first
			 */
			$pathSource = 'Property';

		} else {

			$templateFileName = $this->viewConf['template'];
			if (!empty($templateFileName)) {
				/**
				 * Try configured template file (if any)
				 */
				$this->templateFilePath = t3lib_div::getFileAbsFileName($templateFileName);
				$pathSource = 'Configuration';
				// if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Using configured template "%s" for view "%s"', $this->templateFilePath, $this->getViewName()), 'pt_list');
			} else {

				$templateBasePath = $this->_extConf['templateBasePath'];
				if (empty($templateBasePath)) {
					// Convention: Templates are located in EXT:<extKey>/template/
					// this path can be overwritten with "plugin.tx_<condensedExtKey>.templateBasePath
					$templateBasePath = 'EXT:'.$this->extKey.'/template/';
				}
				tx_pttools_assert::isNotEmptyString($templateBasePath);

				/**
				 * Construct own template file path
				 * Convention:
				 *     ViewName: <templateBasePath>/ViewName.tpl
				 * or
				 *     View_Name: <templateBasePath>/View/Name.tpl
				 */
				$nameParts = t3lib_div::trimExplode('_', $this->getViewName());
				$this->templateFilePath = $templateBasePath;
				if (count($nameParts) > 1) {
					$this->templateFilePath .= implode('/', array_slice($nameParts, 0 ,-1)) . '/';
				}
				$this->templateFilePath .= $this->getViewName() . '.tpl';
				
				$this->templateFilePath = t3lib_div::getFileAbsFileName($this->templateFilePath);
				$pathSource = 'Auto-generated';
			}
		}
		tx_pttools_assert::isFilePath($this->templateFilePath, array('message' => sprintf('Path "%s" not found or invalid (Source: "%s")!', $this->templateFilePath, $pathSource)));

		if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Using "%s" as template for view "%s" (%s)"', $this->templateFilePath, $this->getViewName(), $pathSource), 'pt_mvc', 0);

	}



	/**
	 * Get the configuration
	 *
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-02-27
	 */
	protected function getConfiguration() {

		// global configuration for this view under "plugin.tx_<condensedExtKey>.view.<viewName>"
		$tsKey = 'plugin.' . t3lib_extMgm::getCN($this->extKey) .'.';
		$this->_extConf = tx_pttools_div::typoscriptRegistry($tsKey);

		if (is_array($this->_extConf['view.'][$this->getViewName().'.'])) {
			$this->viewConf = $this->_extConf['view.'][$this->getViewName().'.'];
			if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Found view configuration for view "%s"', $this->getViewName()), 'pt_mvc', 0, $this->viewConf);
		}

		// Controller specific configuration for this view under "view.<viewName>" in the controller configuration.
		// If the contoller takes its configuration in the default way this is "plugin.tx_extKey.controller.<controllerName>.view.<viewName>
		// Controller specific configuration overwrites global view configuration
		$controllerConf = $this->controller->get_conf();
		if (is_array($controllerConf['view.'][$this->getViewName().'.'])) {
			$this->viewConf = t3lib_div::array_merge_recursive_overrule($this->viewConf, $controllerConf['view.'][$this->getViewName().'.']);
		}
	}



    /**
     * Overrides the collection's addItem method to make sure that only
     * - scalar values,
     * - arrays or
     * - objects implemeting the tx_pttools_iTemplateable or the ArrayAccess interface (tx_pttools_iTemplateable will be checked first)
     * can be added to the collection
     *
     * @param   mixed   item to add to the collection
     * @param   int     (optional) id
     * @param   boolean flag whether HTML should be filtered from the items values to prevent XSS when displaying the markers within a HTML page
     * @author  Fabrizio Branca <mail@fabrizio-branca.de>, Rainer Kuhn <kuhn@punkt.de>
     * @since   2009-02-02, extended for HTML filtering by default 2009-05-08
     */
    public function addItem($itemObj, $id=0, $filterHtml=true) {
        
        // if no HTML filtering requested: add items directly
        if ($filterHtml == false) {
            if ($itemObj instanceof tx_pttools_iTemplateable) {
                parent::addItem($itemObj->getMarkerArray(), $id);
            } elseif (empty($itemObj) || is_scalar($itemObj) || is_array($itemObj) || ($itemObj instanceof ArrayAccess)) {
                parent::addItem($itemObj, $id);
            } else {
                throw new tx_pttools_exception('Item not allowed!');
            }
        
        // default: filter HTML code to prevent XSS when displaying the markers within a HTML page
        } else {
            if ($itemObj instanceof tx_pttools_iTemplateable) {
                parent::addItem(tx_pttools_div::htmlOutputArray($itemObj->getMarkerArray()), $id);
            } elseif (empty($itemObj)) {
                parent::addItem($itemObj, $id);
            } elseif (is_scalar($itemObj)) {
                parent::addItem(tx_pttools_div::htmlOutput($itemObj), $id);
            } elseif (is_array($itemObj)) {
                parent::addItem(tx_pttools_div::htmlOutputArray($itemObj), $id);
            } elseif ($itemObj instanceof ArrayAccess) {
                parent::addItem(tx_pttools_div::htmlOutputArrayAccess($itemObj), $id);
            } else {
                throw new tx_pttools_exception('Item not allowed!');
            }
            
        }
        
    }



}

?>