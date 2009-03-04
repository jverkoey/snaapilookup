<?php

class Model_Functions {
  /** Model_DbTable_Functions */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_Functions
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/Functions.php';
      $this->_table = new Model_DbTable_Functions;
    }
    return $this->_table;
  }

  /**
   * Insert or update a function.
   */
  public function insertOrUpdateFunction($fields) {
    $table = $this->getTable();
    $result = $table->fetchAll($table->select()->from($table, array('id'))
                                               ->where('category = ?', $fields['category'])
                                               ->where('hierarchy = ?', $fields['hierarchy'])
                                               ->where('name = ?', $fields['name']))->toArray();

    $fields['time_modified'] = new Zend_Db_Expr('NOW()');

    if( empty($result) ) {
      $fields['time_added'] = new Zend_Db_Expr('NOW()');
      return $table->insert($fields);
    } else {
      unset($fields['time_added']);
      unset($fields['category']);
      unset($fields['hierarchy']);
      unset($fields['name']);
      return $table->update(
        $fields,
        $table->getAdapter()->quoteInto('id = ?', $result[0]['id']));
    }
  }

}
