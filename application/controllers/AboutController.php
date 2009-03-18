<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class AboutController extends SnaapiController {

  public function indexAction() {
    $this->view->headTitle('About us');
    $this->getLogsModel()->add('about');
  }

}
