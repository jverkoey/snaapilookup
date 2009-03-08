<?php

/**
 * This is the DbTable class for the ratings table.
 */
class Model_DbTable_Ratings extends Zend_Db_Table_Abstract {

  /** Table name */
  protected $_name    = 'ratings';
  protected $_primary = array('category', 'id', 'ix', 'user_id');

}
