<?php

/**
 * This is the DbTable class for the openid table.
 */
class Model_DbTable_OpenID extends Zend_Db_Table_Abstract {

  /** Table name */
  protected $_name    = 'openid';
  protected $_primary = 'openid_url';

}
