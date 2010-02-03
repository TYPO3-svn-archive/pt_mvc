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
/**
 * Base class for all controller classes with some initialization and a controller
 *
 * @version $Id$
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @package	TYPO3
 * @subpackage pt_mvc
 */

/**
 * TYPO3 Classes
 */
require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Classes from the "pt_tools" extension
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php';

/**
 * Classes from the same extension
 */
require_once t3lib_extMgm::extPath('pt_mvc').'classes/class.tx_ptmvc_div.php';


/**
 * Base class for all controller classes with some initialization and a controller
 *
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since 	2008-10-10
 * @package TYPO3
 * @subpackage pt_mvc
 */
abstract class tx_ptmvc_controller extends tslib_pibase {

    const SUCCESS_EXIT   = 0;
    const FAILURE_EXIT   = 1;
    const EXCEPTION_EXIT = 2;

    /**
     * @var string prefix Id (equals class name by default)
     */
	public $prefixId = NULL;
	
	/**
	 * @var string script path (will be populated automatically with controller path by convention. See manual)
	 */
	public $scriptRelPath = NULL;
	
	/**
	 * @var string extension key (will be extracted automatically from the class name)
	 */
	public $extKey = NULL;


	/**
	 * This is kept as long this controller class inherits from tslib_pibase. These properties are needed if pi_* methods are used.
	 * The pi_* methods should be replaced by own ones (->link, ->url)
	 */
	// USER: $pi_checkCHash = true, $pi_USER_INT_obj = false; USER_INT: $pi_checkCHash = false, $pi_USER_INT_obj = true
	/**
	 * @var bool	If set, then links are 1) not using cHash and 2) not allowing pages to be cached. (Set this for all USER_INT plugins!)
	 */
	public $pi_USER_INT_obj = true;

	/**
	 * @var bool	If set, then caching is disabled if piVars are incoming while no cHash was set (Set this for all USER plugins!)
	 */
	public $pi_checkCHash = false;



	/**
	 * @var string	controller name of this controller (will be populated on first use)
	 */
	protected $controllerName;

	/**
	 * @var string	action
	 */
	protected $action = '';

	/**
	 * @var string	pluginMode. Each pluginMode can have an own default action "<pluginMode>DefaultAction"
	 */
	protected $pluginMode = NULL;

	/**
	 * @var array	extension configuration from registry
	 */
	protected $_extConf = array();

	/**
	 * @var array	controller configuration
	 */
	public $conf = array();

	/**
	 * @var array	configuration passed to the main() method
	 */
	protected $localConfiguration = array();

	/**
	 * @var array	extension manager configuration
	 */
	protected $_emConf = array();

	/**
	 * @var array	controller parameter (was piVars before)
	 */
	protected $params = array();

	/**
	 * @var int		0 on success. You can detect if the output is an error message or the actual output content by this flag
	 */
	protected $exit_status = 0;

    /**
     * @var int		the depth of the "doAction" stack
     */
    protected $actionLevel = 0;

    /**
     * @var string	stores the last rendered content
     */
    protected $lastRenderedContent;

    /**
     * @var string	content passed by the main function (nut used for now)
     */
    protected $content;



	/**
	 * Constructor
	 * extKey, prefixId and scriptRelPath will be set here
	 *
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-25
	 */
	public function __construct() {

		// extKey
		if (is_null($this->extKey)) {
			$this->extKey = tx_ptmvc_div::getExtKeyFromCondensendExtKey(tx_ptmvc_div::getCondensedExtKeyFromClassName(get_class($this)));
		}
		tx_pttools_assert::isNotEmptyString($this->extKey, array('message' => 'No extKey set!'));

		// prefixId
		if (is_null($this->prefixId)) {
			$this->prefixId = $this->getPrefixId();
		}

		// scriptRelPath
		if (is_null($this->scriptRelPath)) {
			// Convention: controller classes are located under controller/
			$className = get_class($this);
			$classNameArray = t3lib_div::trimExplode('_', $className, 1);
			$pathSegments = array_slice($classNameArray, 2, -1); // remove "tx", "<condensedExtKey>", and last segment
			$this->scriptRelPath = implode('/', $pathSegments) . '/class.' . $className . '.php'; // remove class name from the end.'class.'.get_class($this).'.php';
		}

		if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Constructing new "%s" with prefixId "%s" and scriptRelPath "%s"', get_class($this), $this->prefixId, $this->scriptRelPath), 'pt_mvc');
	}



