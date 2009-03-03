<?php

class Model_Languages {
  /** Model_DbTable_Languages */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_Languages
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/Languages.php';
      $this->_table = new Model_DbTable_Languages;
    }
    return $this->_table;
  }

  /**
   * Fetch all languages.
   */
  public function fetchAll() {
    return $this->getTable()->fetchAll()->toArray();
  }

}
