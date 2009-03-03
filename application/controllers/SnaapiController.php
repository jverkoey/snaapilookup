<?php
// application/controllers/SnaapiController.php

class SnaapiController extends Zend_Controller_Action {

  protected $_frameworksModel;
  protected $_languagesModel;
  protected $_frameworklanguagesModel;

  public function init() {
    $this->view->headTitle('snaapi');
    $this->view->headTitle()->setSeparator(' | ');
    $this->view->env = $this->getInvokeArg('env');
  }

  protected function getFrameworksModel() {
    if (null === $this->_frameworksModel) {
      require_once APPLICATION_PATH . '/models/Frameworks.php';
      $this->_frameworksModel = new Model_Frameworks();
    }
    return $this->_frameworksModel;
  }

  protected function getLanguagesModel() {
    if (null === $this->_languagesModel) {
      require_once APPLICATION_PATH . '/models/Languages.php';
      $this->_languagesModel = new Model_Languages();
    }
    return $this->_languagesModel;
  }

  protected function getFrameworkLanguagesModel() {
    if (null === $this->_frameworklanguagesModel) {
      require_once APPLICATION_PATH . '/models/FrameworkLanguages.php';
      $this->_frameworklanguagesModel = new Model_FrameworkLanguages();
    }
    return $this->_frameworklanguagesModel;
  }

}
