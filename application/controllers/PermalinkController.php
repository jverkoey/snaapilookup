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
    $this->framework('Zend');
  }

  public function facebookApiAction() {
    $this->framework('Facebook API');
  }

  public function djangoAction() {
    $this->framework('django');
  }

  public function firebugAction() {
    $this->framework('Firebug');
  }

  public function iphoneAction() {
    $this->framework('iPhone');
  }


  private function language($name) {
    $this->category($name, 'Language');
  }

  private function framework($name) {
    $this->category($name, 'Framework');
  }

  private function category($name, $type) {
    $function_name = $this->_request->getParam(1);
    $result = $this->getFunctionsModel()->fetchByName($function_name);
    if( $result ) {
      $this->view->headTitle($name);
      $this->view->headTitle($function_name);
      $this->view->category = $result['category'];
      $this->view->id = $result['id'];
      $this->view->hierarchy = $result['hierarchy'];
      $this->view->type = $name;
      $this->view->filter_type = $type;
      $this->view->function_name = $function_name;
      $this->getLogsModel()->add(strtolower($name).'permalink', $function_name);
    }
    $this->_forward('index', 'index');
  }

}
