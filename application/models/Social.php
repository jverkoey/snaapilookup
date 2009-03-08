<?php

class Model_Social {
  /** Model_DbTable_Social */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_Social
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/Social.php';
      $this->_table = new Model_DbTable_Social;
    }
    return $this->_table;
  }

  public function normalizeURL($url) {
    if( preg_match("/^https?:\/\//", strtolower($url)) ) {
      return $url;
    }
    return null;
  }

  /**
   * Add a url.
   */
  public function addURL($category, $id, $url, $user_id) {
    $table = $this->getTable();
    $entry = array(
      'category'  => $category,
      'id'        => $id,
      'type'      => 'link',
      'data'      => $url,
      'user_id'   => $user_id
    );
    return $this->getTable()->insert($entry);
  }

  /**
   * Update the vote.
   */
  public function updateVote($category, $id, $index, $delta) {
    $table = $this->getTable();
    return $table->update(
      array(
        'score' => new Zend_Db_Expr($table->getAdapter()->quoteInto('score + ?', $delta))
      ),
      array(
        $table->getAdapter()->quoteInto('category = ?', $category),
        $table->getAdapter()->quoteInto('id = ?', $id),
        $table->getAdapter()->quoteInto('ix = ?', $index)
      )
    );
  }
  /**
   * Fetch all social data.
   */
  public function fetch($category, $id) {
    $table = $this->getTable();
    return $table->fetchAll(
      $table
        ->select()
        ->from($table, array('ix', 'score', 'type', 'data', 'user_id'))
        ->where('category = ?', $category)
        ->where('id = ?', $id)
        ->order('score DESC'))
      ->toArray();
  }

}
