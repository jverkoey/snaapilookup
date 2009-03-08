<?php

class Model_Logs {
  /** Model_DbTable_Logs */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_Logs
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/Logs.php';
      $this->_table = new Model_DbTable_Logs;
    }
    return $this->_table;
  }

  /**
   * Add a log entry.
   */
  public function add($type, $log) {
    $table = $this->getTable();
    $entry = array(
      'type' => $type,
      'log'  => $log,
      'time' => new Zend_Db_Expr('NOW()')
    );
    return $this->getTable()->insert($entry);
  }

}
