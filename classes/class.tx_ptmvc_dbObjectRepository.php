<?php

require_once t3lib_extMgm::extPath('pt_mvc').'classes/class.tx_ptmvc_dbObjectCollection.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php';

/**
 * Base class for object repositories.
 * This class is intended to be extended by concrete repositories. Set the properties
 * tableName and className (and collectionClassName) in your inheriting class.
 * However, this class can also be used as it is, when passing those parameters to the class constructor.
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 * @since 2009-09-15
 */
class tx_ptmvc_dbObjectRepository {

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var string
	 */
	protected $collectionClassName = 'tx_ptmvc_dbObjectCollection';

	/**
	 * @var t3lib_DB
	 */
	protected $dbObj = NULL;

	/**
	 * @var int|string storage pid or alias, if this is set new records will be stored here
	 */
	protected $storagePid = NULL;

	/**
	 * Constructor
	 *
	 * @param string table name (optional)
	 * @param string class name (optional)
	 * @param string collection class name (optional)
	 */
    public function __construct($tableName=NULL, $className=NULL, $collectionClassName=NULL) {
    	if (!is_null($tableName)) {
    		$this->tableName = $tableName;
    	}
    	if (!is_null($className)) {
    		$this->className = $className;
    	}
    	if (!is_null($collectionClassName)) {
    		$this->collectionClassName = $collectionClassName;
    	}
		tx_pttools_assert::isNotEmptyString($this->tableName, array('message' => 'No table name set!'));
		tx_pttools_assert::isNotEmptyString($this->className, array('message' => 'No class name set!'));
		tx_pttools_assert::isNotEmptyString($this->collectionClassName, array('message' => 'No collection class name set!'));
		// if you want to use another dbObj set it to the property in your inheriting class before calling this parent's constructor
		if (is_null($this->dbObj)) {
			$this->dbObj = $GLOBALS['TYPO3_DB'];
		}
    }
    
    /**
     * This method is called before writing (insert or updated).
     * Overwrite this method if you need individual functionality
     * 
     * @param array $values
     * @return array
     */
    protected function processValuesBeforeSaving(array $values) {
        return $values;
    }
    
    /**
     * This method is called before inserting
     * Overwrite this method if you need individual functionality
     * 
     * @param array $values
     * @return array
     */
    protected function processValuesBeforeInserting(array $values) {
        return $values;
    }
    
    /**
     * This method is called before updating
     * Overwrite this method if you need individual functionality
     * 
     * @param array $values
     * @return array
     */
    protected function processValuesBeforeUpdating(array $values) {
        return $values;
    }


	/***************************************************************************
	 * Basic repository methods
	 **************************************************************************/

