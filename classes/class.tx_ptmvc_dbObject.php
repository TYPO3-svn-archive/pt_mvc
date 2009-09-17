<?php

require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSettableByArray.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php';

/**
 * Very basic base class for domain objects
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 * @since 2009-09-15
 */
abstract class tx_ptmvc_dbObject implements tx_pttools_iSettableByArray, ArrayAccess {

	/**
	 * @var array
	 */
	protected $_values = array('uid' => NULL);

	/**
	 * Set properties from array (for the tx_pttools_iSettableByArray interface)
	 *
	 * @param array properties
	 * @return void
	 */
	public function setPropertiesFromArray(array $dataArray) {
		$this->_values = $dataArray;
	}

	/**
	 * Get all property values
	 *
	 * @param void
	 * @return array
	 */
	public function getPropertyArray() {
		tx_pttools_assert::isArray($this->_values);
		return $this->_values;
	}

	/**
	 * Magic call method to simulate getters and setters ("punkt.de"-style: $object->get_<exactPropertyName>())
	 *
	 * @param string methodname
	 * @param array parameters
	 * @return mixed void in case of a setter and
	 */
    protected function __call($methodName, $parameters) {
        $methodParts = explode('_', $methodName);
        $prefix = array_shift($methodParts);
		$property = implode('_', $methodParts);
    	switch ($prefix) {
			case 'get': {
				if (!$this->offsetExists($property)) {
					throw new tx_pttools_exception(sprintf('Property "%s" not found!', $property));
				}
				return $this->_values[$property];
			} break;

			case 'set': {
				$this->_values[$property] = $parameters[0];
				return $this; // this enables fluent interfaces like $object->set_firstName('Fabrizio')->set_lastName('Branca');
			} break;

			default: {
				throw new tx_pttools_exception(sprintf('"%s" was an invalid method call!', $methodName));
			}
    	}
    }
    
    /**
     * Generic getter method (that looks for concrete getter methods first)
     * 
     * @param string property name
     * @return mixed property value
     */
    protected function get($property) {
        tx_pttools_assert::isNotEmptyString($property);
        $methodName = 'get_'.$property;
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        } else {
            return $this->_values[$property];
        }
    }
    
    /**
     * Generic setter method (that looks for concrete setter methods first)
     * 
     * @param string property name
     * @param string property value
     * @return mixed property value
     */
    protected function set($property, $value) {
        tx_pttools_assert::isNotEmptyString($property);
        $methodName = 'set_'.$property;
        if (method_exists($this, $methodName)) {
            $this->$methodName($value);
        } else {
            $this->_values[$property] = $value;
        }
    }
    
    /***************************************************************************
     * Methods implementing the ArrayAccess interface
     **************************************************************************/
    
	/**
	 * @param offset
	 * @return bool
	 */
	public function offsetExists($offset) {
	    return array_key_exists($offset, $this->_values);
	}

	/**
	 * @param offset
	 */
	public function offsetGet($offset) {
	    return $this->get($offset);
	}

	/**
	 * @param offset
	 * @param value
	 */
	public function offsetSet($offset, $value) {
	    $this->set($offset, $value);
	}

	/**
	 * @param offset
	 */
	public function offsetUnset($offset) {
	    unset($this->_values[$offset]);
	}

}

?>