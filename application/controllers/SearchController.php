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
      $results = $this->getFunctionsModel()->search($query, $filters);
      foreach( $results as &$result ) {
        $result = array(
          'i' => $result['id'],
          'c' => $result['category'],
          'h' => $result['hierarchy'],
          'n' => $result['name']
        );
      }
      $this->_helper->json(array(
        's' => true,
        'q' => $query,
        'r' => $results));
    }

    $this->_helper->viewRenderer->setNoRender();
  }

}
