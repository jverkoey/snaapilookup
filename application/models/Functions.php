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
   * Fetch direct descendants of a hierarchy
   */
  public function fetchDirectDescendants($category, $hierarchy) {
    $table = $this->getTable();
    $result = $table->fetchAll(
      $table
        ->select()
        ->from($table, array('name', 'id'))
        ->where('category = ?', $category)  
        ->where('hierarchy = ?', $hierarchy)
        ->order('name ASC')
    )->toArray();
    return empty($result) ? null : $result;
  }

  /**
   * Fetch a function name by id.
   */
  public function fetchName($category, $id) {
    $table = $this->getTable();
    $result = $table->fetchAll(
      $table
        ->select()
        ->from($table, array('name'))
        ->where('category = ?', $category)  
        ->where('id = ?', $id)
    )->toArray();
    return empty($result) ? null : $result[0]['name'];
  }

  /**
   * Fetch a specific function.
   */
  public function fetchByName($category, $name) {
    $table = $this->getTable();
    $result = $table->fetchAll(
      $table
        ->select()
        ->from(
          $table,
          array(
            'id',
            'hierarchy',
            'name',
            'short_description'
          ))
        ->where('category = ?', $category)  
        ->where('name = ?', $name)
    )->toArray();
    return empty($result) ? null : $result[0];
  }

  /**
   * Fetch all functions in a category.
   */
  public function fetchAll($category) {
    $table = $this->getTable();
    $result = $table->fetchAll(
      $table
        ->select()
        ->from($table, array(
            'id',
            'name',
            'hierarchy'
          ))
        ->where('category = ?', $category)
    )->toArray();
    return $result;
  }

  /**
   * Fetch a specific function.
   */
  public function fetch($category, $id) {
    $table = $this->getTable();
    $result = $table->fetchAll(
      $table
        ->select()
        ->from(
          $table,
          array(
            'url',
            'short_description',
            'time_added',
            'time_modified',
            'data'
          ))
        ->where('category = ?', $category)
        ->where('id = ?', $id)
    )->toArray();
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

  /**
   * Sets the data for a function.
   */
  public function setData($fields) {
    $table = $this->getTable();
    
    $category = $fields['category'];
    $id = $fields['id'];
    $fields['time_modified'] = new Zend_Db_Expr('NOW()');
    $fields['last_scraped'] = new Zend_Db_Expr('NOW()');
    unset($fields['time_added']);
    unset($fields['category']);
    unset($fields['id']);
    unset($fields['hierarchy']);
    unset($fields['name']);
    unset($fields['short_description']);
    unset($fields['time_added']);
    return $table->update(
      $fields,
      array(
        $table->getAdapter()->quoteInto('category = ?', $category),
        $table->getAdapter()->quoteInto('id = ?', $id),
      )
    );
  }

  /**
   * Update an entry's scrape time.
   */
  public function touch($category, $id) {
    $table = $this->getTable();
    return $table->update(
      array('last_scraped' => new Zend_Db_Expr('NOW()')),
      array(
        $table->getAdapter()->quoteInto('category = ?', $category),
        $table->getAdapter()->quoteInto('id = ?', $id)
      )
    );
  }

  /**
   * Fetch all scrapeable functions of a category.
   */
  public function fetchAllScrapeable($category) {
    $table = $this->getTable();
    return $table->fetchAll(
      $table
        ->select()
        ->from($table, array('id', 'name', 'url', 'hierarchy'))
        ->where('category = ?', $category)
        ->where('DATEDIFF(NOW(), last_scraped) IS NULL OR DATEDIFF(NOW(), last_scraped) >= 7')
        ->where('scrapeable = 1'))
      ->toArray();
  }

}
