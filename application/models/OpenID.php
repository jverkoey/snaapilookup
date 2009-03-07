<?php

class Model_OpenID {
  /** Model_DbTable_OpenID */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_OpenID
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/OpenID.php';
      $this->_table = new Model_DbTable_OpenID;
    }
    return $this->_table;
  }

  /**
   * Fetch a user id from an openid
   * 
   * @param  string $openid 
   * @return null|int
   */
  public function fetchUserId($openid) {
    if( Zend_OpenId::normalizeUrl($openid) ) {
      $row = $this->getTable()->find($openid)->toArray();
      return sizeof($row) ? $row[0]['user_id'] : null;
    }

    return null;
  }

  /**
   * Fetch an array of openids from a user id
   * 
   * @param  string $openid 
   * @return null|array(string)
   */
  public function fetchOpenIDsByUser($id) {
    $table = $this->getTable();
    return $table->fetchAll($table->select()->from($table, array('openid_url', 'provider'))
                                            ->where('user_id = ?', $id))
                 ->toArray();
  }

  /**
   * Translate an openid into a provider enumeration.
   *
   * @param  string  $openid
   * @return string
   */
  public function openIDToProvider($openid) {

    if( ereg('https\:\/\/www\.google\.com\/accounts', $openid) ) {
      return 'google';
    }

    if( ereg('http\:\/\/.+\.myopenid\.com\/', $openid) ) {
      return 'myOpenID';
    }

    if( ereg('http\:\/\/.+.blogspot.com\/', $openid) ) {
      return 'blogger';
    }

    if( ereg('https\:\/\/me\.yahoo\.com\/', $openid) ) {
      return 'yahoo';
    }

    return 'unknown';
  }

  /**
   * Insert a new pairing of an openid with a user id.
   *
   * @param  string $openid
   * @param  int    $id
   * @return true if succeeded
   */
  public function attachOpenID($openid, $id) {
    if( Zend_OpenId::normalizeUrl($openid) ) {
      $provider = $this->openIDToProvider($openid);
      if( $openid)
      $data = array(
        'openid_url' => $openid,
        'provider'   => $provider,
        'user_id'    => $id
      );

      $this->getTable()->insert($data);
      return true;
    }

    return false;
  }

  /**
   * Remove a pairing of an openid and user id.
   *
   * @param  string $openid
   * @param  int    $id
   */
  public function detachOpenID($openid, $id) {
    if( Zend_OpenId::normalizeUrl($openid) ) {
      $table = $this->getTable();
      $this->getTable()->delete(array(
        $table->getAdapter()->quoteInto('openid_url = ?', $openid),
        $table->getAdapter()->quoteInto('user_id = ?', $id)));
    }
  }

  /**
   * Remove all pairings of openid and user id.
   *
   * @param  int    $id
   */
  public function detachOpenIDsByUser($id) {
    $table = $this->getTable();
    $this->getTable()->delete($table->getAdapter()->quoteInto('user_id = ?', $id));
  }

}
