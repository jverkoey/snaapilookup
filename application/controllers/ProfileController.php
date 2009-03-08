<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class ProfileController extends SnaapiController {

  public function indexAction() {
    $user_id = $this->_requireLoggedIn();
    if( !$user_id ) {
      return;
    }
    
    $this->view->headTitle('Profile');

    $this->view->profile = $this->_getUserProfile($user_id);
    $this->view->openid_auths = $this->_getOpenIDModel()->fetchOpenIDsByUser($user_id);
    $this->view->fur_auths = $this->_getUserIDModel()->fetchUserIDsByUser($user_id);
    $this->view->any_auths =
      sizeof($this->view->openid_auths) > 0 ||
      sizeof($this->view->fur_auths);
  }

  public function editAction() {
    $user_id = $this->_requireLoggedIn();
    if( !$user_id ) {
      return;
    }

    $this->view->headTitle('Edit your profile');

    $this->view->profile = $this->_getUserProfile($user_id);
  }

  public function confirmAction() {
    $user_id = $this->_requireLoggedIn();
    if( !$user_id ) {
      return;
    }

    $this->view->profile = $this->_getUserProfile($user_id);
    $this->view->confirmOnly = true;
    $this->_helper->viewRenderer->setRender('edit');
  }

  public function updateAction() {
    $this->doUpdate('index');
  }

  public function confirmupdateAction() {
    $this->doUpdate('index', 'index');
  }

  protected function doUpdate($action, $controller = null) {
    $user_id = $this->_requireLoggedIn(array('redirect' => false));
    if( !$user_id ) {
      exit;
    }

    $request = $this->getRequest();

    if( $request->getParam('user_id') && $request->getParam('user_id') == $user_id ) {
      $profile = $request->getParam('profile');
      $this->_storeUserProfile(Zend_Auth::getInstance(), $user_id, $profile);
      $this->_getUsersModel()->updateUserProfile($user_id, $profile);
      $this->_helper->getHelper('Redirector')
                      ->setGotoSimple($action, $controller);
    } else {  
      $this->_forward('index', 'profile', null, array(
        'error' => 'It seems that you might have submitted a stale form.'));
    }
  }
}
