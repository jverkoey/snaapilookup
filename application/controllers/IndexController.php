<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class IndexController extends SnaapiController {
  public function indexAction() {
    $this->_helper->layout->setLayout('dashboard');
  }
}