	/**
	 * This will be called _after_ setting prefixId, scriptRelPath and extKey but _before_ everything else
	 * Use this instead of the constructor to extra code
	 *
	 * @param	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-23
	 */
	protected function bootstrap() {
	}



	/**
	 * Gets the controller parameters (known as "piVars" is in tslib_pibase)
	 * This method should set $this->parameters
	 * Overwrite this method in your inheriting class if you want to use parameters from outside
	 *
	 * @param	void
	 * @return 	void
	 * @author	Fabrizio Branca <fabrizio@scrbl.net>
	 * @since	2008-10-23
	 */
	protected function getParameters() {
		$this->params = array();
	}



	/**
	 * Get the configuration
	 *
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-02-01
	 */
	protected function getConfiguration() {

		// Extension manager configuration
		$this->_emConf = tx_pttools_div::returnExtConfArray($this->extKey, true);

		// Extension configuration (set this in your inheriting class)
		// $this->_extConf

		// Controller configuration (set this in your inheriting class)
		// $this->conf

	}



	/**
	 * Return the configuration array
	 *
	 * @param	void
	 * @return	array	configuration array
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-02-18
	 */
	public function get_conf() {
		return $this->conf;
	}



	/**
	 * Overwrite this method if you want to use the pluginMode
	 *
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-23
	 */
	protected function getPluginMode() {
		if (is_null($this->pluginMode)) {
			$this->pluginMode = $this->conf['pluginMode'];
		}
	}



	/**
	 * Returns the prefixId for this controller
	 *
	 * @param 	void
	 * @return 	string 	prefixId
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-01-20
	 */
	protected function getPrefixId() {
		return get_class($this);
	}



	/**
	 * Initialization method. Override in inheriting class if something should be done before calling an action.
	 *
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-17
	 */
	protected function init() {
	}



	/**
	 * Before exit method. Override in inheriting class if something should be done before calling an action.
	 *
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-31
	 */
	protected function beforeExit() {
	}



	/**
	 * Preparing the controller before processing the action
	 *
	 * @param	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-12-02
	 */
	public function prepare() {

		// bootstrapping
		$this->bootstrap();

		// getting the configuration
		$this->getConfiguration();

		// merge local configuration over existing
		$this->conf = t3lib_div::array_merge_recursive_overrule($this->conf, $this->localConfiguration);

		// getting the parameters
		$this->getParameters();

		// getting the plugin mode
		$this->getPluginMode();

		// initializing
		$this->init();

	}



	/**
	 * The main method of the plugin
	 *
	 * @param 	string		(optional) content
	 * @param 	array		(optional) configuration
	 * @return	string		HTML output
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-17
	 */
	public function main($content='', array $conf=array())	{

		try {

			$this->content = $content;
			if (!empty($conf)) {
                $this->localConfiguration = t3lib_div::array_merge_recursive_overrule($this->localConfiguration, $conf);
			}
			if (!empty($conf['prefixId'])) {
				$this->prefixId = $conf['prefixId'];
			}

			if (tx_pttools_debug::inDevContext()) {
				$GLOBALS['TYPO3_DB']->debugOutput = true;
			}

			$this->prepare();

			// process action
			$content = $this->doAction($this->getAction());

			$this->beforeExit();

		} catch (Exception $excObj) {

			$this->error = true;

			if (method_exists($excObj, 'handle')) {
				$excObj->handle();
			}
			
			$content = $this->outputException($excObj);

			$this->exit_status = self::EXCEPTION_EXIT;
		}

		$this->lastRenderedContent = $content;

		return $content;
	}


