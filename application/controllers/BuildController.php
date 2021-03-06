<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class BuildController extends SnaapiController {

  public function indexAction() {
    global $REVISIONS;

    if( 'development' == $this->getInvokeArg('env') ) {
      date_default_timezone_set('America/Los_Angeles');

      // Build the categories.

      $languages = $this->getCategoriesModel()->fetchAllLanguages();
      $frameworks = $this->getCategoriesModel()->fetchAllFrameworks();

      $current_revision = $REVISIONS['STATIC_JS_BUILD'];
      $static_js_path = APPLICATION_PATH . '/../www/js/static/data.js';
      $contents = @file_get_contents($static_js_path);
      $new_contents = Zend_Json::encode(array(
        array('t'=>'Framework', 'd'=>$frameworks),
        array('t'=>'Language', 'd'=>$languages))
      );
      $new_contents = str_replace('"id"', 'i', $new_contents);
      $new_contents = str_replace('"name"', 'n', $new_contents);
      $new_contents = preg_replace('/"([0-9]+)"/', '$1', $new_contents);
      
      $new_contents = 'var d=new Array('.substr($new_contents, 1, strlen($new_contents)-2).');';
      $new_contents.= 'Snap.Database.singleton.load_categories(d);';

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
      $contents = @file_get_contents($static_hier_path);

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

        $new_contents = 'var i='.$key.';';
        $json = Zend_Json::encode($value);
        $json = substr($json, 1, strlen($json) - 2);
        $new_contents.= 'var d=new Array('.$json.');';
        $new_contents.= 'Snap.Database.singleton.load_functions(i,d);';

        $new_contents = preg_replace('/"([0-9]+)"/', '$1', $new_contents);
        $new_contents = str_replace('"i"', 'i', $new_contents);
        $new_contents = str_replace('"d"', 'd', $new_contents);
        $new_contents = str_replace('"id"', 'i', $new_contents);
        $new_contents = str_replace('"name"', 'n', $new_contents);
        $new_contents = str_replace('"hierarchy"', 'h', $new_contents);

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


      // Build the static indices.

      function sort_functions($left, $right) {
        return strcasecmp($left['name'], $right['name']);
      }
      $static_indice_revisions_changed = false;
      foreach( $all_functions as $key=>$value ) {
        $current_revision = isset($REVISIONS['STATIC_INDICES_BUILD'][$key]) ? $REVISIONS['STATIC_INDICES_BUILD'][$key] : 0;
        $static_fun_path = APPLICATION_PATH . '/views/scripts/indexof/cat/'.$key.'.phtml';
        $contents = @file_get_contents($static_fun_path);

        $new_contents = '<div id="listing">'."\n";

        $category_name = $this->getCategoriesModel()->fetchName($key);
        usort($value, 'sort_functions');
        foreach( $value as $function ) {
          $new_contents .= '<div class="function"><a href="/'.$category_name.'/'.$function['name'].'">'.$function['name'].'</a></div>'."\n";
        }
        $new_contents.= '</div>'."\n";

        if( $contents != $new_contents ) {
          $new_revision = $current_revision + 1;
          file_put_contents($static_fun_path, $new_contents);

          $REVISIONS['STATIC_INDICES_BUILD'][$key] = $new_revision;

          $revisions_changed = true;
          $static_indice_revisions_changed = true;
        }
      }
      if( $static_indice_revisions_changed ) {
        $output = 
'<?php

$REVISIONS[\'STATIC_INDICES_BUILD\'] = array(';
        $values = array();
        foreach( $REVISIONS['STATIC_INDICES_BUILD'] as $key=>$value ) {
          $values []= '"'.$key.'"=>'.$value;
        }
        $output .= implode(',', $values);
        $output .= ');';
        file_put_contents(APPLICATION_PATH . '/revisions/static_indices.php', $output);
      }

      // Build the sitemap.

      $current_revision = $REVISIONS['STATIC_SITEMAP_BUILD'];

      $static_sitemap_path = APPLICATION_PATH . '/../www/sitemap/sitemap.xml';
      $contents = @file_get_contents($static_sitemap_path);
      
      $new_contents =
'<?xml version="1.0" encoding="UTF-8"?>

  <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

  <url>
    <loc>http://snaapi.com/indexof</loc>
    <lastmod>'.date('Y-m-d').'</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
  <url>
    <loc>http://snaapi.com/contact</loc>
    <lastmod>'.date('Y-m-d').'</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc>http://snaapi.com/about</loc>
    <lastmod>'.date('Y-m-d').'</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
';

      foreach( $all_functions as $key=>$value ) {
        $category_name = $this->getCategoriesModel()->fetchName($key);
        foreach( $value as $function ) {
          $new_contents .=
'<url>
  <loc>http://snaapi.com/'.$category_name.'/'.$function['name'].'</loc>
  <lastmod>'.date('Y-m-d').'</lastmod>
  <changefreq>weekly</changefreq>
  <priority>0.8</priority>
</url>
';
        }
      }

      $new_contents .= '</urlset>';
      if( $contents != $new_contents ) {
        $new_revision = $current_revision + 1;
        file_put_contents($static_sitemap_path, $new_contents);
        file_put_contents(APPLICATION_PATH . '/revisions/static_sitemap.php',
'<?php

$REVISIONS[\'STATIC_SITEMAP_BUILD\'] = '.$new_revision.';');

        $REVISIONS['STATIC_SITEMAP_BUILD'] = $new_revision;

        $revisions_changed = true;
      }

      if( $revisions_changed ) {
        $this->updateRevisionFile();
      }
    } else {
      $this->_forward('error', 'error');
    }
  }

  private function get_children($category, $hierarchies, $parent_index, &$num_at_level, $lineage = array()) {
    $parent_depth = $hierarchies[$parent_index]['depth'];
    if( $hierarchies[$parent_index]['id'] != 1 ) {
      $lineage []= $hierarchies[$parent_index]['id'];
    }

    $children = array();

    $index = $parent_index + 1;

    while( $index < count($hierarchies) ) {
      $delta = $hierarchies[$index]['depth'] - $parent_depth;
      if( $delta == 1 ) {
        $num_added = 0;
        $children []= array(
          'c' => $this->get_children($category, $hierarchies, $index, $num_added, $lineage),
          'd' => array(
            'h' => $lineage,
            'n' => $hierarchies[$index]['name'],
            'i' => $hierarchies[$index]['id'],
            'c' => count($this->getFunctionsModel()->fetchDirectDescendants($category, $hierarchies[$index]['id']))
          )
        );
        $index += $num_added + 1;
        $num_at_level+=$num_added+1;
      } else if( $delta <= 0 ) {
        return $children;
      }
    }

    return $children;
  }

  private function scrape_set($set, &$result) {
    static $count = 0;
    $count++;
    $i = 0;
    foreach( $set as $item ) {
      $category = $item['id'];

      $hierarchies = $this->getHierarchiesModel()->fetchAll($category);
      if( count($hierarchies) == 0 ) {
        echo 'Invalid category #'.$category.'<br/>';
        continue;
      }
/*
      $hierarchies = array(
        array(
          'id'    => 1,
          'depth' => 0,
          'name'  => 'root'
        ),
        array(
          'id'    => 2,
          'depth' => 1,
          'name'  => 'top'
        ),
        array(
          'id'    => 3,
          'depth' => 2,
          'name'  => 'middle'
        ),
        array(
          'id'    => 4,
          'depth' => 3,
          'name'  => 'lowest'
        ),
        array(
          'id'    => 5,
          'depth' => 1,
          'name'  => 'top'
        ),
      );*/

      $index = 0;
      $num_added = 0;
      $result[$category] = $this->get_children($category, $hierarchies, $index, $num_added);

      $i++;
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
