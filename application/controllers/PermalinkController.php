<?php
// application/controllers/UserController.php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class PermalinkController extends SnaapiController {

  public function phpAction() {
    $this->language('PHP');
  }

  public function pythonAction() {
    $this->language('Python');
  }

  public function cssAction() {
    $this->language('CSS');
  }

  public function zendAction() {
    $this->language('Zend');
  }

  public function facebookApiAction() {
    $this->language('Facebook API');
  }

  private function language($name) {
    $function_name = $this->_request->getParam(1);
    $result = $this->getFunctionsModel()->fetchByName($function_name);
    if( $result ) {
      $this->view->headTitle($name);
      $this->view->headTitle($function_name);
      $this->view->category = $result['category'];
      $this->view->id = $result['id'];
      $this->view->hierarchy = $result['hierarchy'];
      $this->view->type = $name;
      $this->view->filter_type = 'Language';
      $this->view->function_name = $function_name;
      $this->getLogsModel()->add(strtolower($name).'permalink', $function_name);
    }
    $this->_forward('index', 'index');
  }

}
