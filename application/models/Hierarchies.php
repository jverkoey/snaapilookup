<?php

class Model_Hierarchies {
  /** Model_DbTable_Hierarchies */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_Hierarchies
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/Hierarchies.php';
      $this->_table = new Model_DbTable_Hierarchies;
    }
    return $this->_table;
  }

  /**
   * Fetch all scrapeable hierarchies of a category.
   */
  public function fetchAllScrapeable($category_id) {
    $table = $this->getTable();
    return $table->fetchAll($table->select()->from($table, array('id', 'name', 'source_url'))
                                            ->where('category = ?', $category_id)
                                            ->where('scrapeable = 1'))->toArray();
  }

}
