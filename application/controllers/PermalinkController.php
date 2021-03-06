<?php
// application/controllers/UserController.php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class PermalinkController extends SnaapiController {

  public function phpAction() {
    $this->language('PHP');
  }

  public function python301Action() {
    $this->language('Python 3.0.1');
  }

  public function python261Action() {
    $this->language('Python 2.6.1');
  }

  public function pythonAction() {
    $this->language('Python 2.6.1');
  }

  public function cssAction() {
    $this->language('CSS');
  }

  public function javascriptAction() {
    $this->language('Javascript');
  }

  public function zendAction() {
    $this->framework('Zend');
  }

  public function facebookAction() {
    $this->framework('Facebook');
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

  public function jqueryAction() {
    $this->framework('jQuery');
  }

  public function twitterAction() {
    $this->framework('twitter');
  }

  public function androidAction() {
    $this->framework('android');
  }

  public function mootoolsAction() {
    $this->framework('mootools');
  }

  public function clojureAction() {
    $this->language('Clojure');
  }


  private function language($name) {
    $this->category($name, 'Language');
  }

  private function framework($name) {
    $this->category($name, 'Framework');
  }

  private function category($name, $type) {
    $category = $this->getCategoriesModel()->fetchCategoryByName($name);
    $function_name = $this->_request->getParam(1);
    $this->view->headTitle($name);
    if( $function_name ) {
      $result = $this->getFunctionsModel()->fetchByName($category, $function_name);
      if( $result ) {
        $this->view->headTitle($result['name']);
        $this->view->category = $category;
        $this->view->id = $result['id'];
        $this->view->hierarchy = $result['hierarchy'];
        $this->view->type = $name;
        $this->view->filter_type = $type;
        $this->view->function_name = $result['name'];
        $this->view->function_desc = $result['short_description'];
        $this->getLogsModel()->add(strtolower($name).'permalink', $function_name);
      }
    } else {
      $this->view->category = $category;
      $this->view->type = $name;
      $this->view->filter_type = $type;
      $this->getLogsModel()->add(strtolower($name).'filter', '');
    }
    $this->_forward('index', 'index');
  }

}
