<?php

/**
 * This is the DbTable class for the users table.
 */
class Model_DbTable_Users extends Zend_Db_Table_Abstract {

  /** Table name */
  protected $_name    = 'users';
  protected $_primary = 'id';

}
