<?php

########################################################################
# Extension Manager/Repository config file for ext: "pt_mvc"
#
# Auto generated 18-09-2008 10:40
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'MVC Base',
	'description' => 'Base classes for a mvc extension',
	'category' => 'misc',
	'author' => 'Fabrizio Branca',
	'author_email' => 'branca@punkt.de',
	'shy' => '',
	'dependencies' => 'pt_tools,smarty',
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
	'author_company' => 'punkt.de GmbH',
	'version' => '0.0.1',
	'constraints' => array(
		'depends' => array(
			'pt_tools' => '',
			'smarty' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'If tx_ptmvc_controllerCli is used: PEAR Console_CommandLine (THIS IS JUST A HINT, please ignore if your server is correctly configured)' => '',
			'If tx_ptmvc_controllerCli is used: PEAR Console_Color (THIS IS JUST A HINT, please ignore if your server is correctly configured)' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:5:{s:9:"ChangeLog";s:4:"0548";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"1bdc";s:19:"doc/wizard_form.dat";s:4:"26d7";s:20:"doc/wizard_form.html";s:4:"c204";}',
);

?>