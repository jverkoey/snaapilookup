<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class BuildController extends SnaapiController {

  public function indexAction() {
    global $REVISIONS;

    if( 'development' == $this->getInvokeArg('env') ) {
      $languages = $this->getCategoriesModel()->fetchAllLanguages();
      $frameworks = $this->getCategoriesModel()->fetchAllFrameworks();

      $current_revision = $REVISIONS['STATIC_JS_BUILD'];
      $static_js_path = APPLICATION_PATH . '/../www/js/static/data.js';
      $contents = file_get_contents($static_js_path);
      $new_contents = Zend_Json::encode(array(
        array('t'=>'Framework', 'd'=>$frameworks),
        array('t'=>'Language', 'd'=>$languages))
      );
      $new_contents = str_replace('"id"', 'i', $new_contents);
      $new_contents = str_replace('"name"', 'n', $new_contents);
      $new_contents = preg_replace('/"([0-9]+)"/', '$1', $new_contents);

      $revisions_changed = false;
      if( $contents != $new_contents ) {
        $new_revision = $current_revision + 1;
        file_put_contents($static_js_path, $new_contents);
        file_put_contents(APPLICATION_PATH . '/revisions/static_js.php',
'<?php

$REVISIONS[\'STATIC_JS_BUILD\'] = '.$new_revision.';');

        $this->view->old_revision = $current_revision;
        $this->view->new_revision = $new_revision;
        $REVISIONS['STATIC_JS_BUILD'] = $new_revision;

        $revisions_changed = true;
      }

      foreach( $languages as $language ) {
        
      }


      if( $revisions_changed ) {
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
