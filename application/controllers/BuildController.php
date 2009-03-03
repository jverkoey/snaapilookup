<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class BuildController extends SnaapiController {
  public function indexAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $languages = $this->getLanguagesModel()->fetchAll();
      $frameworks = $this->getFrameworksModel()->fetchAll();

      file_put_contents(APPLICATION_PATH . '/../www/js/static/data.js',
        Zend_Json::encode(array(
          array('type'=>'Framework', 'data'=>$frameworks),
          array('type'=>'Language', 'data'=>$languages))
        )
      );
    } else {
      $this->_forward('error', 'error');
    }
  }
}
