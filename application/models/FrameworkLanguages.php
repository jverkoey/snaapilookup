<?php

class Model_FrameworkLanguages {
  /** Model_DbTable_FrameworkLanguages */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_FrameworkLanguages
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/FrameworkLanguages.php';
      $this->_table = new Model_DbTable_FrameworkLanguages;
    }
    return $this->_table;
  }

}