	/**
	 * Adds an object to this repository.
	 *
	 * @param object $object The object to add
	 * @return void
	 */
	public function add(tx_ptmvc_dbObject $object) {
		tx_pttools_assert::isInstanceOf($object, $this->className);

		$values = $object->getPropertyArray();
		
		$values = $this->processValuesBeforeSaving($values);

		// unset null fields
		foreach ($values as $key => $value) {
            if (is_null($value)) {
                unset($values[$key]); // this is crucial since TYPO3's exec_INSERTquery() will quote all fields including NULL otherwise!!
            }
        }

        $uid = $object->get_uid();

		if ($uid) {
			// updating
		    $values = $this->processValuesBeforeUpdating($values);
			tx_pttools_assert::isValidUid($uid);
			$where = 'uid='.intval($uid);
			$res = $this->dbObj->exec_UPDATEquery($this->tableName, $where, $values);
			tx_pttools_assert::isMySQLRessource($res, $this->dbObj);
		} else {
			// inserting
			$values = $this->processValuesBeforeInserting($values);
		    if (empty($values['pid']) && !is_null($this->storagePid)) {
		        $values['pid'] = tx_pttools_div::getPid($this->storagePid);
		    }
			$res = $this->dbObj->exec_INSERTquery($this->tableName, $values);
			tx_pttools_assert::isMySQLRessource($res, $this->dbObj);

				// updating the object's uid
			$lastInsertedId = $this->dbObj->sql_insert_id();
			$object->set_uid($lastInsertedId);
		}
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 */
	public function remove(tx_ptmvc_dbObject $object) {
		tx_pttools_assert::isInstanceOf($object, $this->className);
		tx_pttools_assert::isValidUid($object->get_uid());
	}

	/**
	 * Returns all objects of this repository.
	 *
	 * @param string (optional) where clause
	 * @param string (optional) order by clause
	 * @param string (optional) limit clause
	 * @param bool (optional) ignore enable fields
	 * @return tx_pttools_objectCollection An collection of objects, empty if no objects found
	 */
	public function findAll($where='', $orderBy='', $limit='', $ignoreEnableFields=false) {
		$collection = $this->createEmptyCollection();

        $select  = '*';
        $from    = $this->tableName;
        $where = empty($where) ? '1=1' : $where;
        if (!$ignoreEnableFields) {
        	$where  .= tx_pttools_div::enableFields($this->tableName);
        }
        $groupBy = '';

        $res = $this->dbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        tx_pttools_assert::isMySQLRessource($res, $this->dbObj);

        while (($recordValues = $this->dbObj->sql_fetch_assoc($res)) !== false) {
	        /* @var $object tx_ptmvc_dbObject */
	        $object = t3lib_div::makeInstance($this->className);
	        tx_pttools_assert::isInstanceOf($object, 'tx_ptmvc_dbObject'); // make sure this class inherits from the tx_ptmvc_dbObject class!
	        $object->setPropertiesFromArray($recordValues);
	        $collection->addItem($object, $recordValues['uid']);
        }

        $this->dbObj->sql_free_result($res);
        return $collection;
	}

	/**
	 * Returns all objects of this repository in a given pid
	 *
	 * @param int pid
	 * @param string (optional) where clause
	 * @param string (optional) order by clause
	 * @param string (optional) limit clause
	 * @param bool (optional) ignore enable fields
	 * @return tx_pttools_objectCollection An collection of objects, empty if no objects found
	 */
	public function findAllInPid($pid, $where='', $orderBy='', $limit='', $ignoreEnableFields=false) {
	    $where = empty($where) ? '1=1' : $where;
	    return $this->findAll('pid='.intval($pid) .' AND '.$where, $orderBy, $limit, $ignoreEnableFields);
	}

	/**
	 * Create an empty object collection
	 *
	 * @param void
	 * @return tx_pttools_objectCollection
	 */
	protected function createEmptyCollection() {
	    // TODO: in TYPO3 4.3 this should be changed!
		$collectionClassName = t3lib_div::makeInstanceClassName($this->collectionClassName);
		/* @var $collection tx_ptmvc_dbObjectCollection */
		$collection = new $collectionClassName($this->className);
		tx_pttools_assert::isInstanceOf($collection, 'tx_pttools_collection'); // make sure this class inherits from the tx_pttools_collection class!
		return $collection;
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param int $uid The identifier of the object to find
	 * @return false|tx_ptmvc_dbObject The matching object if found, otherwise NULL
	 */
	public function findByUid($uid) {
        return $this->findOne('uid = '.intval($uid));
	}

	/**
	 * Finds an object matching the given where clause.
	 *
	 * @param int $uid The identifier of the object to find
	 * @return false|tx_ptmvc_dbObject The matching object if found, otherwise NULL
	 */
	protected function findOne($where) {
        $select  = '*';
        $from    = $this->tableName;
        $where  .= ' '.tx_pttools_div::enableFields($this->tableName);
        $groupBy = '';
        $orderBy = '';
        $limit   = '1';

        $res = $this->dbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        tx_pttools_assert::isMySQLRessource($res, $this->dbObj);
        $recordValues = $this->dbObj->sql_fetch_assoc($res);
        $this->dbObj->sql_free_result($res);

        if ($recordValues !== false) {
	        /* @var $object tx_ptmvc_dbObject */
	        $object = t3lib_div::makeInstance($this->className);
	        tx_pttools_assert::isInstanceOf($object, 'tx_ptmvc_dbObject'); // make sure this class inherits from the tx_ptmvc_dbObject class!
	        $object->setPropertiesFromArray($recordValues);
	        return $object;
        } else {
        	return false;
        }
	}
	
	/**
	 * Quote string
	 * 
	 * @param string $string
	 * @return string quoted string
	 */
	public function quote($string) {
	    return $GLOBALS['TYPO3_DB']->quoteStr($string, $this->tableName);
	}
	
	/**
	 * Full quote string
	 * 
	 * @param string $string
	 * @return string quoted string
	 */
	public function fullQuote($string) {
	    return $GLOBALS['TYPO3_DB']->fullQuoteStr($string, $this->tableName);
	}

}

?>