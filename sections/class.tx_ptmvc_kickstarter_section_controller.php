<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2008 Fabrizio Branca (mail@fabrizio-branca.de)  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/



// TODO: add properties _properties, _aliasMap, _values, _ignoreFields


require_once(t3lib_extMgm::extPath('kickstarter').'class.tx_kickstarter_sectionbase.php');
require_once(t3lib_extMgm::extPath('tcaobjects').'lib/spyc.php');

/**
 * Adds a section to the extension kickstarter
 *
 * @author  Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-03-15
 */
class tx_ptmvc_kickstarter_section_controller extends tx_kickstarter_sectionbase {

	public $sectionID = 'ptmvccontroller';

	public $pluginnr = -1;

	/**
	 * @var tx_kickstarter_wizard wizard object
	 */
	public $wizard;

	/**
	 * Renders the form in the kickstarter;
	 *
	 * @return	HTML
	 */
	public function render_wizard() {
		
		$action = explode(':',$this->wizard->modData['wizAction']);
		
		if ($action[0] == 'edit') {
			$piConf   = $this->wizard->wizArray[$this->sectionID][$action[1]];
		
			$ffPrefix ='['.$this->sectionID.']['.$action[1].']';
			
			// Enter title controller
			$subContent = '<strong>Controller name:</strong><br />';
			$subContent .= $this->returnName($this->wizard->extKey, 'tables', 'controller').'_';
			$subContent .= $this->renderStringBox($ffPrefix.'[name]',trim($piConf['name']));
			
			$subContent .= '<br /><br />';
			$subContent .= '<strong>Controller title:</strong><br />';
			$subContent .= $this->renderStringBox_lang('title', $ffPrefix, $piConf);
			
			$subContent .= '<br /><br />';
			$subContent .= '<strong>Include flexform configuration:</strong><br />';
			$subContent .= $this->renderCheckBox($ffPrefix.'[includeflex]',trim($piConf['includeflex']));
			
			$subContent .= '<br /><br />';
			$subContent .= '<strong>Cached (USER):</strong><br />';
			$subContent .= $this->renderCheckBox($ffPrefix.'[cached]',trim($piConf['cached']));
			
			$subContent .= '<br /><br />';
			$subContent .= '<strong>Actions:</strong><br />';
			$subContent .= 'Insert your actions here separated by new lines. Do not append "Action". E.g.: showSingle, showList, createNew,...<br />';
			$subContent .= 'Optionally you can append the viewName if it differs from the default one. E.g: showSingle|foo_bar<br />';
			if (!isset($piConf['actions'])) {
				$piConf['actions'] = 'default';
			}
			$subContent .= $this->renderTextareaBox($ffPrefix.'[actions]', trim($piConf['actions']));
			
			$subContent .= '<br /><br />';
			
		}
		
		return $subContent;
	}

