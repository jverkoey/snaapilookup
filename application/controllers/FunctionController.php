<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class FunctionController extends SnaapiController {

  public function indexAction() {
    $category = trim($this->_request->getParam('category'));
    $id = trim($this->_request->getParam('id'));

    if( empty($category) || empty($id) ) {
      // Nothing to search!
      $this->_helper->json(array(
        'succeeded' => false));
    } else {
      $data = $this->getFunctionsModel()->fetch($category, $id);
      if( $data ) {
        $this->_helper->json(array(
          'succeeded' => true,
          'category'  => $category,
          'id'        => $id,
          'data'      => $data));
      } else {
        $this->_helper->json(array(
          'succeeded' => false));
      }
    }

    $this->_helper->viewRenderer->setNoRender();
  }

  

}