	/**
	 * This method will be called if an exception was catched. It can be used to display debugging information
	 * while developing
	 *
	 * @param Exception $excObj
	 * @return string the output of this method will be displayed as controller output
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-15
	 */
	protected function outputException(Exception $excObj) {

		if (tx_pttools_debug::inDevContext()) {

			// output exception info in popup window
			if (t3lib_extMgm::isLoaded('cc_debug') && is_object($GLOBALS['errorList'])) {
				$GLOBALS['errorList']->add(array(
					'level'		=> E_ERROR,
					'message'	=> tx_pttools_debug::exceptionToHTML($excObj),
					'file'		=> $excObj->getFile(),
					'line'		=> $excObj->getLine(),
					'variables'	=> array(),
					'signature'	=> mt_rand(),
				));
			} else {
				tx_pttools_div::outputToPopup(tx_pttools_debug::exceptionToHTML($excObj));
			}
		}
		
		return $excObj->__toString();
	}



	/**
	 * Returns the action
	 *
	 * @param	void
	 * @return 	string	action
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-23
	 */
	protected function getAction() {
		return $this->params['action'];
	}



	/**
	 * Returns exit status
	 *
	 * @param 	void
	 * @return 	int		exit status, 0 is success
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-16
	 */
	public function get_exitstatus() {
		return $this->exit_status;
	}



	/**
	 * Create view object
	 *
	 * @param 	string	(optional) name of the view, if empty: <controllername>_default
	 * @return 	tx_ptmvc_view
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-10
	 */
	public function getView($viewName = '') {
		if (empty($viewName)) {
			$viewName = $this->getControllerName() . '_default';
		}

		// check controller specific view configuration first
		$viewClassFromConfiguration = $this->conf['view.'][$viewName . '.']['class'];
		if (empty($viewClassFromConfiguration)) {
			// check global view configuration
			$viewClassFromConfiguration = $this->_extConf['view.'][$viewName . '.']['class'];
		}

		if (!empty($viewClassFromConfiguration)) {

			$classParts = explode(':', $viewClassFromConfiguration);
			$path = implode(':', array_slice($classParts, 0, -1));
			$viewClassName = end($classParts);

			if (!empty($path)) {
				$requireFile = t3lib_div::getFileAbsFileName($path);
				if ($requireFile)	t3lib_div::requireOnce($requireFile);
			}
		} else {
			$viewClassName = t3lib_extMgm::getCN($this->extKey) . '_view_' . $viewName;
			if (!class_exists($viewClassName)) {
				throw new tx_pttools_exception(sprintf('Class "%s" not found!', $viewClassName));
			}
		}

		$viewObj = new $viewClassName($this);
		tx_pttools_assert::isInstanceOf($viewObj, 'tx_ptmvc_view', array('message' => 'Object generated from typoscript configuration is not an instance of "tx_ptmvc_view".'));

		return $viewObj;
	}



	/**
	 * Returns the name of the controller (all parts after tx_<condensedExtKey>_controller_)
	 *
	 * @return 	string	controller name
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-10
	 */
	public function getControllerName() {
		if (empty($this->controllerName)) {
			$parts = t3lib_div::trimExplode('_', get_class($this));
			$this->controllerName = implode('_', array_slice($parts, 3)); // throw away "tx", "<condensedExtKey>", "controller"
		}
		return $this->controllerName;
	}



