<?php
// application/controllers/SnaapiController.php

class SnaapiController extends Zend_Controller_Action {

  public function init() {
    $this->view->headTitle('snaapi');
    $this->view->headTitle()->setSeparator(' | ');
    $this->view->env = $this->getInvokeArg('env');
  }

}