	/**
	 * Renders the extension PHP code
	 *
	 * @param	string		$k: fieldname (key)
	 * @param	array		$config: pi config
	 * @param	string		$extKey: extension key
	 * @return	void
	 */
	public function render_extPart($k, $conf, $extKey) {
		$controllerClassPrefix = $this->returnName($extKey, 'class') . '_controller_';
		$controllerPathSuffix = 'controller/';
		
		$viewClassPrefix = $this->returnName($extKey, 'class') . '_view_';
		$viewPathSuffix = 'view/';
				
		
		
		if ($k == 1) {
			
			$this->wizard->ext_localconf[$extKey] = '$GLOBALS[$_EXTKEY.\'_controllerArray\'] = array('."\n";
			foreach ($this->wizard->wizArray[$this->sectionID] as $controllerConf) {
				$controllerName = $controllerConf['name'];
				$controllerOptions = array();
				if ($controllerConf['includeflex']) {
					$controllerOptions[] = "'includeFlexform' => true";
				}
				$this->wizard->ext_localconf[$extKey] .= "\t'_controller_$controllerName' => array(" . implode(', ', $controllerOptions). "),\n";
			}
			$this->wizard->ext_localconf[$extKey] .= ');'."\n";
			
			
			// add pt_mvc to the dependencies
			if (!t3lib_div::inList($this->wizard->wizArray['emconf'][1]['dependencies'], 'pt_mvc')) {
				if (!empty($this->wizard->wizArray['emconf'][1]['dependencies'])) {
					$this->wizard->wizArray['emconf'][1]['dependencies'] .= ',';
				}
				$this->wizard->wizArray['emconf'][1]['dependencies'] .= 'pt_mvc';
			}
			
			// add typoscript configuration file
			$this->addFileToFileArray(
				$tsFileName = 'typoscript/plugin.' . $this->returnName($extKey, 'class') .'.ts.php',
				'# <?php die(\'No Access!\'); ?>
				
plugin.' . $this->returnName($extKey, 'class') .' {
	
}'
			);
			
			// add static template
			$this->wizard->wizArray['ts']['pt_mvc_autogenerated'] = array(
				'title' => $extKey,
				'constants' => '',
				'setup' => '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:'.$extKey.'/'.$tsFileName.'">'."\n",
			);
			
		}
		
		
		$controllerName = $conf['name'];
		
		// add typoscript configuration file
		$this->addFileToFileArray(
			$tsFileName = 'typoscript/plugin.' . $this->returnName($extKey, 'class') .'.controller.'.$controllerName.'.ts.php',
			'# <?php die(\'No Access!\');
			
plugin.' . $this->returnName($extKey, 'class') .'.controller.'.$controllerName. ' {

}'
		);
		$this->wizard->wizArray['ts']['pt_mvc_autogenerated']['setup'] .= '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:'.$extKey.'/'.$tsFileName.'">'."\n";

		// include labels in locallang_db.xml
		foreach ($conf as $key => $value) {
			list($field, $language) = t3lib_div::trimExplode('_', $key);
			if (empty($language)) {
				$language = 'default';
			}
			if ($field == 'title') {
				$this->wizard->ext_locallang_db[$language]['tt_content.list_type_controller_'.$controllerName] = array($value);
			}
		}
		
		
		if ($conf['includeflex']) {
			
			$this->addFileToFileArray(
				$controllerPathSuffix . 'flexform_controller_'.$controllerName.'.xml', 
				'<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3DataStructure>

	<meta>
		<langDisable>1</langDisable>
	</meta>

	<sheets>

		<sDefault>
			<ROOT>
				<TCEforms>
	  				<sheetTitle>General Options</sheetTitle>
				</TCEforms>
				<type>array</type>
				<el>
                    
					<pluginMode>
	    				<TCEforms>
	      					<label>Plugin Mode</label>
							<config>
								<type>select</type>
								<items type="array">
									<numIndex index="0" type="array">
										<numIndex index="0">Show list view</numIndex>
										<numIndex index="1">showList</numIndex>
									</numIndex>
									<numIndex index="1" type="array">
										<numIndex index="0">Show single view</numIndex>
										<numIndex index="1">showSingle</numIndex>
									</numIndex>
								</items>	
							</config>
	    				</TCEforms>
	  				</pluginMode>
	  				
				</el>
			</ROOT>
		</sDefault>

	</sheets>
</T3DataStructure>'
			);
			
		}
		
		$controllerClassName = $controllerClassPrefix . $controllerName;
		$controllerFileName = $controllerPathSuffix;
		$controllerMiddlePath = implode('/', array_slice(explode('_', $controllerClassName), 3, -1)); 
		$controllerFileName .= $controllerMiddlePath . (!empty($controllerMiddlePath) ? '/' : ''); 
		$controllerFileName .= 'class.' . $controllerClassName . '.php';
		
		// process actions			
		$actions = array();
		foreach (t3lib_div::trimExplode(chr(10), $conf['actions']) as $line) {
			
			list($actionName, $viewName) = t3lib_div::trimExplode('|', $line);
			
			if (empty($viewName)) {
				$viewName = $controllerName.'_'.$actionName;
			}
			
			$actions[$viewName] = '
			
	/**
	 * Action '.$actionName.'
	 *
	 * @param void
	 * @return string HTML output
	 * @author '.$this->userField('name').' <'.$this->userField('email').'>
	 * @since '.date('Y-m-d').'
	 */
	public function '.$actionName.'Action() {
	
		// create view
		$view = $this->getView(\''.$viewName.'\');
		
		// do something with the model
		
		// assign the output to the view
		$view->addItem(\''.$this->userField('name').' says "Hello" from the action "'.$actionName.'" in controller "'.$controllerName.'"\', \'message\');
		
		// return the rendered view
		return $view->render();
	}
	
';
			// create the view class file
			$viewClassName = $viewClassPrefix . $viewName;
			$viewFileName = $viewPathSuffix;
			$viewMiddlePath = implode('/', array_slice(explode('_', $viewClassName), 3, -1)); 
			$viewFileName .= $viewMiddlePath . (!empty($viewMiddlePath) ? '/' : ''); 
			$viewFileName .= 'class.' . $viewClassName . '.php';
			$viewClassDescription = 'View class for action "'.$actionName.'" from controller "'.$controllerName.'"'; 
			$viewClassCode = 'class '.$viewClassName.' extends tx_ptmvc_view {
				
	protected $runInNoConfigurationMode = true;
	
}';
				
			$this->addFileToFileArray(
				$viewFileName, 
				$this->PHPclassFile(
					$extKey, 
					$viewFileName, 
					$viewClassCode, 
					$viewClassDescription
				)
			);
		
			// create the view template file
			$templateFileName = 'template/'.$viewMiddlePath.'/'.$viewName.'.tpl'; 
			$this->addFileToFileArray(
				$templateFileName, 
				'Message: <b>{$message}</b><br /><br />Current template file is EXT:'.$extKey.'/'.$templateFileName
			);
			
		} // end foreach (actions loop)
			
		$controllerClassCode = 'class '.$controllerClassName.' extends tx_ptmvc_controllerFrontend {' . implode('', $actions) . '}';
		$controllerClassDescription = 'Controller class for "'.$conf['title'].'"';
		
		$this->addFileToFileArray(
			$controllerFileName, 
			$this->PHPclassFile(
				$extKey, 
				$controllerFileName, 
				$controllerClassCode, 
				$controllerClassDescription
			)
		);
			
		
		if ($k == 1) {
		
			$this->wizard->ext_localconf[$extKey] .= '
			
		
$cN = t3lib_extMgm::getCN($_EXTKEY);
foreach (array_keys($GLOBALS[$_EXTKEY.\'_controllerArray\']) as $prefix) {
	
	$path = t3lib_div::trimExplode(\'_\', $prefix, 1);
	$path = implode(\'/\', array_slice($path, 0, -1)); // remove class name from the end
	
	// Add PlugIn to Static Template #43
	t3lib_extMgm::addPItoST43($_EXTKEY, $path.\'/class.\'.$cN.$prefix.\'.php\', $prefix, \'list_type\', 0);
}
';
			
			$this->wizard->ext_tables[$extKey] = '
t3lib_div::loadTCA(\'tt_content\');

foreach ($GLOBALS[$_EXTKEY.\'_controllerArray\'] as $prefix => $configuration) {
	$TCA[\'tt_content\'][\'types\'][\'list\'][\'subtypes_excludelist\'][$_EXTKEY.$prefix]=\'layout,select_key,pages,recursive\';
	
	// Adds an entry to the list of plugins in content elements of type "Insert plugin"
	t3lib_extMgm::addPlugin(array(\'LLL:EXT:\'.$_EXTKEY.\'/locallang_db.xml:tt_content.list_type\'.$prefix, $_EXTKEY.$prefix),\'list_type\');
	
	// Include flexform
	if ($configuration[\'includeFlexform\']) {
		$TCA[\'tt_content\'][\'types\'][\'list\'][\'subtypes_addlist\'][$_EXTKEY.$prefix] = \'pi_flexform\';
		t3lib_extMgm::addPiFlexFormValue($_EXTKEY.$prefix, \'FILE:EXT:\'.$_EXTKEY.\'/controller/flexform\'.$prefix.\'.xml\');
	}
}';
		}
		
		
		
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/sections/class.tx_kickstarter_section_tcaobjects.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/sections/class.tx_kickstarter_section_tcaobjects.php']);
}

?>