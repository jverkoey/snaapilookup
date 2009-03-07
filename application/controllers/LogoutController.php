<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class LogoutController extends SnaapiController {

  public function indexAction() {
    return $this->_forward('logout', 'auth');
  }

}
