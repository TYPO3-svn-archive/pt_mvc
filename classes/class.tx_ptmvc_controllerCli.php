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
require_once t3lib_extMgm::extPath('pt_mvc').'classes/class.tx_ptmvc_div.php';

require_once 'Console/CommandLine/Result.php';
require_once 'Console/CommandLine/Outputter.php';
require_once 'Console/Color.php';



/**
 * Base class for all cli controller classes with some initialization and a controller
 *
 * @version 	$Id$
 * @author		Fabrizio Branca <mail@fabrizio-branca.de>
 * @package		TYPO3
 * @subpackage	pt_mvc
 */
abstract class tx_ptmvc_controllerCli extends tx_ptmvc_controller {
	
	/**
	 * @var Console_CommandLine
	 */
	protected $commandLineParser;
	
	/**
	 * @var array command line arguments for the selected sub-command
	 */
	protected $arguments = array();
	
	/**
	 * @var array command line options for the selected sub-command
	 */
	protected $options = array();
	
	/**
	 * @var array command line arguments for the main command
	 */
	protected $mainCommandArguments = array();
	
	/**
	 * @var array command line options for the main command
	 */
	protected $mainCommandOptions = array();
	
	/**
	 * @var string	end of line
	 */
	protected $eol = "\n";
	
	/**
	 * @var bool	if false nothing will be outputted to stdout
	 */
	protected $verbose = true;
	
	/**
	 * @var bool	Strips ANSI color codes from output
	 */
	protected $stripcolors = false;
	

	
	/**
	 * Constructor (disable output buffering)
	 * 
	 * @param 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-11-10
	 */
	public function __construct() {
		ob_end_clean();
		parent::__construct();
	}
	
	
	
	/**
	 * Sets a PEAR Console_CommandLine object
	 * This method has to be called before the main() method
	 * 
	 * @param 	Console_CommandLine
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-25
	 */
	public function setCommandLineParser(Console_CommandLine $parser) {
		
		$this->commandLineParser = $parser;
		
		$this->commandLineParser->version = tx_ptmvc_div::getExtensionInfo($this->extKey, 'version');
		
		// add some "global" options
		$this->commandLineParser->addOption('quit', array(
			'short_name' => '-q',
			'long_name' => '--quit',
			'description' => 'do not print status messages to stdout',
			'action' => 'StoreTrue',
		));
		$this->commandLineParser->addOption('stripcolors', array(
			'short_name' => '-s',
			'long_name' => '--stripcolors',
			'description' => 'strips ANSI color codes from output',
			'action' => 'StoreTrue',
		));
		
		// parse the result
		$result = $this->commandLineParser->parse();
		
		// assign the arguments and options to internal properties
		$this->mainCommandArguments = $result->args;
		$this->mainCommandOptions = $result->options;
		$this->arguments = $result->command->args;
		$this->options = $result->command->options;
		$this->action = $result->command_name;
		
		// set some flags
		if ($this->mainCommandOptions['quit']) {
			$this->verbose = false;
		}
		if ($this->mainCommandOptions['stripcolors']) {
			$this->stripcolors = true;
		}
	}
	
	
	
	/**
	 * Returns the action, that was set before in setResult()
	 *
	 * @param	void
	 * @return 	string	action
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-25
	 */
	public function getAction() {
		return $this->action; 
	}
	
	
	
	/**
	 * Overwrites the outputException from parent class to output the error on stderr
	 *
	 * @param	Exception 	exception
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-26
	 */
	protected function outputException(Exception $excObj) {
		$msg = array();
		$msg[] = 'Controller: ' . $this->getControllerName();
		$msg[] = 'Message: %r%7 ' . $excObj->getMessage() . ' %n';
		$msg[] = 'File:    ' . $excObj->getFile() . '('.$excObj->getLine().')';
		$msg[] = 'Trace:';
		$msg[] = $excObj->getTraceAsString();
		$this->stderr($msg); 			
	}
	
	
	
	/**
	 * Wrapper for the command line's outputter object's stdout method
	 * Is an array is passed every line corresponds to a new line
	 * Supports colors (@see http://pear.php.net/manual/en/package.console.console-color.intro.php)
	 *
	 * @param 	string|array	message
	 * @param 	bool			(optional) true to add end of line, default: true
	 * @param 	vool			(optional) force output even if non in verbose mode
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-26
	 */
	protected function stdout($msg, $addEol = true, $forceOutput = false) {
		if ($this->verbose || $forceOutput) {
			if (is_array($msg)) {
				$msg = implode($this->eol, $msg);
			}
			$msg = Console_Color::convert($msg);
			if ($this->stripcolors){
				$msg = Console_Color::strip($msg);
			}
			if ($addEol) {
				$msg .= $this->eol;
			}
			tx_pttools_assert::isInstanceOf($this->commandLineParser->outputter, 'Console_CommandLine_Outputter', array('message' => 'No "Console_CommandLine_Outputter" found!'));
			$this->commandLineParser->outputter->stdout($msg);
		}
	}
	
	
	
	/**
	 * Wrapper for the command line's outputter object's stderr method
	 *
	 * @param 	string|array	message
	 * @param 	bool	(optional) true to add end of line, default: true
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-26
	 */
	protected function stderr($msg, $addEol = true) {
		if (is_array($msg)) {
			$msg = implode($this->eol, $msg);
		}
		$msg = Console_Color::convert($msg);
		if ($this->stripcolors){
			$msg = Console_Color::strip($msg);
		}
		if ($addEol) {
			$msg .= $this->eol;
		}
		tx_pttools_assert::isInstanceOf($this->commandLineParser->outputter, 'Console_CommandLine_Outputter', array('message' => 'No "Console_CommandLine_Outputter" found!'));
		$this->commandLineParser->outputter->stderr($msg);
	}
	
	
	
	/**
	 * Clears the "screen" by outputting some special characters
	 * 
	 * @param 	void
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-11-10
	 */
	protected function clear() {
		
		// passthru('clear');
		
		$clear = array(27, 91, 72, 27, 91, 50, 74, 0);
    	$output = '';
    	foreach ($clear as $char) { $output .= chr($char); }
    	$this->stdout($output, false);
	}
	
	
	
	/**
	 * Default action
	 * 
	 * @param 	void
	 * @return 	voi
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-25
	 */
	protected function defaultAction() {
		$this->stdout('%rNo command selected! Try option "-h" for help!%n');
	}
			
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_mvc/classes/class.tx_ptmvc_controllerCli.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_mvc/misc/class.tx_ptmvc_controllerCli.php']);
}

?>