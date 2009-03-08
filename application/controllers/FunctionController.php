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
      $this->getLogsModel()->add('function', 'cat: '.$category.' id: '.$id);
      $data = $this->getFunctionsModel()->fetch($category, $id);
      if( $data ) {
        $this->_helper->json(array(
          'succeeded'   => true,
          'category'    => $category,
          'id'          => $id,
          'data'        => $data
        ));
      } else {
        $this->_helper->json(array(
          'succeeded' => false));
      }
    }

    $this->_helper->viewRenderer->setNoRender();
  }

  public function socialAction() {
    $category = trim($this->_request->getParam('category'));
    $id = trim($this->_request->getParam('id'));

    if( empty($category) || empty($id) ) {
      // Nothing to search!
      $this->_helper->json(array(
        'succeeded' => false));
    } else {
      $data = $this->getSocialModel()->fetch($category, $id);
      if( !$data ) {
        $data = array();
      }
      $this->_helper->json(array(
        'succeeded'   => true,
        'category'    => $category,
        'id'          => $id,
        'data'        => $data
      ));
    }

    $this->_helper->viewRenderer->setNoRender();
  }

  public function voteAction() {
    $user_id = $this->_requireLoggedIn(array('redirect' => false));
    if( !$user_id ) {
      $this->_helper->json(array('succeeded' => false));
      return;
    }

    $category = trim($this->_request->getParam('category'));
    $id = trim($this->_request->getParam('id'));
    $index = trim($this->_request->getParam('index'));
    $vote = intval(trim($this->_request->getParam('vote')));

    if( empty($category) || empty($id) || empty($index) || empty($vote) ||
        ($vote != -1 && $vote != 1) ) {
      // Nothing to search!
      $this->_helper->json(array(
        'succeeded' => false
      ));
    } else {
      $result = array(
        'succeeded' => true,
        'updated' => true,
        'category' => $category,
        'id' => $id
      );

      $rating = intval($this->getRatingsModel()->fetchRating($category, $id, $index, $user_id));
      if( $rating == null ) {
        $this->getRatingsModel()->addRating($category, $id, $index, $user_id, $vote);
        $this->getSocialModel()->updateVote($category, $id, $index, $vote);
        $this->getLogsModel()->add('voteup', 'cat: '.$category.' id: '.$id.' ix: '.$index.' v: '.$vote);
        $result['new'] = true;
      } else if( $rating != $vote ) {
        $this->getRatingsModel()->updateRating($category, $id, $index, $user_id, $vote);
        $this->getSocialModel()->updateVote($category, $id, $index, $vote - $rating);
        $this->getLogsModel()->add('voteup', 'cat: '.$category.' id: '.$id.' ix: '.$index.' v: '.$vote);
        $result['new'] = false;
      } else {
        $result['updated'] = false;
      }
      $this->_helper->json($result);
    }

    $this->_helper->viewRenderer->setNoRender();
  }

  public function addurlAction() {
    $user_id = $this->_requireLoggedIn(array('redirect' => false));
    if( !$user_id ) {
      $this->_helper->json(array('succeeded' => false));
      return;
    }

    $category = $this->_request->getParam('category');
    $id = $this->_request->getParam('id');
    $url = $this->_request->getParam('url');

    if( $this->getSocialModel()->normalizeURL($url) ) {
      $this->getSocialModel()->addURL($category, $id, $url, $user_id);
      $this->_helper->getHelper('Redirector')
                    ->setGotoSimple('index', 'index');
    } else {
      // Error!
      echo 'Invalid url: '.$url;
    }

    $this->_helper->viewRenderer->setNoRender();
  }

}
