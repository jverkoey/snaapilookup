<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class HierarchyController extends SnaapiController {

  public function indexAction() {
    $query = trim($this->_request->getParam('query'));

    if( empty($query) ) {
      // Nothing to search!
      $this->_helper->json(array(
        'succeeded' => false));
    } else {
      $pairs = explode('|', $query);

      if( count($pairs) ) {
        $ancestors = array();
        foreach( $pairs as &$pair ) {
          $pair = explode(',', $pair);
          $category = $pair[0];
          $id = $pair[1];

          if( !isset($ancestors[$category]) ) {
            $ancestors[$category] = array();
          }
          $ancestors[$category][$id] = array();
          $ancestry = $this->getHierarchiesModel()->fetchAncestry($category, $id);
          foreach( $ancestry as $link ) {
            $ancestors[$category][$id] []= $link['id'];
          }
        }

        $this->_helper->json(array(
          'succeeded' => true,
          'ancestors' => $ancestors
        ));
      } else {
        $this->_helper->json(array(
          'succeeded' => false));
      }
    }

    $this->_helper->viewRenderer->setNoRender();
  }

}