	/**
	 * Checks if the action method exists locally or in a hook. Actions defined in a hook take priority over local defined actions
	 *
	 * @param 	string	action method name
	 * @return 	string		'notfound' = no action found; 'class' = local action; 'hook' = action found in hook; 'hook_prefixId' = prefixId-specific hook
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-16
	 */
	protected function actionMethodExists($actionMethodName) {
		$result = 'notfound';
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_mvc']['controller_actions'][$this->getControllerName()][$this->prefixId][$actionMethodName])) {
			$result = 'hook_prefixId';
		} elseif (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_mvc']['controller_actions'][$this->getControllerName()][$actionMethodName])) {
			$result = 'hook';
		// } elseif (method_exists($this, $actionMethodName)) {
		} elseif (is_callable(array($this, $actionMethodName))) {
			$result = 'class';
		}
		return $result;
	}



	/**
	 * Calls a action
	 *
	 * @param 	string	(optional) name of the action, if empty the method is trying the default actions
	 * @param 	array	(optional) additional parameters to be passed to the action
	 * @return 	string	HTML Output
	 * @throws	tx_pttools_exception if action empty or no matching method is found
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-08
	 */
	public function doAction($action = '', array $parameter = array()) {

		// increasing the actionLevel
		$this->actionLevel += 1;
		tx_pttools_assert::isTrue($this->actionLevel < 25, array('message' => 'Controller actions cannot be nested more than 25 times!')); // Be sure not to redirect actions recursively!

		// retrieve action
		if (empty($action)) {
			/**
			 * Retrieve default actions...
			 */
			if (!empty($this->pluginMode) && $this->actionMethodExists($this->pluginMode.'DefaultAction')) {
				/**
				 * Look for a default action for the given pluginMode first
				 */
				$action = $this->pluginMode.'Default';
			} elseif ($this->actionMethodExists('defaultAction')) {
				/**
				 * Look for a default action regardless of the pluginMode
				 */
				$action = 'default';
			} else {
				/**
				 * Throw an exception if no action was given and none of the default actions can be found
				 */
				$message = !empty($this->pluginMode) ? 'or "'.$this->pluginMode.'DefaultAction" ' : '';
				throw new tx_pttools_exception('No default action method "defaultAction" '.$message.'found!');
			}
		}
		tx_pttools_assert::isNotEmptyString($action, array('message' => 'No action set!'));
		$actionMethod = $action.'Action';
		$result = $this->callMethod($actionMethod, $parameter);

		// decreasing the actionLevel
		$this->actionLevel -= 1;

		return $result;
	}



	/**
	 * Calls a method within this object
	 *
	 * @param 	string	name of the method
	 * @param 	array	(optional) array of paramters to pass to the called method
	 * @return 	mixed	return of the called method
	 * @throws	tx_pttools_exception if action empty or no matching method is found
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-08
	 */
	protected function callMethod($methodName, array $parameter = array()) {
		tx_pttools_assert::isNotEmptyString($methodName, array('message' => 'No method given!'));

		switch ($this->actionMethodExists($methodName)) {

			/**
			 * Local action method
			 */
			case 'class': {
				if ($GLOBALS['TT'] instanceof t3lib_timeTrack) $GLOBALS['TT']->push(get_class($this).'->'.$methodName. '()');
				$content = $this->$methodName($parameter);
				if ($GLOBALS['TT'] instanceof t3lib_timeTrack) $GLOBALS['TT']->pull();
			} break;

			/**
			 * Action method implemented in a hook (prefixId-specific)
			 */
			case 'hook_prefixId': {
				$funcName = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_mvc']['controller_actions'][$this->getControllerName()][$this->prefixId][$methodName];
				if ($GLOBALS['TT'] instanceof t3lib_timeTrack) $GLOBALS['TT']->push('Action method from hook "'.$funcName.'" (prefixId-specific)');
				$params = array();
				$content = t3lib_div::callUserFunction($funcName, $params, $this, '');
				if ($GLOBALS['TT'] instanceof t3lib_timeTrack) $GLOBALS['TT']->pull();
			} break;

			/**
			 * Action method implemented in a hook
			 */
			case 'hook': {
				$funcName = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_mvc']['controller_actions'][$this->getControllerName()][$methodName];
				if ($GLOBALS['TT'] instanceof t3lib_timeTrack) $GLOBALS['TT']->push('Action method from hook "'.$funcName.'"');
				$params = array();
				$content = t3lib_div::callUserFunction($funcName, $params, $this, '');
				if ($GLOBALS['TT'] instanceof t3lib_timeTrack) $GLOBALS['TT']->pull();
			} break;

			case 0:
			case 'notfound':
			// break left out intentionally
			default: {
				throw new tx_pttools_exception(sprintf('Method "%s" not found for controller "%s"', $methodName, $this->getControllerName()));
			}
		}

		return $content;
	}



	/**
	 * Returns the last rendered content
	 *
	 * @param 	void
	 * @return 	string 	last rendered content
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-03-11
	 */
	public function get_lastRenderedContent() {
		return $this->lastRenderedContent;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_mvc/classes/class.tx_ptmvc_controller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_mvc/misc/class.tx_ptmvc_controller.php']);
}

?>