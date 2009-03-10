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

?>