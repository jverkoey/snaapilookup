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
   * Fetch the hierarchy info.
   */
  public function fetch($category_id, $id) {
    $table = $this->getTable();
    $result = $table->fetchAll($table->select()->from($table, array('name', 'source_url'))
                                               ->where('category = ?', $category_id)
                                               ->where('id = ?', $id))->toArray();
    return empty($result) ? null : $result[0];
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
   * Fetch the ancestry of a category.
   */
  public function fetchAncestry($category, $id) {
    $table = $this->getTable();
    $info = $table->info();
    $db = $table->getAdapter();
    $sql = 'SELECT parent.id FROM '.$info['name'].' child, '.$info['name'].' parent ' .
           'WHERE parent.lft != 1 AND parent.category = child.category AND '.
                 'parent.lft < child.lft AND parent.rgt > child.rgt AND ' .
           $db->quoteInto('child.category = ?', $category) . ' AND ' .
           $db->quoteInto('child.id = ?', $id) . ';';
    return $db->query($sql)->fetchAll();
  }

  /**
   * Fetch all scrapeable hierarchies of a category.
   */
  public function fetchAllScrapeable($category) {
    $table = $this->getTable();
    return $table->fetchAll($table->select()->from($table, array('id', 'name', 'source_url'))
                                            ->where('category = ?', $category)
                                            ->where('DATEDIFF(NOW(), last_scraped) IS NULL OR DATEDIFF(NOW(), last_scraped) >= 7')
                                            ->where('scrapeable = 1'))->toArray();
  }

}
