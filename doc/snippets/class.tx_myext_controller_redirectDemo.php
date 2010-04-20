<?php

/**
 * Demonstration redirects to controller actions
 *
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since 2010-04-20
 * @package	TYPO3
 * @subpackage	tx_ptmvc\snippets
 */
class tx_myext_controller_redirectDemo extends tx_ptmvc_controllerFrontend {
	
	/**
	 * Logout action
	 * 
	 * @return void (never returns, because of a redirect)
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-04-20
	 */
	public function logoutAction() {
		// do some logout stuff
		
		// redirect to sayGoodBye (on the same page)
		$redirectUrl = $this->actionUrl('sayGoodBye', array('name' => 'Chuck')); // add a third optional paramter to redirect to the same controller on another page
		$this->doAction('redirect', array('target' => $redirectUrl));
	}
	 	
	/**
	 * Say good bye action
	 * 
	 * @return string message
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-04-20
	 */
	public function sayGoodByeAction() {
		$name = $this->params['name'];
		return 'Good bye, ' . $name;
	}
		
}

?>