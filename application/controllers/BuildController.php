<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class BuildController extends SnaapiController {

  public function indexAction() {
    global $REVISIONS;

    if( 'development' == $this->getInvokeArg('env') ) {

      // Build the categories.

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

        $REVISIONS['STATIC_JS_BUILD'] = $new_revision;

        $revisions_changed = true;
      }


      // Build the hierarchies.

      $all_hierarchies = array();
      $this->scrape_set($languages, $all_hierarchies);
      $this->scrape_set($frameworks, $all_hierarchies);


      $current_revision = $REVISIONS['STATIC_HIER_BUILD'];
      $static_hier_path = APPLICATION_PATH . '/../www/js/static/hier.js';
      $contents = file_get_contents($static_hier_path);

      $new_contents = Zend_Json::encode($all_hierarchies);
      $new_contents = preg_replace('/"([a-z])"/', '$1', $new_contents);
      $new_contents = preg_replace('/"([0-9]+)"/', '$1', $new_contents);
      $new_contents = str_replace(',d:null', '', $new_contents);
      $new_contents = str_replace('c:[],', '', $new_contents);

      if( $contents != $new_contents ) {
        $new_revision = $current_revision + 1;
        file_put_contents($static_hier_path, $new_contents);
        file_put_contents(APPLICATION_PATH . '/revisions/static_hier.php',
'<?php

$REVISIONS[\'STATIC_HIER_BUILD\'] = '.$new_revision.';');

        $REVISIONS['STATIC_HIER_BUILD'] = $new_revision;

        $revisions_changed = true;
      }


      // Build the indices.

      $all_functions = array();
      foreach( $languages as $item ) {
        $all_functions[$item['id']] = $this->getFunctionsModel()->fetchAll($item['id']);
      }
      foreach( $frameworks as $item ) {
        $all_functions[$item['id']] = $this->getFunctionsModel()->fetchAll($item['id']);
      }

      $fun_revisions_changed = false;
      foreach( $all_functions as $key=>$value ) {
        $current_revision = isset($REVISIONS['STATIC_FUN_BUILD'][$key]) ? $REVISIONS['STATIC_FUN_BUILD'][$key] : 0;
        $static_fun_path = APPLICATION_PATH . '/../www/js/static/fun/'.$key.'.js';
        $contents = @file_get_contents($static_fun_path);

        $new_contents = Zend_Json::encode($value);
        $new_contents = preg_replace('/"([0-9]+)"/', '$1', $new_contents);
        $new_contents = str_replace('"id"', 'i', $new_contents);
        $new_contents = str_replace('"name"', 'n', $new_contents);

        if( $contents != $new_contents ) {
          $new_revision = $current_revision + 1;
          file_put_contents($static_fun_path, $new_contents);

          $REVISIONS['STATIC_FUN_BUILD'][$key] = $new_revision;

          $revisions_changed = true;
          $fun_revisions_changed = true;
        }
      }
      if( $fun_revisions_changed ) {
        $output = 
'<?php

$REVISIONS[\'STATIC_FUN_BUILD\'] = array(';
        $values = array();
        foreach( $REVISIONS['STATIC_FUN_BUILD'] as $key=>$value ) {
          $values []= '"'.$key.'"=>'.$value;
        }
        $output .= implode(',', $values);
        $output .= ');';
        file_put_contents(APPLICATION_PATH . '/revisions/static_fun.php', $output);
      }

      if( $revisions_changed ) {
        $this->updateRevisionFile();
      }
    } else {
      $this->_forward('error', 'error');
    }
  }

  private function get_children($hierarchies, &$index) {
    $parent_depth = $hierarchies[$index]['depth'];
    ++$index;

    $children = array();

    for(; $index < count($hierarchies); ++$index ) {
      $delta = $hierarchies[$index]['depth'] - $parent_depth;
      if( $delta == 1 ) {
        $current_index = $index;
        $children [] = array(
          'c' => $this->get_children($hierarchies, $index),
          'd' => array(
            'n' => $hierarchies[$current_index]['name'],
            'i' => $hierarchies[$current_index]['id']
          )
        );
      } else if( $delta <= 0 ) {
        return $children;
      }
    }

    return $children;
  }

  private function scrape_set($set, &$result) {
    foreach( $set as $item ) {
      $category = $item['id'];

      $hierarchies = $this->getHierarchiesModel()->fetchAll($category);

      $index = 0;
      $result[$category] = $this->get_children($hierarchies, $index);
    }
  }

  private function updateRevisionFile() {
    global $REVISIONS;
    $this->view->old_revision = $REVISIONS['ALL'];
    $this->view->new_revision = $REVISIONS['ALL']+1;
    $REVISIONS['ALL']++;

    $output =
'/**
 * snaapi static revisions.
 * Last updated: '.date('l jS \of F Y h:i:s A').'
 * Revision: '.$REVISIONS['ALL'].'
 */
';
    $revisions = strtolower(Zend_Json::encode($REVISIONS));
    $revisions = preg_replace('/"([a-z0-9_]+)"/', '$1', $revisions);
    $output .= 'var Revisions = '.$revisions.';';
    file_put_contents(APPLICATION_PATH . '/../www/js/static/revisions.js', $output);
    
    file_put_contents(APPLICATION_PATH . '/revisions/revisions.php',
'<?php

$REVISIONS[\'ALL\'] = '.$REVISIONS['ALL'].';');
  }

}
