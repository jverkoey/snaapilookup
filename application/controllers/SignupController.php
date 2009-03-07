<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class SignupController extends SnaapiController {
  public function indexAction() {
    $this->view->signup_error = $this->_getParam('signup_error');
    $this->view->openid_url = $this->_getParam('openid_url');
    $this->view->create_username = $this->_getParam('username');
    $this->view->create_email = $this->_getParam('email');
    $auth = Zend_Auth::getInstance();
    if( $auth->hasIdentity() ) {
      $this->view->username = $auth->getIdentity();
    }
  }
}
