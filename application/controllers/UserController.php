<?php
// application/controllers/UserController.php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class UserController extends SnaapiController {

  // TODO:
  //  ( ) - Allow this /user/exists/<username> to work.

  public function existsAction() {
    if( $this->_request->getParam('nickname') ) {
      $nickname = $this->_request->getParam('nickname');

      if( $this->_request->isXmlHttpRequest() ) {
        $exists = $this->_getUsersModel()->nicknameExists($nickname);

        $this->_helper->json(array('nickname' => $nickname, 'exists' => $exists));

      } else {
        $exists = $this->_getUsersModel()->nicknameExists($nickname);

        echo $exists ? 'true' : 'false';

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->setLayout('datatype/text');
      }
    } else {
      $this->_forward('index', 'rest');

    }
  }

}
