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
   * Fetch all social data.
   */
  public function addRating($category, $id, $index, $user_id, $rating) {
    $table = $this->getTable();
    $entry = array(
      'category'  => $category,
      'id'        => $id,
      'index'     => $index,
      'user_id'   => $user_id,
      'rating'    => $rating
    );
    return $this->getTable()->insert($entry);
  }

}
