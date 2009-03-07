<?php

class Model_UserID {
  /** Model_DbTable_UserID */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_UserID
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/UserID.php';
      $this->_table = new Model_DbTable_UserID;
    }
    return $this->_table;
  }

  /**
   * Strip a username of non-alphanumeric characters.
   */
  public function cleanUsername($username) {
    return strtolower(ereg_replace("[^A-Za-z0-9_]", "", trim($username)));
  }

  /**
   * Generate a random salt string.
   */
  public function generateSalt() {
    $dynamicSalt = '';
    for( $i = 0; $i < 16; $i++ ) {
      $dynamicSalt .= chr(rand(33, 126));
    }
    return $dynamicSalt;
  }

  /**
   * Encrypt a password using a salt and md5.
   */
  public function hashPassword($username, $password, $salt) {
    return md5(Zend_Registry::get('staticSalt') . $password . $salt);
  }

  /**
   * Fetch a user id from a username
   *
   * @param  string $openid 
   * @return null|int
   */
  public function fetchUserId($username) {
    $username = $this->cleanUsername($username);
    $row = $this->getTable()->find($username)->toArray();
    return sizeof($row) ? $row[0]['user_id'] : null;
  }

  /**
   * Fetch an array of usernames from a user id
   *
   * @param  int  $id 
   * @return null|array(string)
   */
  public function fetchUserIDsByUser($id) {
    $table = $this->getTable();
    return $table->fetchAll($table->select()->from($table, array('username'))
                                            ->where('user_id = ?', $id))
                 ->toArray();
  }

  /**
   * Insert a new pairing of a username with a user id.
   *
   * @param  string $username
   * @param  string $password
   * @param  int    $id
   * @return true if succeeded
   */
  public function attachUserID($username, $password, $id) {
    $username = $this->cleanUsername($username);
    $salt = $this->generateSalt();
    $hash = $this->hashPassword($username, $password, $salt);
    $data = array(
      'username' => $username,
      'password' => $hash,
      'salt'     => $salt,
      'user_id'  => $id
    );

    $this->getTable()->insert($data);
    return true;
  }

  /**
   * Remove a pairing of a username and user id.
   *
   * @param  string $username
   * @param  int    $id
   */
  public function detachUserID($username, $id) {
    $username = $this->cleanUsername($username);
    $table = $this->getTable();
    $this->getTable()->delete(array(
      $table->getAdapter()->quoteInto('username = ?', $username),
      $table->getAdapter()->quoteInto('user_id = ?', $id)));
  }

  /**
   * Remove all pairings of username and user id.
   *
   * @param  int    $id
   */
  public function detachUserIDsByUser($id) {
    $table = $this->getTable();
    $this->getTable()->delete($table->getAdapter()->quoteInto('user_id = ?', $id));
  }

}
