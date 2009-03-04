<?php
// application/controllers/SnaapiController.php

class SnaapiController extends Zend_Controller_Action {

  protected $_categoriesModel;
  protected $_hierarchiesModel;
  protected $_functionsModel;
  protected $_frameworklanguagesModel;

  public function init() {
    $this->view->headTitle('snaapi');
    $this->view->headTitle()->setSeparator(' | ');
    $this->view->env = $this->getInvokeArg('env');
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

  protected function getFrameworkLanguagesModel() {
    if (null === $this->_frameworklanguagesModel) {
      require_once APPLICATION_PATH . '/models/FrameworkLanguages.php';
      $this->_frameworklanguagesModel = new Model_FrameworkLanguages();
    }
    return $this->_frameworklanguagesModel;
  }

}
