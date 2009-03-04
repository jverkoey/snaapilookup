<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class ScrapeController extends SnaapiController {

  public function init() {
    SnaapiController::init();

    if( 'development' == $this->getInvokeArg('env') ) {
      $this->_helper->viewRenderer->setRender('index');
    }
  }

  public function phpAction() {
    if( 'development' == $this->getInvokeArg('env') ) {

      $category = 'PHP';

      $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

      if( !$category_id ) {
        $this->invalid_category($category);
        return;
      }

      $scrapeable = $this->getHierarchiesModel()->fetchAllScrapeable($category_id);

      if( empty($scrapeable) ) {
        $this->nothing_to_scrape($category);
        return;
      }

      $this->view->results = '';

      foreach( $scrapeable as $hierarchy ) {
        $this->view->results .= $hierarchy['name'] . "\n";
        if( !$hierarchy['source_url'] ) {
          $this->view->results .= 'No source URL specified, skipping...' . "\n";
          continue;
        }
        $source_url = $hierarchy['source_url'];
        $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

        $contents = file_get_contents($source_url);
        
        $start_index = strpos($contents, '<h2>Table of Contents</h2>');
        if( $start_index === FALSE ) {
          $this->view->results .= 'We didn\'t find a Table of Contents, skipping...' . "\n";
          continue;
        }
        $end_index = strpos($contents, '</ul>', $start_index);
        if( $end_index === FALSE ) {
          $this->view->results .= 'We couldn\'t find the end of the list, skipping...' . "\n";
          continue;
        }

        $line = str_replace("\n", '', substr($contents, $start_index, $end_index - $start_index));
        if( !preg_match_all('/<li><a href="([a-zA-Z0-9_\-.]+)">([a-zA-Z0-9_.]+)<\/a> — ([a-zA-Z0-9 \-_,.\/\'\(\)]+)<\/li>/', $line, $matches) ) {
          $this->view->results .= 'We coulnd\'t find any functions in this list, skipping...' . "\n";
          $this->view->results .= $line . "\n";
          $this->view->results .= $start_index.'-'.$end_index . "\n";
          continue;
        }

        $list_item_count = preg_match_all('/<li>/', $line, $nothing);
        if( $list_item_count != count($matches[1]) ) {
          $this->view->results .= 'We missed some items ('.($list_item_count - count($matches[1])).') in the list, skipping...' . "\n";
          $this->view->results .= print_r($matches[2], TRUE);
          continue;
        }

        $dirname = dirname($source_url).'/';
        for( $index = 0; $index < count($matches[1]); ++$index ) {
          $name = $matches[2][$index];
          $url = $dirname . $matches[1][$index];
          $description = $matches[3][$index];
          //$this->view->results .= $name.' - '.$description."\n";
          //$this->view->results .= '  <a href="'.$url.'">'.$url."</a>\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $name,
            'url' => $url,
            'short_description' => $description
          ));
        }
      }
    } else {
      $this->_forward('error', 'error');
    }
  }

  private function invalid_category($name) {
    $this->view->results = 'We can\'t find the category you requested: '.$name;
  }

  private function nothing_to_scrape($name) {
    $this->view->results = 'We can\'t find anything to scrape in this category: '.$name;
  }

}
