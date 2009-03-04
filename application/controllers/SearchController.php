<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class SearchController extends SnaapiController {

  public function indexAction() {
    $query = trim($this->_request->getParam('query'));
    $filters = trim($this->_request->getParam('filters'));
    if( empty($filters) ) {
      $filters = array();
    } else {
      $filters = explode(',', $filters);
    }

    if( $query == '' ) {
      // Nothing to search!
      $this->_helper->json(array(
        'succeeded' => false));
    } else {
      $this->_helper->json(array(
        'succeeded' => true,
        'query' => $query,
        'results' => $this->getFunctionsModel()->search($query, $filters)));
    }

    $this->_helper->viewRenderer->setNoRender();
  }

}
