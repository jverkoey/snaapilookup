<?php

/**
 * This is the DbTable class for the userid table.
 */
class Model_DbTable_UserID extends Zend_Db_Table_Abstract {

  /** Table name */
  protected $_name    = 'userid';
  protected $_primary = 'username';

}
