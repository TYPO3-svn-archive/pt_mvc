<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Fabrizio Branca <mail@fabrizio-branca.de>
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

if (!defined('TYPO3_cliMode'))  die('You cannot run this script directly!');

require_once(PATH_t3lib.'class.t3lib_cli.php');

class tx_ptmvc_t3libCli extends t3lib_cli {

	/**
	 * Constructor
	 */
    public function __construct() {

        // Running parent class constructor
        parent::t3lib_cli();

        /*
        $this->cli_options[] = array('-d','Dry run');

        // Setting help texts:
        $this->cli_help['name'] = 'Put the title here';
        $this->cli_help['synopsis'] = '###OPTIONS###';
        $this->cli_help['description'] = 'Put the description here';
        $this->cli_help['examples'] = 'Put some example calls here';
        $this->cli_help['author'] = 'Fabrizio Branca (c) 2010';
		*/
    }

    /**
     * CLI engine
     *
     * @param array Command line arguments
     * @return void
     */
    public function cli_main(array $argv) {

    	try {
	        // get task (function)
	        $task = (string)$this->cli_args['_DEFAULT'][1];
			
			$task = !empty($task) ? $task : 'default';
	
	        $actionMethodName = $task.'Action';
	        if (method_exists($this, $actionMethodName)) {
	        	$this->$actionMethodName();
	        } else {
	        	throw new Exception(sprintf('No action method for action "%s" found!', $actionMethodName));
	        }
        } catch (Exception $excObj) {
			if (method_exists($excObj, 'handle')) {
				$excObj->handle();
			}
			$content = $this->outputException($excObj);
        }
	}
	
	/**
	 * Default action
	 * 
	 * @return void
	 */
	public function defaultAction() {
		$this->cli_echo('Please choose an action:' . chr(10));

		$methods = get_class_methods($this);
		foreach ($methods as $method) {
			if (substr($method, -6) == 'Action' && $method != 'defaultAction') {
				$this->cli_echo('    - ' . substr($method, 0, -6) . chr(10));
			}
		}
	}
	
	public function outputException(Exception $excObj) {
		return $excObj->__toString();
	}

}

?>