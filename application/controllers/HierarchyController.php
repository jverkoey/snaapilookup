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

  public function infoAction() {
    $category = trim($this->_request->getParam('c'));
    $hierarchies = trim($this->_request->getParam('h'));

    if( empty($category) || empty($hierarchies) ) {
      // Nothing to search!
      $this->_helper->json(array(
        's' => false));
    } else {
      $hierarchies = explode(',', $hierarchies);

      $info = array($category=>array());
      foreach( $hierarchies as $id ) {
        $info[$category][$id] = $this->getHierarchiesModel()->fetch($category, $id);
      }

      $this->_helper->json(array(
        's' => true,
        'i' => $info
      ));
    }

    $this->_helper->viewRenderer->setNoRender();
  }

}
