<?php

/**
 * This is the DbTable class for the social table.
 */
class Model_DbTable_Social extends Zend_Db_Table_Abstract {

  /** Table name */
  protected $_name    = 'social';
  protected $_primary = array('category', 'id', 'index');

}
