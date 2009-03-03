<?php

class Model_Frameworks {
  /** Model_DbTable_Frameworks */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_Frameworks
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/Frameworks.php';
      $this->_table = new Model_DbTable_Frameworks;
    }
    return $this->_table;
  }

  /**
   * Fetch all frameworks.
   */
  public function fetchAll() {
    return $this->getTable()->fetchAll()->toArray();
  }

}
