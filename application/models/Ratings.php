<?php

class Model_Ratings {
  /** Model_DbTable_Ratings */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_Ratings
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/Ratings.php';
      $this->_table = new Model_DbTable_Ratings;
    }
    return $this->_table;
  }

  /**
   * Fetch a rating.
   */
  public function fetchRating($category, $id, $index, $user_id) {
    $table = $this->getTable();
    $result = $table->fetchAll(
      $table
        ->select()
        ->from($table, array('rating'))
        ->where('category = ?', $category)
        ->where('id = ?', $id)
        ->where('ix = ?', $index)
        ->where('user_id = ?', $user_id)
      )->toArray();
    return empty($result) ? null : $result[0]['rating'];
  }

  /**
   * Add a rating.
   */
  public function addRating($category, $id, $index, $user_id, $rating) {
    $table = $this->getTable();
    $entry = array(
      'category'  => $category,
      'id'        => $id,
      'ix'        => $index,
      'user_id'   => $user_id,
      'rating'    => $rating
    );
    return $this->getTable()->insert($entry);
  }

  /**
   * Update a rating.
   */
  public function updateRating($category, $id, $index, $user_id, $rating) {
    $table = $this->getTable();
    $rating = array('rating' => $rating);
    return $table->update(
      $rating,
      array(
        $table->getAdapter()->quoteInto('category = ?', $category),
        $table->getAdapter()->quoteInto('id = ?', $id),
        $table->getAdapter()->quoteInto('ix = ?', $index),
        $table->getAdapter()->quoteInto('user_id = ?', $user_id),
      ));
  }

}
