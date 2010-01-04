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
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php';

/**
 * Base class for all frontend controller classes
 *
 * @version 	$Id$
 * @author		Fabrizio Branca <mail@fabrizio-branca.de>
 * @package		TYPO3
 * @subpackage	pt_mvc
 */
class tx_ptmvc_controllerFrontend extends tx_ptmvc_controller {

	/**
	 * @var tslib_cObj	the current cObj will be stored here, when running as a frontend plugin
	 */
	public $cObj;
	
	/**
	 * @var t3lib_PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * @var bool	if true the controller merges flexform settings with configuration settings
	 */
	protected $mergeConfAndFlexform = true;
	
	/**
	 * @var int	current language uid
	 */
	protected $languageUid;


	/**
	 * Constructor
	 * cObj, extKey, prefixId and scriptRelPath will be set here
	 *
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-02-06
	 */
	public function __construct() {
		
		if (t3lib_div::compat_version('4.3')) {
			$this->pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		}
		
		$this->cObj = empty($this->cObj) ? $GLOBALS['TSFE']->cObj : $this->cObj;
		
		$this->languageUid = intval($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']) > 0 ? $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] : 0; // current language uid
		
		parent::__construct();
	}



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
		
		if (!empty($this->conf['prefixId_alternative'])) {
			$this->params = t3lib_div::array_merge_recursive_overrule($this->params, t3lib_div::GParrayMerged($this->conf['prefixId_alternative']));
		}

		// kept for backwards compatibility (this is needed as long the controller class inherits from tslib_pibase and pi_* methods are used
		$this->piVars =& $this->params;
	}



	/**
	 * Loads configuration
	 *
	 * This default method assumes that you are in a frontend context and loads
	 * the configuration under plugin.tx_<condensedExtKey>. to $this->_extConf
	 * Then it assumes that the controller specific configuration ist found under
	 * plugin.tx_<condensedExtKey>.<controllerName> and writes it to $this->conf
	 * Then it looks for a prefixId-specific configuration under
	 * plugin.tx_<condensedExtKey>.controller.<controllerName>.<prefixId> and merges it
	 * over the existing configuration
	 * At last it tries to read the plugin flexforms and merges (overrides) matching
	 * keys with the plugin configuration in $this->conf.
	 *
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-16
	 */
	protected function getConfiguration() {

		// this will set $this->_emConf;
		parent::getConfiguration();

		// load extension configuration from typoscript (TSFE assumed)
		$emConfPath = 'plugin.' . t3lib_extMgm::getCN($this->extKey) .'.';
        $this->_extConf = tx_pttools_div::typoscriptRegistry($emConfPath);

        // global configuration for this controller found under "plugin.tx_<condensedExtKey>.controller.<controllerName>."
        if (is_array($this->_extConf['controller.'][$this->getControllerName().'.'])) {
        	$this->conf = t3lib_div::array_merge_recursive_overrule($this->conf, $this->_extConf['controller.'][$this->getControllerName().'.']);
        }

        // prefixId specific configuration for this controller found under "plugin.tx_<condensedExtKey>.controller.<controllerName>.<prefixId>."
        $prefixIdSpecificConfiguration = $this->_extConf['controller.'][$this->getControllerName().'.'][$this->prefixId.'.'];
        if (is_array($prefixIdSpecificConfiguration)) {
        	if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Found prefixId-specific configuration for prefixId "%s" in controller "%s"', $this->getControllerName(), $this->prefixId), 'pt_mvc', 0, $prefixIdSpecificConfiguration);
        	$this->conf = t3lib_div::array_merge_recursive_overrule($this->conf, $prefixIdSpecificConfiguration);
        } else {
        	if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Did not find prefixId-specific configuration for prefixId "%s" in controller "%s"', $this->getControllerName(), $this->prefixId), 'pt_mvc', 0, $prefixIdSpecificConfiguration);
        }

        // merge with flexforms
        if ($this->mergeConfAndFlexform) {
			tx_pttools_div::mergeConfAndFlexform($this, true);
        }
	}



	/**
	 * Checks if the action method exists locally or in a hook. Actions defined in a hook take priority over local defined actions
	 *
	 * @example
	 * <code>
	 * plugin.tx_<condensedExtKey>.controller.<controllerName>.myTyposcriptAction = TEXT
	 * plugin.tx_<condensedExtKey>.controller.<controllerName>.myTyposcriptAction.value = Hello World
	 * </code>
	 * @param 	string	action method name
	 * @return 	string	'typoscript' = typoscript action found, see parent method for more results
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-16
	 */
	protected function actionMethodExists($actionMethodName) {

		if (!empty($this->conf[$actionMethodName]) && !empty($this->conf[$actionMethodName.'.'])) {
			$result = 'typoscript';
		} else {
			$result = parent::actionMethodExists($actionMethodName);
		}
		return $result;
	}



