<?php
/**
 * Siscon Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license, you can get it at www.fgsl.eti.br.
 *
 * @category   Siscon
 * @package    Siscon_Model
 * @subpackage Siscon_Model
 * @copyright  Copyright (c) 2014 Douglas Thyago Mariz de Assis (http://www.siscon.com.br)
 * @license   New BSD License
 * @version    1.0.0
 */

/**
 * Siscon_Model_Abstract
 */
abstract class Siscon_Model_Abstract 
{
	/**
	 * 
	 * @var Siscon_Db_Table_Abstract
	 */
	protected $_dbTable = null;
	protected $_filters = array();
	protected $_validators = array();

	public function __construct()
	{
		// discover the mapper class name from model name
		$dbTable = str_replace('Model', 'Model_DbTable', get_class($this));
		$this->_dbTable = new $dbTable();
                var_dump($this->_dbTable);
                exit;
	}

	public function getDbTable()
	{
		return $this->_dbTable;
	}
	
	public function getFilters($fieldName = null)
	{
		return isset($this->_filters[$fieldName]) ? $this->_filters[$fieldName] : array();
	}
	
	public function getValidators($fieldName = null)
	{
		return isset($this->_validators[$fieldName]) ? $this->_validators[$fieldName] : array();
	}

	/**
	 * Persist an entity
	 * $data is an associative array so: fieldname => value
	 * @param array $data
	 */
	public function save(array $data)
	{
		$fieldNames = $this->getDbTable()->getCols();		

		$fields = array();
		$unlockedData = array();
		foreach ($fieldNames as $fieldName)
		{
			$unlockedData[$fieldName] = $this->getDbTable()->getCastValue($fieldName,$data[$fieldName]);
			if ($this->getDbTable()->isLocked($fieldName)) continue;
			$fields[$fieldName] = $this->getDbTable()->getCastValue($fieldName,$data[$fieldName]);
		}
		
		$this->getDbTable()->getAdapter()->beginTransaction();
		try {
			if (isset($data['Insert']))
			{				
				$this->getDbTable()->insert($fields);
			}
			else
			{
				$fieldKey = $this->getDbTable()->getFieldKey();
				$where = $this->getDbTable()->getAdapter()->quoteInto("$fieldKey = ?", $unlockedData[$fieldKey]);
				$this->getDbTable()->update($fields,$where);
			}
			$this->getDbTable()->getAdapter()->commit();
		}
		catch(Exception $e )
		{
			$this->getDbTable()->getAdapter()->rollback();
			throw new Siscon_Exception(Siscon_Exception::TRANSACTION_ABORTED_MESSAGE . ' ' . $e->getMessage(), Siscon_Exception::TRANSACTION_ABORTED_CODE, $e);
		}
	}
	
	public function remove($key)
	{
		$where = $this->getDbTable()->getAdapter()->quoteInto("{$this->getDbTable()->getFieldKey()} = ?",$key);
		$this->getDbTable()->delete($where);
	}
}
