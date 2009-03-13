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
  public function fetchAll($category_id) {
    $table = $this->getTable();
    $info = $table->info();
    $db = $table->getAdapter();
    $sql = 'SELECT COUNT(parent.name)-1 AS depth, node.name AS name, node.id as id '.
           'FROM '.$info['name'].' AS node, '.
           $info['name'].' AS parent '.
           $db->quoteInto('WHERE parent.category = ?', $category_id).' AND '.
           $db->quoteInto('node.category = ?', $category_id).' AND '.
           'node.lft BETWEEN parent.lft AND parent.rgt '.
           'GROUP BY node.name '.
           'ORDER BY node.lft;';
    return $db->query($sql)->fetchAll();
  }

  /**
   * Fetch the id by name.
   */
  public function fetchByName($category, $parent, $name) {
    $table = $this->getTable();
    $info = $table->info();
    $db = $table->getAdapter();
    $sql = 'SELECT child.id FROM '.$info['name'].' child, '.$info['name'].' parent ' .
           'WHERE ' . $db->quoteInto('parent.id = ?', $parent) .' AND parent.category = child.category AND '.
                  $db->quoteInto('child.category = ?', $category) . ' AND ' .
                 'parent.lft < child.lft AND parent.rgt > child.rgt AND ' .
           $db->quoteInto('child.category = ?', $category) . ' AND ' .
           $db->quoteInto('child.name = ?', $name) . ';';
    $result = $db->query($sql)->fetchAll();
    return $result ? $result[0]['id'] : null;
  }

  /**
   * Fetch the hierarchy info.
   */
  public function fetch($category_id, $id) {
    $table = $this->getTable();
    $result = $table->fetchAll(
      $table
        ->select()
        ->from($table, array('source_url'))
        ->where('category = ?', $category_id)
        ->where('id = ?', $id)
    )->toArray();
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
   * Fetch the ancestry of a category.
   */
  public function insert($category, $parent, $name, $url) {
    return
'LOCK TABLE hierarchies WRITE;
SELECT @parentRight := rgt FROM hierarchies WHERE category = '.$category.' AND id = '.$parent.';
UPDATE hierarchies SET rgt = rgt + 2 WHERE category = '.$category.' AND rgt >= @parentRight;
UPDATE hierarchies SET lft = lft + 2 WHERE category = '.$category.' AND lft > @parentRight;
INSERT INTO hierarchies( category, lft, rgt, scrapeable, name, source_url ) VALUES( '.
      $category.', @parentRight, @parentRight + 1, 0, "'.$name.'", "'.$url.'");UNLOCK TABLES;';
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
