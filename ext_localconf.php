<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// setting up the tcaobjects autoloader
if (t3lib_extMgm::isLoaded('tcaobjects')) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoloader'][$_EXTKEY] = array(
		'partsMatchDirectories' => true,
		'classPaths' => array(
			// ordered by where the most classes are to be autoloaded
			'classes',
		)
	);
}

$TYPO3_CONF_VARS['EXTCONF']['kickstarter']['sections']['ptmvccontroller'] = array(
	'classname'   => 'tx_ptmvc_kickstarter_section_controller',
	'filepath'    => 'EXT:pt_mvc/sections/class.tx_ptmvc_kickstarter_section_controller.php',
	'title'       => 'Create pt_mvc controller',
	'description' => 'Create pt_mvc controller classes',
	'singleItem'  => '',
);

?>