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
   * Fetch a specific function.
   */
  public function fetch($category, $id) {
    $table = $this->getTable();
    $result = $table->fetchAll($table->select()->from($table, array('url', 'short_description', 'time_added', 'time_modified'))
                                               ->where('category = ?', $category)
                                               ->where('id = ?', $id))->toArray();
    return empty($result) ? null : $result[0];
  }

  /**
   * Search for a function name.
   */
  public function search($name, $filters) {
    $name = str_replace('_', '\_', $name);
    $table = $this->getTable();
    $sql = $table->select()->from($table, array('id', 'category', 'hierarchy', 'name'))
                           ->where('name LIKE ?', '%'.$name.'%')
                           ->order($table->getAdapter()->quoteInto('CHAR_LENGTH(?) / CHAR_LENGTH(name) DESC', $name))
                           ->limit(10);
    if( count($filters) > 0 ) {
      $filter_ary = array();
      foreach( $filters as $filter ) {
        $filter_ary []= $table->getAdapter()->quoteInto('category = ?', $filter);
      }
      $sql->where(implode(' OR ', $filter_ary));
    }
    return $table->fetchAll($sql)->toArray();
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
      $category = $fields['category'];
      unset($fields['time_added']);
      unset($fields['category']);
      unset($fields['hierarchy']);
      unset($fields['name']);
      return $table->update(
        $fields,
        array(
          $table->getAdapter()->quoteInto('category = ?', $category),
          $table->getAdapter()->quoteInto('id = ?', $result[0]['id']),
        ));
    }
  }

}
