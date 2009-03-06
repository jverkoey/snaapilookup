<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class ScrapeController extends SnaapiController {

  private $_pages_scraped;
  const MAX_PAGES_TO_SCRAPE = 1000;

  public function init() {
    SnaapiController::init();

    if( 'development' == $this->getInvokeArg('env') ) {
      $this->_helper->viewRenderer->setRender('index');
    }
  }

  public function phpAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      /*$model = $this->getFunctionsModel();
      $db = $model->getTable()->getAdapter();
      $sql = "SELECT *  FROM `functions` WHERE `data` LIKE '% ,%'";
      foreach( $db->query($sql)->fetchAll() as $result ) {
        $result['data'] = str_replace(" ,", ',', $result['data']);

        $this->getFunctionsModel()->setData(array(
          'category' => $result['category'],
          'id' => $result['id'],
          'data' => $result['data']
        ));
      }*/

      $this->scrapePHPHierarchies();
      $this->scrapePHPFunctions();
    } else {
      $this->_forward('error', 'error');
    }
  }

  private function scrapePHPHierarchies() {
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

    foreach( $scrapeable as $hierarchy ) {
      $this->_pages_scraped++;

      if( $this->_pages_scraped > ScrapeController::MAX_PAGES_TO_SCRAPE ) {
        $this->view->results .= 'Hit max page count for scraping.' . "\n";
        return;
      }

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
      if( !preg_match_all('/<li><a href="([a-zA-Z0-9_\-.]+)">([a-zA-Z0-9_:.\->]+)<\/a> — ([a-zA-Z0-9 \-_,.+;\[:\]<>=\/\'\(\)"#\\\\]+)<\/li>/', $line, $matches) ) {
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
      $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
    }
  }

  private function scrapePHPFunctions() {
    $category = 'PHP';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $scrapeable = $this->getFunctionsModel()->fetchAllScrapeable($category_id);

    if( empty($scrapeable) ) {
      $this->nothing_to_scrape($category);
      return;
    }

    foreach( $scrapeable as $function ) {
      $this->_pages_scraped++;

      if( $this->_pages_scraped % 100 == 0 ) {
        sleep(1);
      }

      if( $this->_pages_scraped > ScrapeController::MAX_PAGES_TO_SCRAPE ) {
        $this->view->results .= 'Hit max page count for scraping.' . "\n";
        return;
      }

      $this->view->results .= $function['name'] . "\n";
      if( !$function['url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $function['url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents($source_url);

      if( strpos($contents, 'classsynopsis') !== FALSE ) {
        $this->view->results .= 'This is a class definition, skipping...' . "\n";
        continue;
      }

      if( strpos($contents, '<span class="simpara">') !== FALSE ) {
        $this->view->results .= 'This is not a function, skipping...' . "\n";
        continue;
      }

      $start_index = strpos($contents, '<h3 class="title">Description</h3>');
      if( $start_index === FALSE ) {
        $this->view->results .= 'We didn\'t find a Description, skipping...' . "\n";
        continue;
      }
      $start_index += strlen('<h3 class="title">Description</h3>');
      $end_index = strpos($contents, '</div>', $start_index);
      if( $end_index === FALSE ) {
        $this->view->results .= 'We couldn\'t find the end of the description, skipping...' . "\n";
        continue;
      }

      if( strpos($contents, 'This function is an alias of:') !== FALSE ) {
        $this->view->results .= 'This function appears to be an alias, skipping...' . "\n";
        $this->getFunctionsModel()->touch($category_id, $function['id']);
        continue;
      }

      $start_index = strpos($contents, '<span', $start_index);
      if( $start_index === FALSE ) {
        $this->view->results .= 'We couldn\'t find any description tags, skipping...' . "\n";
        continue;
      }

      $line = str_replace("\n", '', substr($contents, $start_index, $end_index - $start_index));
      $line = str_replace("<span class=\"type.+?\">", '<st>', $line);
      $line = str_replace("<b>", '', $line);
      $line = str_replace("</b>", '', $line);
      $line = str_replace("<span class=\"modifier\">", '<st>', $line);
      $line = str_replace("<span class=\"methodname\">", '<sm>', $line);
      $line = str_replace("<span class=\"methodparam\">", '<smp>', $line);
      $line = str_replace("<span class=\"initializer\">", '<si>', $line);
      $line = preg_replace("/<tt.+?>/", '<sp>', $line);
      $line = str_replace("</tt>", '</s>', $line);
      $line = str_replace("</span>", '</s>', $line);
      $line = str_replace("</a>", '', $line);
      $line = preg_replace("/<a.+?>/", '', $line);
      $line = preg_replace('/( ){2,}/', ' ', $line);
      //$this->view->results .= $line."\n";
      if( strlen($line) < 5 ) {
        $this->view->results .= 'Line is unreasonably small, skipping...' . "\n";
        continue;
      }

      $this->getFunctionsModel()->setData(array(
        'category' => $category_id,
        'id' => $function['id'],
        'data' => $line
      ));
    }
  }

  private function invalid_category($name) {
    $this->view->results .= 'We can\'t find the category you requested: '.$name."\n";
  }

  private function nothing_to_scrape($name) {
    $this->view->results .= 'We can\'t find anything to scrape in the '.$name.' category.' . "\n";
  }

}
