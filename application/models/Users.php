<?php

class Model_Users {
  /** Model_DbTable_Users */
  protected $_table;

  /**
   * Retrieve table object
   * 
   * @return Model_DbTable_Users
   */
  public function getTable() {
    if (null === $this->_table) {
      // since the dbTable is not a library item but an application item,
      // we must require it to use it
      require_once APPLICATION_PATH . '/models/DbTable/Users.php';
      $this->_table = new Model_DbTable_Users;
    }
    return $this->_table;
  }

  /**
   * Strips a nickname of non-alphanumeric characters.
   */
  public function cleanNickname($nickname) {
    return strtolower(ereg_replace("[^A-Za-z0-9_]", "", trim($nickname)));
  }

  /**
   * Fetch a user id by unique nickname.
   *
   * @param  string
   * @return null|int
   */
  public function fetchUserIdByNickname($nickname) {
    $clean_nickname = $this->cleanNickname($nickname);
    if( $clean_nickname == '' ) {
      return null;
    }

    $table = $this->getTable();
    $query = $table->select()
                   ->from($table, array('id'))
                   ->where('nickname = ?', $clean_nickname);
    $results = $table->fetchAll($query)
                     ->toArray();
    if( !empty($results) ) {
      return $results[0]['id'];
    }

    return null;
  }

  /**
   * Determine whether a particular nickname already exists.
   *
   * @param  string
   * @return boolean
   */
  public function nicknameExists($nickname) {
    return $this->fetchUserIdByNickname($nickname) != null;
  }

  /**
   * Determine whether a user exists.
   *
   * @param  id
   * @return boolean
   */
  public function userExists($user_id) {
    $user = $this->getTable()->find($user_id)->toArray();
    return !empty($user);
  }

  /**
   * Fetch a user profile by id.
   *
   * @param  id
   * @return array
   */
  public function fetchUserProfile($id) {
    $row = $this->getTable()->find($id)->toArray();
    return empty($row) ? array() : $row[0];
  }

  /**
   * Update a user profile by id.
   *
   * @param  id
   * @param  array
   * @return boolean
   */
  public function updateUserProfile($user_id, $profile) {
    $profile['last_profile_update'] = new Zend_Db_Expr('CURDATE()');
    $table = $this->getTable();
    return $table->update($profile, $table->getAdapter()->quoteInto('id = ?', $user_id));
  }

  /**
   * Update the last time a user logged in.
   *
   * @param  id
   * @return boolean
   */
  public function updateLoginTime($user_id) {
    $table = $this->getTable();
    return $table->update(
      array('last_active' => new Zend_Db_Expr('CURDATE()')),
      $table->getAdapter()->quoteInto('id = ?', $user_id));
  }

  /**
   * Create a new user from a profile.
   *
   * @return int  The new user id
   */
  public function createNewUserFromProfile($profile_info) {
    // Avoid hackery.
    unset($profile_info['id']);

    $profile_info['signup_date'] = new Zend_Db_Expr('CURDATE()');
    $profile_info['last_active'] = new Zend_Db_Expr('CURDATE()');

    if( isset($profile_info['nickname']) && trim($profile_info['nickname']) != '' ) {

      // Even if there is a dupe, keep their requested nickname around
      $profile_info['nickname_dirty'] = $profile_info['nickname'];

      // Avoid dupes.
      $dupe_id = $this->fetchUserIdByNickname($profile_info['nickname']);
      if( $dupe_id ) {
        unset($profile_info['nickname']);

      } else {
        // nickname_dirty is the original nickname, as specified by the user
        // nickname is the clean, indexed nickname that must be unique.
        $profile_info['nickname'] = $this->cleanNickname($profile_info['nickname']);
      }
    } else {
      unset($profile_info['nickname']);
      unset($profile_info['nickname_dirty']);
    }
    return $this->getTable()->insert($profile_info);
  }

}