	/**
	 * Overwriting the callMethod to add support for typoscript actions
	 *
	 * @param 	string 	method name
	 * @param	 array 	(optional) parameter to be passed to the action
	 * @return 	string 	HTML output
	 */
	protected function callMethod($methodName, array $parameter = array()) {
		tx_pttools_assert::isNotEmptyString($methodName, array('message' => 'No method given!'));

		switch ($this->actionMethodExists($methodName)) {

			/**
			 * Typoscript cObject as action methods output
			 */
			case 'typoscript': {
				if ($GLOBALS['TT'] instanceof t3lib_timeTrack) $GLOBALS['TT']->push(get_class($this).'->'.$methodName. '()');
				$content = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf[$methodName], $this->conf[$methodName.'.']);
				if ($GLOBALS['TT'] instanceof t3lib_timeTrack) $GLOBALS['TT']->pull();
			} break;

			default: {
				$content = parent::callMethod($methodName, $parameter);
			}
		}

		return $content;
	}



	/**
	 * Init "old-style" language handling.
	 *
	 * Use this if you have to access (maybe from old code) language labels in the controller via pi_getLL()
	 *
	 * Expects a "locallang.xml" or a "locallang.php" file in the same folder of the controllers class file
	 *
	 * @param	string	(optional) path to locallang file
	 * @return  void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-11-14
	 */
	protected function initLL($locallangPath = NULL) {
		if ($GLOBALS['TSFE']->config['config']['language'])	{
			$this->LLkey = $GLOBALS['TSFE']->config['config']['language'];
			if ($GLOBALS['TSFE']->config['config']['language_alt'])	{
				$this->altLLkey = $GLOBALS['TSFE']->config['config']['language_alt'];
			}
		}
		$this->pi_loadLL($locallangPath);
	}



	/**
	 * Load LL clone
	 *
	 * @param 	string	(optional) path to locallang file
	 * @return 	void
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 	2008-12-28
	 */
	public function pi_loadLL($locallangPath = NULL) {
		if (!$this->LOCAL_LANG_loaded && $this->scriptRelPath)	{
			if (is_null($locallangPath)) {
				$basePath = t3lib_extMgm::extPath($this->extKey).dirname($this->scriptRelPath).'/locallang.php';
			} else {
				$basePath = $locallangPath;
			}

				// Read the strings in the required charset (since TYPO3 4.2)
			$this->LOCAL_LANG = t3lib_div::readLLfile($basePath,$this->LLkey,$GLOBALS['TSFE']->renderCharset);
			if ($this->altLLkey)	{
				$tempLOCAL_LANG = t3lib_div::readLLfile($basePath,$this->altLLkey);
				$this->LOCAL_LANG = array_merge(is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array(),$tempLOCAL_LANG);
			}

				// Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
			if (is_array($this->conf['_LOCAL_LANG.']))	{
				reset($this->conf['_LOCAL_LANG.']);
				while(list($k,$lA)=each($this->conf['_LOCAL_LANG.']))	{
					if (is_array($lA))	{
						$k = substr($k,0,-1);
						foreach($lA as $llK => $llV)	{
							if (!is_array($llV))	{
								$this->LOCAL_LANG[$k][$llK] = $llV;
									// For labels coming from the TypoScript (database) the charset is assumed to be "forceCharset" and if that is not set, assumed to be that of the individual system languages
								$this->LOCAL_LANG_charset[$k][$llK] = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : $GLOBALS['TSFE']->csConvObj->charSetArray[$k];
							}
						}
					}
				}
			}
		}
		$this->LOCAL_LANG_loaded = 1;
	}

    /**
     * Wrapper for language label retrieval (will be isolated from tslib_pibase in the future)
     *
     * @param   see tx_ptmvc_controllerFrontend::pi_getLL()
     * @return  see tx_ptmvc_controllerFrontend::pi_getLL()
     * @author  Fabrizio Branca <mail@fabrizio-branca.de>
     * @since   2009-03-10
     */
    public function getLL($key, $alt='', $hsc=FALSE) {
        return $this->pi_getLL($key, $alt, $hsc);
    }


	/**
	 * Wrapper for pi_getLL
	 *
	 * @deprecated use tx_ptmvc_controllerFrontend::getLL() instead
	 * @param 	see tslib_pibase::pi_getLL()
	 * @return 	see tslib_pibase::pi_getLL()
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-12-28
	 */
	public function pi_getLL($key, $alt='', $hsc=FALSE) {
		if (!$this->LOCAL_LANG_loaded) {
			// call initLL before!
			throw new tx_pttools_exception('Locallang was not loaded!');
		}
		return parent::pi_getLL($key, $alt, $hsc);
	}



	/***************************************************************************
	 * Generic actions
	 **************************************************************************/

	/**
	 * Redirect action
	 *
	 * @param 	array 	expects key "target" for local path to redirect to, e.g. generated by $GLOBALS['TSFE']->cObj->getTypoLink_URL()
	 * @return 	void 	(never returns, because it exits after sending the redirect header)
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-01-26
	 */
	public function redirectAction(array $params) {
		tx_pttools_assert::isNotEmptyString($params['target'], array('message' => 'No "target" key found in params array!'));
		tx_pttools_div::localRedirect($params['target']);
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_mvc/classes/class.tx_ptmvc_controllerFrontend.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_mvc/misc/class.tx_ptmvc_controllerFrontend.php']);
}

?>