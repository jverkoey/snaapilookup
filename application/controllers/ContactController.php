<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class ContactController extends SnaapiController {

  public function indexAction() {
    $this->view->headTitle('Contact');
    $this->getLogsModel()->add('contact');
  }

}
