<?php

class Model_Categories {
  /** Model_DbTable_Categories */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_Categories
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/Categories.php';
      $this->_table = new Model_DbTable_Categories;
    }
    return $this->_table;
  }

  /**
   * Fetch a specific category by id.
   */
  public function fetchName($id) {
    $table = $this->getTable();
    $result = $table->fetchAll($table->select()->from($table, array('name'))
                                               ->where('id = ?', $id))->toArray();
    return !empty($result) ? $result[0]['name'] : null;
  }

  /**
   * Fetch a specific category by name.
   */
  public function fetchCategoryByName($name) {
    $table = $this->getTable();
    $result = $table->fetchAll($table->select()->from($table, array('id'))
                                               ->where('name = ?', $name))->toArray();
    return !empty($result) ? $result[0]['id'] : null;
  }

  /**
   * Fetch a specific category by name.
   */
  public function fetchCategoryInfoByName($name) {
    $table = $this->getTable();
    $result = $table->fetchAll($table->select()->from($table, array('id', 'name', 'type'))
                                               ->where('name = ?', $name))->toArray();
    return !empty($result) ? $result[0] : null;
  }

  /**
   * Fetch all frameworks.
   */
  public function fetchAllFrameworks() {
    $table = $this->getTable();
    return $table->fetchAll($table->select()->from($table, array('id', 'name'))
                                            ->where('type = \'framework\''))->toArray();
  }

  /**
   * Fetch all languages.
   */
  public function fetchAllLanguages() {
    $table = $this->getTable();
    return $table->fetchAll($table->select()->from($table, array('id', 'name'))
                                            ->where('type = \'language\''))->toArray();
  }

}
