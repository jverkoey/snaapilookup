<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class BuildController extends SnaapiController {

  public function indexAction() {
    global $REVISIONS;

    if( 'development' == $this->getInvokeArg('env') ) {
      $languages = $this->getLanguagesModel()->fetchAll();
      $frameworks = $this->getFrameworksModel()->fetchAll();

      $current_revision = $REVISIONS['STATIC_JS_BUILD'];
      $static_js_path = APPLICATION_PATH . '/../www/js/static/data.js';
      $contents = file_get_contents($static_js_path);
      $new_contents = Zend_Json::encode(array(
        array('type'=>'Framework', 'data'=>$frameworks),
        array('type'=>'Language', 'data'=>$languages))
      );
      if( $contents != $new_contents ) {
        $new_revision = $current_revision + 1;
        file_put_contents($static_js_path, $new_contents);
        file_put_contents(APPLICATION_PATH . '/revisions/static_js.php',
'<?php

$REVISIONS[\'STATIC_JS_BUILD\'] = '.$new_revision.';');

        $this->view->old_revision = $current_revision;
        $this->view->new_revision = $new_revision;
        $REVISIONS['STATIC_JS_BUILD'] = $new_revision;

        $this->updateRevisionFile();
      }
    } else {
      $this->_forward('error', 'error');
    }
  }

  private function updateRevisionFile() {
    global $REVISIONS;

    $REVISIONS['ALL']++;

    $revisions = array();
    foreach( $REVISIONS as $key=>$value ) {
      $revisions []= strtolower($key) . ':' . $value;
    }
    $output =
'/**
 * snaapi static revisions.
 * Last updated: '.date('l jS \of F Y h:i:s A').'
 * Revision: '.$REVISIONS['ALL'].'
 */
';
    $output .= 'var Revisions = {'. implode(',', $revisions) .'};';
    file_put_contents(APPLICATION_PATH . '/../www/js/static/revisions.js', $output);
    
    file_put_contents(APPLICATION_PATH . '/revisions/revisions.php',
'<?php

$REVISIONS[\'ALL\'] = '.$REVISIONS['ALL'].';');
  }

}
