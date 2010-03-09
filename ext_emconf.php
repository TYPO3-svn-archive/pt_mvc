<?php

########################################################################
# Extension Manager/Repository config file for ext: "pt_mvc"
#
# Auto generated 26-10-2009 12:13
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'MVC Base',
	'description' => 'Base classes for mvc extensions',
	'category' => 'misc',
	'author' => 'Fabrizio Branca',
	'author_email' => 'mail@fabrizio-branca.de',
	'shy' => '',
	'dependencies' => 'pt_tools',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.4dev',
	'constraints' => array(
		'depends' => array(
			'pt_tools' => '0.4.2-',
			'smarty' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'kickstarter' => '0.4.0-',
			'smarty' => '',
			'tcaobjects' => '',
			'If tx_ptmvc_controllerCli is used: PEAR Console_CommandLine (THIS IS JUST A HINT, please ignore if your server is correctly configured)' => '',
			'If tx_ptmvc_controllerCli is used: PEAR Console_Color (THIS IS JUST A HINT, please ignore if your server is correctly configured)' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:16:{s:9:"ChangeLog";s:4:"d0eb";s:10:"README.txt";s:4:"9fa9";s:12:"ext_icon.gif";s:4:"5837";s:17:"ext_localconf.php";s:4:"0a5e";s:14:"doc/DevDoc.txt";s:4:"02db";s:14:"doc/manual.sxw";s:4:"2a3c";s:37:"classes/class.tx_ptmvc_controller.php";s:4:"1321";s:44:"classes/class.tx_ptmvc_controllerBackend.php";s:4:"8790";s:40:"classes/class.tx_ptmvc_controllerCli.php";s:4:"8b58";s:40:"classes/class.tx_ptmvc_controllerEid.php";s:4:"8f0d";s:45:"classes/class.tx_ptmvc_controllerFrontend.php";s:4:"cc9d";s:35:"classes/class.tx_ptmvc_dbObject.php";s:4:"5f18";s:45:"classes/class.tx_ptmvc_dbObjectCollection.php";s:4:"f4d7";s:45:"classes/class.tx_ptmvc_dbObjectRepository.php";s:4:"ca9b";s:30:"classes/class.tx_ptmvc_div.php";s:4:"f03e";s:31:"classes/class.tx_ptmvc_view.php";s:4:"bb85";}',
	'suggests' => array(
	),
);

?>