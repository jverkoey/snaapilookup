<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class FunctionController extends SnaapiController {

  public function indexAction() {
    $category = trim($this->_request->getParam('category'));
    $id = trim($this->_request->getParam('id'));
    $silent = trim($this->_request->getParam('silent'));

    if( empty($category) || empty($id) ) {
      // Nothing to search!
      $this->_helper->json(array(
        'succeeded' => false));
    } else {
      $this->getLogsModel()->add(
        'function',
        'cat: '.$category.' id: '.$id.' silent: '.($silent?'true':'false')
      );
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

  public function selectAction() {
    $category = trim($this->_request->getParam('category'));
    $id = trim($this->_request->getParam('id'));

    if( empty($category) || empty($id) ) {
      // Nothing to search!
    } else {
      $this->getLogsModel()->add(
        'function',
        'cat: '.$category.' id: '.$id
      );
    }

    $this->_helper->viewRenderer->setNoRender();
  }

  public function viewframeAction() {
    $category = trim($this->_request->getParam('category'));
    $id = trim($this->_request->getParam('id'));

    if( empty($category) || empty($id) ) {
      // Nothing to search!
    } else {
      $this->getLogsModel()->add(
        'viewframe',
        'cat: '.$category.' id: '.$id
      );
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
    $url = stripslashes($this->_request->getParam('url'));

    $category_name = $this->getCategoriesModel()->fetchName($category);
    $function_name = $this->getFunctionsModel()->fetchName($category, $id);

    if( !$category_name || !$function_name ) {
      $category_name = 'index';
      $function_name = 'index';
    }

    if( !$this->getSocialModel()->normalizeURL($url) ) {
      $url = 'http://'.$url;
    }
    $url = strip_tags($url);
    $this->getSocialModel()->addURL($category, $id, $url, $user_id);
    $this->_helper->getHelper('Redirector')
                  ->setGotoSimple($function_name, $category_name);

    $this->_helper->viewRenderer->setNoRender();
  }

  public function addsnippetAction() {
    $user_id = $this->_requireLoggedIn(array('redirect' => false));
    if( !$user_id ) {
      $this->_helper->json(array('succeeded' => false));
      return;
    }

    $category = $this->_request->getParam('category');
    $id = $this->_request->getParam('id');
    $snippet = stripslashes($this->_request->getParam('snippet'));

    $category_name = $this->getCategoriesModel()->fetchName($category);
    $function_name = $this->getFunctionsModel()->fetchName($category, $id);

    if( !$category_name || !$function_name ) {
      $category_name = 'index';
      $function_name = 'index';
    }

    $snippet = str_replace('<', '&lt;', $snippet);
    $snippet = str_replace('>', '&gt;', $snippet);

    $this->getSocialModel()->addSnippet($category, $id, $snippet, $user_id);
    $this->_helper->getHelper('Redirector')
                  ->setGotoSimple($function_name, $category_name);

    $this->_helper->viewRenderer->setNoRender();
  }

}
