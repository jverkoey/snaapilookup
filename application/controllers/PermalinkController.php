<?php
// application/controllers/UserController.php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class PermalinkController extends SnaapiController {

  public function phpAction() {
    $function_name = $this->_request->getParam(1);
    $result = $this->getFunctionsModel()->fetchByName($function_name);
    if( $result ) {
      $this->view->category = $result['category'];
      $this->view->id = $result['id'];
      $this->view->hierarchy = $result['hierarchy'];
      $this->view->type = 'PHP';
      $this->view->function_name = $function_name;
    }
    $this->_forward('index', 'index');
  }

}
