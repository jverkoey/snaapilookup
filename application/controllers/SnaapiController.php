<?php
// application/controllers/SnaapiController.php

class SnaapiController extends Zend_Controller_Action {

  protected $_openIDModel;
  protected $_userIDModel;
  protected $_usersModel;
  protected $_categoriesModel;
  protected $_hierarchiesModel;
  protected $_functionsModel;
  protected $_socialModel;
  protected $_ratingsModel;
  protected $_frameworklanguagesModel;
  protected $_logsModel;

  public function init() {
    if( !isset($this->view->title_set) ) {
      $this->view->title_set = true;
      $this->view->headTitle('snaapi: simple API search', 'SET');
    }
    $this->view->headTitle()->setSeparator(' | ');
    $this->view->env = $this->getInvokeArg('env');
  }

  protected function isLoggedIn() {
    return Zend_Auth::getInstance()->hasIdentity();
  }

  protected function _requireLoggedIn($options = array('redirect' => true)) {
    $auth = Zend_Auth::getInstance();
    if( $auth->hasIdentity() ) {
      $identity = $auth->getIdentity();
      if( $this->_getUsersModel()->userExists($identity['id']) ) {
        return $identity['id'];
      } else {
        Zend_Auth::getInstance()->clearIdentity();
      }
    }

    if( $options['redirect'] ) {
      $request = $this->getRequest();
      // Fail out.
      $this->_forward('index', 'login', null, array(
                      'redirect' => array( 'controller' => $request->getControllerName(),
                                           'action'     => $request->getActionName(),
                                           'params'     => $request->getParams() ) ));
      return null;
    }

    return null;
  }

  protected function _storeUserProfile($auth, $user_id, $profile) {
    $safe_profile = is_array($auth->getIdentity()) ? $auth->getIdentity() : array();
    if( isset($profile['nickname']) ) {
      $safe_profile['nickname'] = $profile['nickname'];
    }
    if( isset($profile['fullname']) ) {
      $safe_profile['fullname'] = $profile['fullname'];
    }
    $auth->getStorage()->write(array_merge(array('id' => $user_id), $safe_profile));
  }

  protected function _getUserProfile($user_id) {
    return $this->_getUsersModel()->fetchUserProfile($user_id);
  }

  protected function _getOpenIDModel() {
    if (null === $this->_openIDModel) {
      // autoload only handles "library" components.  Since this is an 
      // application model, we need to require it from its application 
      // path location.
      require_once APPLICATION_PATH . '/models/OpenID.php';
      $this->_openIDModel = new Model_OpenID();
    }
    return $this->_openIDModel;
  }

  protected function _getUserIDModel() {
    if (null === $this->_userIDModel) {
      require_once APPLICATION_PATH . '/models/UserID.php';
      $this->_userIDModel = new Model_userID();
    }
    return $this->_userIDModel;
  }

  protected function _getUsersModel() {
    if (null === $this->_usersModel) {
      require_once APPLICATION_PATH . '/models/Users.php';
      $this->_usersModel = new Model_Users();
    }
    return $this->_usersModel;
  }
  protected function getCategoriesModel() {
    if (null === $this->_categoriesModel) {
      require_once APPLICATION_PATH . '/models/Categories.php';
      $this->_categoriesModel = new Model_Categories();
    }
    return $this->_categoriesModel;
  }

  protected function getHierarchiesModel() {
    if (null === $this->_hierarchiesModel) {
      require_once APPLICATION_PATH . '/models/Hierarchies.php';
      $this->_hierarchiesModel = new Model_Hierarchies();
    }
    return $this->_hierarchiesModel;
  }

  protected function getFunctionsModel() {
    if (null === $this->_functionsModel) {
      require_once APPLICATION_PATH . '/models/Functions.php';
      $this->_functionsModel = new Model_Functions();
    }
    return $this->_functionsModel;
  }

  protected function getSocialModel() {
    if (null === $this->_socialModel) {
      require_once APPLICATION_PATH . '/models/Social.php';
      $this->_socialModel = new Model_Social();
    }
    return $this->_socialModel;
  }

  protected function getRatingsModel() {
    if (null === $this->_ratingsModel) {
      require_once APPLICATION_PATH . '/models/Ratings.php';
      $this->_ratingsModel = new Model_Ratings();
    }
    return $this->_ratingsModel;
  }

  protected function getFrameworkLanguagesModel() {
    if (null === $this->_frameworklanguagesModel) {
      require_once APPLICATION_PATH . '/models/FrameworkLanguages.php';
      $this->_frameworklanguagesModel = new Model_FrameworkLanguages();
    }
    return $this->_frameworklanguagesModel;
  }

  protected function getLogsModel() {
    if (null === $this->_logsModel) {
      require_once APPLICATION_PATH . '/models/Logs.php';
      $this->_logsModel = new Model_Logs();
    }
    return $this->_logsModel;
  }

}
