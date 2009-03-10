<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Fabrizio Branca (branca@punkt.de)
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

require_once PATH_t3lib . 'class.t3lib_tstemplate.php';

/**
 * Some stativ function
 *
 * @version $Id: class.tx_ptmvc_div.php,v 1.5 2009/02/27 16:19:11 ry44 Exp $
 * @author	Fabrizio Branca <branca@punkt.de>
 * @package	TYPO3
 * @subpackage pt_mvc
 */
class tx_ptmvc_div {

	/**
	 * @var array	lookup table: array('condensedExtKey' => 'full_extKey') for method getExtKeyFromCondensendExtKey
	 */
	public static $extKeyLookupTable = array();



	/**
	 * Returns the full extension key from the condensed extension key (with caching)
	 *
	 * @param 	string	condensedExtKey
	 * @return 	mixed	full_extKey or false if no extKey found
	 * @throws	tx_pttools_exception if no extension can be found
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-06-09
	 */
	public static function getExtKeyFromCondensendExtKey($condensedExtKey) {
		
		tx_pttools_assert::isNotEmpty($condensedExtKey);

		if (isset(self::$extKeyLookupTable[$condensedExtKey])) {
			return self::$extKeyLookupTable[$condensedExtKey];
		} else {
			$extKeys = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList']);
			foreach ($extKeys as $extKey) {
				if ($condensedExtKey == str_replace('_', '', $extKey)) {
					self::$extKeyLookupTable[$condensedExtKey] = $extKey;
					return $extKey;
				}
			}
		}
		throw new tx_pttools_exception(sprintf('Extension key for condensed extension key "%s" not found', $condensedExtKey));
		
	}
	
	

	/**
	 * Get condensened extension key from class name
	 *
	 * @param 	string	class name
	 * @return 	string 	condensed extension key
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-05-27
	 */
	public static function getCondensedExtKeyFromClassName($className) {
		list ( , $extKey) = t3lib_div::trimExplode('_', $className);
		return $extKey;
	}
	
	
	
	/**
	 * Returns the em configuration of an extension
	 * 
	 * @param 	string	extension key
	 * @param 	string	(optional) if a key is set not the whole configuration is returned, but only this key
	 * @return 	array|mixed	the whole configuration or only a single key
	 * @throws	tx_pttools_exceptionAssertion if the configuration or the given key is not found
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-10-25
	 */
	public static function getExtensionInfo($_EXTKEY, $key = '') {
		include (t3lib_extMgm::extPath($_EXTKEY) . 'ext_emconf.php');
		tx_pttools_assert::isNotEmptyArray($EM_CONF[$_EXTKEY]);
		if (!empty($key)) {
			tx_pttools_assert::isNotEmpty($EM_CONF[$_EXTKEY][$key]);
			return $EM_CONF[$_EXTKEY][$key];
		} else {
			return $EM_CONF[$_EXTKEY];
		}	
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_mvc/classes/class.tx_ptmvc_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_mvc/classes/class.tx_ptmvc_div.php']);
}

?>