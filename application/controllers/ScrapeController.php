<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class ScrapeController extends SnaapiController {

  private $_pages_scraped;
  const MAX_PAGES_TO_SCRAPE = 1;

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

  public function pythonAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      $this->scrapePythonModules(true);
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function cssAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      $this->scrapeCSSFunctions();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function zendAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      $this->scrapeZend();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function fbAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      //$this->scrapeFacebook();
      //$this->scrapeFacebookFbml();
      $this->scrapeFacebookFbmlPhase2();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function djangoAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      //$this->scrapeDjango1();
      $this->scrapeDjango2();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function iphoneAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      $this->scrapeiPhone();
      //$this->scrapeiPhoneDir();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function jsAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      $this->scrapeJavascript();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function jqueryAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      //$this->scrapejQuery();
      $this->scrapejQuery2();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function androidAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;
      
      //$this->scrapeAndroidPackageList();
      //$this->scrapeAndroidPackages(2);
      $this->scrapeAndroidFunctions();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function mootoolsAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      $this->scrapeMootoolsFunctions();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function clojureAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      //$this->scrapeClojureHierarchies();
      $this->scrapeClojureFunctions();
    } else {
      $this->_forward('error', 'error');
    }
  }

  private function scrapeClojureFunctions() {
    $category = 'Clojure';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $contents = file_get_contents(APPLICATION_PATH . '/scraper/clojure/api.html');

    $hierarchies = array_slice(explode('<h2 id="', $contents), 1);
    foreach( $hierarchies as $hierarchy ) {
      if( !preg_match('/(.+?)">(.+?)<\/h2>/', $hierarchy, $matches) ) {
        $this->view->results .= 'No name found, skipping...' . "\n";
        continue;
      }

      $name = trim($matches[2]);
      $sub_id = $this->getHierarchiesModel()->fetchByName($category_id, 1, $name);

      $functions = array_slice(explode('<hr>', $hierarchy), 1);

      foreach( $functions as $function ) {
        if( !preg_match_all('/<h3 id="(.+?)">(.+?)<\/h3>/', $function, $matches) ) {
          $this->view->results .= 'No function info found, skipping...' . "\n";
          $this->view->results .= $function . "\n\n";
          continue;
        }

        if( !preg_match('/(?:.+<\/h3>) (.+?)<br>/', str_replace("\n", ' ', $function), $desc_matches) ) {
          $this->view->results .= 'No desc found, skipping...' . "\n";
          $this->view->results .= $function . "\n\n";
          continue;
        }
        
        $desc = trim(strip_tags($desc_matches[1]));
        
        for( $index = 0; $index < count($matches[0]); ++$index ) {
          $url = 'http://clojure.org/api#'.$matches[1][$index];
          $name = trim(str_replace('&amp;', '&', strip_tags($matches[2][$index])));
          $this->view->results .= $sub_id ."\n";
          $this->view->results .= $name ."\n";
          $this->view->results .= $url ."\n";
          $this->view->results .= $desc ."\n\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $sub_id,
            'name' => $name,
            'url' => $url,
            'short_description' => $desc,
            'scrapeable' => 0
          ));
        }
      }
    }
  }

  private function scrapeClojureHierarchies() {
    $category = 'Clojure';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $contents = file_get_contents(APPLICATION_PATH . '/scraper/clojure/api.html');

    $hierarchies = array_slice(explode('<h2 id="', $contents), 1);
    foreach( $hierarchies as $hierarchy ) {
      if( !preg_match('/(.+?)">(.+?)<\/h2>/', $hierarchy, $matches) ) {
        $this->view->results .= 'No name found, skipping...' . "\n";
        continue;
      }

      $name = trim($matches[2]);
      $url = 'http://clojure.org/api#'.$matches[1];
      $this->view->results .= $this->getHierarchiesModel()->insert($category_id, 1, $name, $url, 0)."\n";
    }
  }

  private function scrapeMootoolsFunctions() {
    $category = 'mootools';

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
      $this->view->results .= $hierarchy['name'] . "\n";
      if( !$hierarchy['source_url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $hierarchy['source_url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents($source_url);

      $start_index = strpos($contents, '<h2 id="');
      $data = substr($contents, $start_index);

      if( !preg_match_all('/<h2 id=".+?"(?: class="description")?><a href="(.+?)">(?:(?:.+? )?(?:Function|Method|Property|Selector|Event)): (.+?)<\/a><\/h2>/', $data, $matches ) ) {
        $this->view->results .= 'No functions found, checking for features...' . "\n";

        if( !preg_match_all('/<li>(.+?) - \(<em>(.+?)<\/em>\) (.+?)<\/li>/', $contents, $matches) ) {
          $this->view->results .= 'No features found, skipping...' . "\n";
          continue;
        }

        for( $index = 0; $index < count($matches[0]); ++$index ) {
          $url = $source_url;
          $name = trim($matches[1][$index]);
          $desc = trim(strip_tags($matches[3][$index]));
          $this->view->results .= $name."\n";
          $this->view->results .= $url."\n";
          $this->view->results .= $desc."\n\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $name,
            'url' => $url,
            'short_description' => $desc,
            'scrapeable' => 0
          ));
        }
        $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
        continue;
      }

      $functions = array_slice(explode('<h2 id="', $data), 1);

      foreach( $functions as $function ) {
        $desc = '';
        if( preg_match('/<p class="description">(.+?)<\/p>/', str_replace("\n", ' ', $function), $matches) ) {
          $desc = $matches[1];
        }

        if( !preg_match('/.+?"(?: class="description")?><a href="(.+?)">(?:(?:.+? )?(?:Function|Method|Property|Selector|Event)): (.+?)<\/a><\/h2>/', $function, $matches ) ) {
          $this->view->results .= 'Couldn\'t find the function name, skipping...' . "\n";
          continue;
        }

        $url = $source_url . $matches[1];
        $name = trim($matches[2]);
        if( $hierarchy['name'] != 'Core' && $name[0] != '$' ) {
          $name = $hierarchy['name'].'.'.$name;
        }
        $desc = trim(strip_tags($desc));
        $this->view->results .= $name."\n";
        $this->view->results .= $url."\n";
        $this->view->results .= $desc."\n\n";

        $this->getFunctionsModel()->insertOrUpdateFunction(array(
          'category' => $category_id,
          'hierarchy' => $hierarchy['id'],
          'name' => $name,
          'url' => $url,
          'short_description' => $desc,
          'scrapeable' => 0
        ));
      }
      $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
    }
  }

  private function scrapeAndroidFunctions() {
    $category = 'android';

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
      $this->view->results .= $hierarchy['name'] . "\n";
      if( !$hierarchy['source_url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $hierarchy['source_url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents($source_url);

      if( !preg_match('/<td colspan="1" class="jd-inheritance-class-cell">(.+?)<\/td>/', $contents, $matches) ) {
        $this->view->results .= 'No name found, skipping...' . "\n";
        break;
      }

      $name = $matches[1];
      $this->view->results .= $name ."\n";

      $desc = '';

      $OVERVIEW_TXT = '<h2>Class Overview</h2>';
      $desc_start = strpos($contents, $OVERVIEW_TXT);
      if( false !== $desc_start ) {
        $desc_start += strlen($OVERVIEW_TXT);
        $desc_end = strpos($contents, '</p>', $desc_start);
        if( false !== $desc_end ) {
          $desc = trim(strip_tags(str_replace("\n", ' ', substr($contents, $desc_start, $desc_end - $desc_start))));
        }
      }
      if( $desc == '' ) {
        $this->view->results .= 'No description found...'."\n";
      } else {
        $this->view->results .= $desc ."\n";
      }

      $this->getFunctionsModel()->insertOrUpdateFunction(array(
        'category' => $category_id,
        'hierarchy' => $hierarchy['id'],
        'name' => $name,
        'url' => $source_url,
        'short_description' => $desc,
        'scrapeable' => 1
      ));
      $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
    }
  }

  private function process_section($links, $name, $mode, $category_id, $hierarchy, $source_url) {
    $interface_start = strpos($links, '<li><h2>'.$name.'</h2>');
    if( $interface_start !== false ) {
      if( $mode == 2 ) {
        $interface_end = strpos($links, '    </li>', $interface_start);
        $data = substr($links, $interface_start, $interface_end - $interface_start);

        $sub_id = $this->getHierarchiesModel()->fetchByName($category_id, $hierarchy, $name);

        if( !$sub_id ) {
          $this->view->results .= $hierarchy."\n";
          $this->view->results .= $name."\n";
          $this->view->results .= 'Couldn\'t find any parent hierarchy, skipping...' . "\n";
          return false;
        }
        if( !preg_match_all('/<li><a href="(.+?)">(.+?)<\/a>(?:&lt;T&gt;)?<\/li>/', $data, $matches) ) {
          $this->view->results .= 'Couldn\'t find any members name, skipping...' . "\n";
          return false;
        }

        for( $index = 0; $index < count($matches[0]); ++$index ) {
          $name = $matches[2][$index];
          $url = 'http://developer.android.com'.$matches[1][$index];
          $this->view->results .= $this->getHierarchiesModel()->insert($category_id, $sub_id, $name, $url, 1)."\n";
        }
      } else if( $mode == 1 ) {
        $this->view->results .= $this->getHierarchiesModel()->insert($category_id, $hierarchy, $name, $source_url, 0)."\n";
      }
    }

    return true;
  }

  private function scrapeAndroidPackages($mode) {
    $category = 'android';

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
      if( !$hierarchy['source_url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $hierarchy['source_url'];

      $contents = file_get_contents($source_url);
      
      $start_index = strpos($contents, '</div> <!-- end resize-packages -->');
      $links = substr($contents, $start_index);

      $succeeded = true;
      $succeeded = $succeeded && $this->process_section($links, 'Interfaces', $mode, $category_id, $hierarchy['id'], $source_url);
      $succeeded = $succeeded && $this->process_section($links, 'Classes', $mode, $category_id, $hierarchy['id'], $source_url);
      $succeeded = $succeeded && $this->process_section($links, 'Exceptions', $mode, $category_id, $hierarchy['id'], $source_url);
      $succeeded = $succeeded && $this->process_section($links, 'Enums', $mode, $category_id, $hierarchy['id'], $source_url);
      if( $mode == 2 && $succeeded) {
        $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
      }
    }
  }

  private function scrapeAndroidPackageList() {
    $category = 'android';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $contents = file_get_contents('http://developer.android.com/reference/packages.html');

    $start_index = strpos($contents, '<div id="packages-nav">');
    if( $start_index === false ) {
      $this->view->results .= 'Couldn\'t find the packages navigation, skipping...' . "\n";
      return;
    }

    $links = substr($contents, $start_index);

    if( !preg_match_all('/<a href="(.+?)">(.+?)<\/a><\/li>/', $links, $matches) ) {
      $this->view->results .= 'Couldn\'t find any links, skipping...' . "\n";
      return;
    }

    for( $index = 0; $index < count($matches[0]); ++$index ) {
      $name = $matches[2][$index];
      $url = 'http://developer.android.com'.$matches[1][$index];
      $this->view->results .= $this->getHierarchiesModel()->insert($category_id, 1, $name, $url, 1)."\n";
    }
  }

  private function scrapejQuery2() {
    $category = 'jQuery';

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

    $is_saving = true;

    foreach( $scrapeable as $hierarchy ) {
      $this->view->results .= $hierarchy['name'] . "\n";
      if( !$hierarchy['source_url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $hierarchy['source_url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents($source_url);

      $start_index = strpos($contents, '<div id="options">');
      if( $start_index === false ) {
        $this->view->results .= 'Couldn\'t find the options, skipping...' . "\n";
        continue;
      }

      $end_index = strpos($contents, '<div id="', $start_index+1);
      if( $end_index === false ) {
        $this->view->results .= 'Couldn\'t find the end of the options, skipping...' . "\n";
        continue;
      }

      $source_name = strtolower($hierarchy['name']);

      $data = str_replace("\n", '', substr($contents, $start_index, $end_index - $start_index));

      $elements = explode('<li class="option"', $data);
      foreach( $elements as $element ) {
        if( preg_match('/<h3 class="option-name"><a href="(.+?)">(.+?)<\/a><\/h3>.+?<p>(.+?)<\/p>/', $element, $matches) ) {
          $link = $source_url.$matches[1];
          $name = $source_name .' '.trim(str_replace(' )', ')', str_replace('&nbsp;', '', strip_tags($matches[2]))));
          $desc = trim(strip_tags($matches[3], '<b>'));
          
          $this->view->results .= $link.' - '.$name."\n";
          $this->view->results .= $desc."\n\n";

          if( $is_saving ) {
            $this->getFunctionsModel()->insertOrUpdateFunction(array(
              'category' => $category_id,
              'hierarchy' => $hierarchy['id'],
              'name' => $name,
              'url' => $link,
              'short_description' => $desc
            ));
            $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
          }
        } else {
          //$this->view->results .= htmlentities($element)."\n\n";
        }
      }

      $start_index = strpos($contents, '<div id="events">');
      if( $start_index === false ) {
        $this->view->results .= 'Couldn\'t find the events, skipping...' . "\n";
        continue;
      }

      $end_index = strpos($contents, '<div id="', $start_index+1);
      if( $end_index === false ) {
        $this->view->results .= 'Couldn\'t find the end of the events, skipping...' . "\n";
        continue;
      }

      $source_name = strtolower($hierarchy['name']);

      $data = str_replace("\n", '', substr($contents, $start_index, $end_index - $start_index));

      $elements = explode('<li class="event"', $data);
      foreach( $elements as $element ) {
        if( preg_match('/<h3 class="event-name"><a href="(.+?)">(.+?)<\/a><\/h3>.+?<p>(.+?)<\/p>/', $element, $matches) ) {
          $link = $source_url.$matches[1];
          $name = $source_name .' '.trim(str_replace(' )', ')', str_replace('&nbsp;', '', strip_tags($matches[2]))));
          $desc = trim(strip_tags($matches[3], '<b>'));

          $this->view->results .= $link.' - '.$name."\n";
          $this->view->results .= $desc."\n\n";

          if( $is_saving ) {
            $this->getFunctionsModel()->insertOrUpdateFunction(array(
              'category' => $category_id,
              'hierarchy' => $hierarchy['id'],
              'name' => $name,
              'url' => $link,
              'short_description' => $desc
            ));
            $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
          }
        } else {
          //$this->view->results .= htmlentities($element)."\n\n";
        }
      }

      $start_index = strpos($contents, '<div id="methods">');
      if( $start_index === false ) {
        $this->view->results .= 'Couldn\'t find the methods, skipping...' . "\n";
        continue;
      }

      $end_index = strpos($contents, '<div id="', $start_index+1);
      if( $end_index === false ) {
        $this->view->results .= 'Couldn\'t find the end of the methods, skipping...' . "\n";
        continue;
      }

      $source_name = strtolower($hierarchy['name']);

      $data = str_replace("\n", '', substr($contents, $start_index, $end_index - $start_index));

      $elements = explode('<li class="method"', $data);
      foreach( $elements as $element ) {
        if( preg_match('/<h3 class="method-name"><a href="(.+?)">(.+?)<\/a><\/h3>.+?<p>(.+?)<\/p>/', $element, $matches) ) {
          $link = $source_url.$matches[1];
          $name = $source_name .'(\''.trim(str_replace(' )', ')', str_replace('&nbsp;', '', strip_tags($matches[2])))).'\')';
          $desc = trim(strip_tags($matches[3], '<b>'));

          $this->view->results .= $link.' - '.$name."\n";
          $this->view->results .= $desc."\n\n";

          if( $is_saving ) {
            $this->getFunctionsModel()->insertOrUpdateFunction(array(
              'category' => $category_id,
              'hierarchy' => $hierarchy['id'],
              'name' => $name,
              'url' => $link,
              'short_description' => $desc
            ));
            $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
          }
        } else {
          //$this->view->results .= htmlentities($element)."\n\n";
        }
      }
    }
  }

  private function scrapejQuery() {
    $category = 'jQuery';

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
      $this->view->results .= $hierarchy['name'] . "\n";
      if( !$hierarchy['source_url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $hierarchy['source_url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents($source_url);

      $start_index = strpos($contents, '<div class="options list">');
      if( $start_index === false ) {
        $this->view->results .= 'Couldn\'t find the options list, skipping...' . "\n";
        continue;
      }

      $end_index = strpos($contents, '<div class="printfooter">', $start_index);
      if( $end_index === false ) {
        $this->view->results .= 'Couldn\'t find the end of the options list, skipping...' . "\n";
        continue;
      }

      $data = substr($contents, $start_index, $end_index - $start_index);

      $elements = explode('tr class="option"', $data);
      foreach( $elements as $element ) {
        if( preg_match('/<a href="(.+?)" title=".+?">(.+?)<\/a><\/b>.+?<td colspan="2" class="desc">(.+?)<\/td>/', $element, $matches) ) {
          $link = 'http://docs.jquery.com'.$matches[1];
          $name = trim(str_replace(' )', ')', str_replace('&nbsp;', '', strip_tags($matches[2]))));
          $desc = trim(strip_tags($matches[3], '<b>'));
          
          $this->view->results .= $link.' - '.$name."\n";
          $this->view->results .= $desc."\n\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $name,
            'url' => $link,
            'short_description' => $desc
          ));
          $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
        } else {
          //$this->view->results .= htmlentities($element)."\n\n";
        }
      }
    }
  }

  private function scrapeJavascript() {
    $category = 'Javascript';

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
      $this->view->results .= $hierarchy['name'] . "\n";
      if( !$hierarchy['source_url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $hierarchy['source_url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents(APPLICATION_PATH . '/scraper/js/'.$hierarchy['id'].'.html');

      if( preg_match("/<h2>The (.+?) Object<\/h2>\n<p>(.+)?<\/p>/", $contents, $matches) ) {
        $object_name = $matches[1];
        $description = $matches[2];
        $this->view->results .= $object_name."\n";
        $this->view->results .= $description."\n";

        $this->getFunctionsModel()->insertOrUpdateFunction(array(
          'category' => $category_id,
          'hierarchy' => $hierarchy['id'],
          'name' => $object_name,
          'url' => $source_url,
          'short_description' => $description
        ));
      } else {
        $this->view->results .= 'We couldn\'t find the description...' . "\n";
      }
      
      $is_dom = strpos($contents, 'HTML DOM <span class="color_h1">') !== false;
      if( $is_dom ) {
        $object_name = strtolower(str_replace(' ', '', $hierarchy['name']));
      }

      $properties_index = strpos($contents, 'Object Collections</h');
      $end_index = strpos($contents, '</table>', $properties_index);
      if( $properties_index !== FALSE && $end_index !== FALSE ) {
        $properties = array_slice(
          explode(
            '<tr>',
            substr($contents, $properties_index, $end_index - $properties_index)
          ),
          2
        );

        foreach( $properties as $property ) {
          $elements = explode('<td', $property);
          foreach( $elements as &$element ) {
            $element = trim(
              str_replace(
                '&nbsp;',
                '',
                preg_replace(
                  '/^.+?>/',
                  '',
                  str_replace(
                    "\n",
                    '',
                    strip_tags(
                      $element,
                      '<a>'
                    )
                  )
                )
              )
            );
          }
          if( count($elements) <= 1 ) {
            $this->view->results .= 'Invalid element list.'."\n";
            $this->view->results .= print_r($property, true);
            break;
          }
          $link = $elements[1];
          $desc = $elements[2];
          $ff = $elements[3];
          if( count($elements) >= 6 ) {
            $ns = $elements[4];
            $ie = $elements[5];
          } else {
            $ie = $elements[4];
          }

          $name = '';
          if( $link ) {
            if( preg_match('/<a href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
              $link = $matches[1];
              $name = $matches[2];
            } else {
              $name = $link;
              $link = '';
            }
          }
          $name = str_replace('[]', '', $name);
          $this->view->results .= $object_name.'.'.$name ." - ";
          $this->view->results .= $link ." - ".$is_dom.' - ';
          $this->view->results .= $desc ."\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $object_name.'.'.$name,
            'url' => $link,
            'short_description' => $desc
          ));
        }
      }


      $properties_index = strpos($contents, 'Object Properties</h');
      $end_index = strpos($contents, '</table>', $properties_index);
      if( $properties_index !== FALSE && $end_index !== FALSE ) {
        $properties = array_slice(
          explode(
            '<tr>',
            substr($contents, $properties_index, $end_index - $properties_index)
          ),
          2
        );

        foreach( $properties as $property ) {
          $elements = explode('<td', $property);
          foreach( $elements as &$element ) {
            $element = trim(
              str_replace(
                '&nbsp;',
                '',
                preg_replace(
                  '/^.+?>/',
                  '',
                  str_replace(
                    "\n",
                    '',
                    strip_tags(
                      $element,
                      '<a>'
                    )
                  )
                )
              )
            );
          }
          if( count($elements) <= 1 ) {
            $this->view->results .= 'Invalid element list.'."\n";
            $this->view->results .= print_r($property, true);
            break;
          }
          $link = $elements[1];
          $desc = $elements[2];
          $ff = $elements[3];
          if( count($elements) >= 6 ) {
            $ns = $elements[4];
            $ie = $elements[5];
          } else {
            $ie = $elements[4];
          }

          $name = '';
          if( $link ) {
            if( preg_match('/<a href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
              $link = $matches[1];
              $name = $matches[2];
            } else {
              $name = $link;
              $link = '';
            }
          }
          $this->view->results .= $object_name.'.'.$name ." - ";
          $this->view->results .= $link ." - ".$is_dom.' - ';
          $this->view->results .= $desc ."\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $object_name.'.'.$name,
            'url' => $link,
            'short_description' => $desc
          ));
        }
      }

      
      $methods_index = strpos($contents, 'Object Methods</h');
      $end_index = strpos($contents, '</table>', $methods_index);
      
      if( $methods_index !== FALSE && $end_index !== FALSE ) {
        $methods = array_slice(
          explode(
            '<tr>',
            substr($contents, $methods_index, $end_index - $methods_index)
          ),
          2
        );

        foreach( $methods as $method ) {
          $elements = explode('<td valign="top">', $method);
          foreach( $elements as &$element ) {
            $element = trim(str_replace('&nbsp;', '', str_replace("\n", '', strip_tags($element, '<a>'))));
          }
          $link = $elements[1];
          $desc = $elements[2];
          $ff = $elements[3];
          if( count($elements) >= 6 ) {
            $ns = $elements[4];
            $ie = $elements[5];
          } else {
            $ie = $elements[4];
          }

          $name = '';
          if( $link ) {
            if( preg_match('/<a(?: target="_top")? href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
              $link = $matches[1];
              $name = $matches[2];
            } else {
              $name = $link;
              $link = '';
            }
          }
          $name = preg_replace('/(\(.*?\))/', '', $name);
          $this->view->results .= $object_name.'.'.$name ." - ";
          $this->view->results .= $link ." - ";
          $this->view->results .= $desc ."\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $object_name.'.'.$name,
            'url' => $link,
            'short_description' => $desc,
            'scrapeable' => 1
          ));
        }
        continue;
      }

      $start_index = strpos($contents, 'Top-level Functions</h2>');
      $end_index = strpos($contents, '</table>', $start_index);
      $start_prop_index = strpos($contents, 'Top-level Properties</h2>');
      $end_prop_index = strpos($contents, '</table>', $start_prop_index);
      if( $start_index !== false && $end_index !== false &&
          $start_prop_index !== false && $end_prop_index !== false ) {
        $functions = array_slice(
          explode(
            '<tr>',
            substr($contents, $start_index, $end_index - $start_index)
          ),
          2
        );

        foreach( $functions as $function ) {
          $elements = explode('<td valign="top">', $function);
          foreach( $elements as &$element ) {
            $element = trim(str_replace('&nbsp;', '', str_replace("\n", '', strip_tags($element, '<a>'))));
          }
          $link = $elements[1];
          $desc = $elements[2];
          $ff = $elements[3];
          if( count($elements) >= 6 ) {
            $ns = $elements[4];
            $ie = $elements[5];
          } else {
            $ie = $elements[4];
          }

          $name = '';
          if( $link ) {
            if( preg_match('/<a(?: target="_top")? href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
              $link = $matches[1];
              $name = $matches[2];
            } else {
              $name = $link;
              $link = '';
            }
          }
          $name = preg_replace('/(\(.*?\))/', '', $name);
          $this->view->results .= $name ." - ";
          $this->view->results .= $link ." - ";
          $this->view->results .= $desc ."\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $name,
            'url' => $link,
            'short_description' => $desc,
            'scrapeable' => 1
          ));
        }

        $properties = array_slice(
          explode(
            '<tr>',
            substr($contents, $start_prop_index, $end_prop_index - $start_prop_index)
          ),
          2
        );

        foreach( $properties as $property ) {
          $elements = explode('<td valign="top">', $property);
          foreach( $elements as &$element ) {
            $element = trim(str_replace('&nbsp;', '', str_replace("\n", '', strip_tags($element, '<a>'))));
          }
          $link = $elements[1];
          $desc = $elements[2];
          $ff = $elements[3];
          if( count($elements) >= 6 ) {
            $ns = $elements[4];
            $ie = $elements[5];
          } else {
            $ie = $elements[4];
          }

          $name = '';
          if( $link ) {
            if( preg_match('/<a(?: target="_top")? href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
              $link = $matches[1];
              $name = $matches[2];
            } else {
              $name = $link;
              $link = '';
            }
          }
          $name = preg_replace('/(\(.*?\))/', '', $name);
          $this->view->results .= $name ." - ";
          $this->view->results .= $link ." - ";
          $this->view->results .= $desc ."\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $name,
            'url' => $link,
            'short_description' => $desc,
            'scrapeable' => 1
          ));
        }
        continue;
      }

      $start_index = strpos($contents, '<h2>Event Handlers</h2>');
      $end_index = strpos($contents, '</table>', $start_index);
      if( $start_index !== false && $end_index !== false ) {
        $events = array_slice(
          explode(
            '<tr>',
            substr($contents, $start_index, $end_index - $start_index)
          ),
          2
        );

        foreach( $events as $event ) {
          $elements = explode('<td valign="top">', $event);
          foreach( $elements as &$element ) {
            $element = trim(str_replace('&nbsp;', '', str_replace("\n", '', strip_tags($element, '<a>'))));
          }
          $link = $elements[1];
          $desc = $elements[2];
          $ff = $elements[3];
          if( count($elements) >= 6 ) {
            $ns = $elements[4];
            $ie = $elements[5];
          } else {
            $ie = $elements[4];
          }

          $name = '';
          if( $link ) {
            if( preg_match('/<a(?: target="_top")? href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
              $link = $matches[1];
              $name = $matches[2];
            } else {
              $name = $link;
              $link = '';
            }
          }
          $name = preg_replace('/(\(.*?\))/', '', $name);
          if( $is_dom ) {
            $name = 'event.'.$name;
          }
          $this->view->results .= $name ." - ";
          $this->view->results .= $link ." - " . $is_dom.' - ';
          $this->view->results .= $desc ."\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $name,
            'url' => $link,
            'short_description' => $desc,
            'scrapeable' => 1
          ));
        }
        if( !$is_dom ) {
          continue;
        }
      }

      $start_index = strpos($contents, 'Keyboard Attributes</h');
      $end_index = strpos($contents, '</table>', $start_index);
      if( $start_index !== false && $end_index !== false ) {
        $events = array_slice(
          explode(
            '<tr>',
            substr($contents, $start_index, $end_index - $start_index)
          ),
          2
        );

        foreach( $events as $event ) {
          $elements = explode('<td valign="top">', $event);
          foreach( $elements as &$element ) {
            $element = trim(str_replace('&nbsp;', '', str_replace("\n", '', strip_tags($element, '<a>'))));
          }
          $link = $elements[1];
          $desc = $elements[2];
          $ff = $elements[3];
          if( count($elements) >= 6 ) {
            $ns = $elements[4];
            $ie = $elements[5];
          } else {
            $ie = $elements[4];
          }

          $name = '';
          if( $link ) {
            if( preg_match('/<a(?: target="_top")? href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
              $link = $matches[1];
              $name = $matches[2];
            } else {
              $name = $link;
              $link = '';
            }
          }
          $name = preg_replace('/(\(.*?\))/', '', $name);
          if( $is_dom ) {
            $name = 'event.'.$name;
          }
          $this->view->results .= $name ." - ";
          $this->view->results .= $link ." - " . $is_dom.' - ';
          $this->view->results .= $desc ."\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $name,
            'url' => $link,
            'short_description' => $desc,
            'scrapeable' => 1
          ));
        }
        if( !$is_dom ) {
          continue;
        }
      }

      $start_index = strpos($contents, 'Event Attributes</h');
      $end_index = strpos($contents, '</table>', $start_index);
      if( $start_index !== false && $end_index !== false ) {
        $events = array_slice(
          explode(
            '<tr>',
            substr($contents, $start_index, $end_index - $start_index)
          ),
          2
        );

        foreach( $events as $event ) {
          $elements = explode('<td valign="top">', $event);
          foreach( $elements as &$element ) {
            $element = trim(str_replace('&nbsp;', '', str_replace("\n", '', strip_tags($element, '<a>'))));
          }
          $link = $elements[1];
          $desc = $elements[2];
          $ff = $elements[3];
          if( count($elements) >= 6 ) {
            $ns = $elements[4];
            $ie = $elements[5];
          } else {
            $ie = $elements[4];
          }

          $name = '';
          if( $link ) {
            if( preg_match('/<a(?: target="_top")? href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
              $link = $matches[1];
              $name = $matches[2];
            } else {
              $name = $link;
              $link = '';
            }
          }
          $name = preg_replace('/(\(.*?\))/', '', $name);
          if( $is_dom ) {
            $name = 'event.'.$name;
          }
          $this->view->results .= $name ." - ";
          $this->view->results .= $link ." - " . $is_dom.' - ';
          $this->view->results .= $desc ."\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $name,
            'url' => $link,
            'short_description' => $desc,
            'scrapeable' => 1
          ));
        }
        if( !$is_dom ) {
          continue;
        }
      }

      $start_index = strpos($contents, '<h3>Properties</h3>');
      $end_index = strpos($contents, '</table>', $start_index);
      if( $start_index !== false && $end_index !== false ) {
        $events = array_slice(
          explode(
            '<tr>',
            substr($contents, $start_index, $end_index - $start_index)
          ),
          2
        );

        foreach( $events as $event ) {
          $elements = explode('<td valign="top">', $event);
          foreach( $elements as &$element ) {
            $element = trim(str_replace('&nbsp;', '', str_replace("\n", '', strip_tags($element, '<a>'))));
          }
          if( count($elements) < 2 ) {
            $this->view->results .= 'Missing '.print_r($event);
            continue;
          }
          $link = $elements[1];
          $desc = $elements[2];
          $ff = $elements[3];
          if( count($elements) >= 6 ) {
            $ns = $elements[4];
            $ie = $elements[5];
          } else {
            $ie = $elements[4];
          }

          $name = '';
          if( $link ) {
            if( preg_match('/<a(?: target="_top")? href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
              $link = $matches[1];
              $name = $matches[2];
            } else {
              $name = $link;
              $link = '';
            }
          }
          $name = preg_replace('/(\(.*?\))/', '', $name);
          $this->view->results .= $object_name.'.'.$name ." - ";
          $this->view->results .= $link ." - " . $is_dom.' - ';
          $this->view->results .= $desc ."\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $object_name.'.'.$name,
            'url' => $link,
            'short_description' => $desc,
            'scrapeable' => 1
          ));
        }
        if( !$is_dom ) {
          continue;
        }
      }

      $start_index = 0;
      do {
        $start_index = strpos($contents, 'properties</a></h3>', $start_index);
        $end_index = strpos($contents, '</table>', $start_index);
        if( $start_index !== false && $end_index !== false ) {
          $events = array_slice(
            explode(
              '<tr>',
              substr($contents, $start_index, $end_index - $start_index)
            ),
            2
          );

          foreach( $events as $event ) {
            $elements = explode('<td valign="top">', $event);
            foreach( $elements as &$element ) {
              $element = trim(str_replace('&nbsp;', '', str_replace("\n", '', strip_tags($element, '<a>'))));
            }
            $link = $elements[1];
            $desc = $elements[2];
            $ff = $elements[3];
            if( count($elements) >= 6 ) {
              $ns = $elements[4];
              $ie = $elements[5];
            } else {
              $ie = $elements[4];
            }

            $name = '';
            if( $link ) {
              if( preg_match('/<a(?: target="_top")? href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
                $link = $matches[1];
                $name = $matches[2];
              } else {
                $name = $link;
                $link = '';
              }
            }
            $name = preg_replace('/(\(.*?\))/', '', $name);
            $this->view->results .= $object_name.'.'.$name ." - ";
            $this->view->results .= $link ." - " . $is_dom.' - ';
            $this->view->results .= $desc ."\n";
  
            $this->getFunctionsModel()->insertOrUpdateFunction(array(
              'category' => $category_id,
              'hierarchy' => $hierarchy['id'],
              'name' => $object_name.'.'.$name,
              'url' => $link,
              'short_description' => $desc,
              'scrapeable' => 1
            ));
          }
          if( !$is_dom ) {
            continue;
          }
        }
        $start_index++;
      } while( $start_index !== false );

      $start_index = strpos($contents, 'Standard Properties</h3>');
      $end_index = strpos($contents, '</table>', $start_index);
      if( $start_index !== false && $end_index !== false ) {
        $events = array_slice(
          explode(
            '<tr>',
            substr($contents, $start_index, $end_index - $start_index)
          ),
          2
        );

        foreach( $events as $event ) {
          $elements = explode('<td valign="top">', $event);
          foreach( $elements as &$element ) {
            $element = trim(str_replace('&nbsp;', '', str_replace("\n", '', strip_tags($element, '<a>'))));
          }
          $link = $elements[1];
          $desc = $elements[2];
          $ff = $elements[3];
          if( count($elements) >= 6 ) {
            $ns = $elements[4];
            $ie = $elements[5];
          } else {
            $ie = $elements[4];
          }

          $name = '';
          if( $link ) {
            if( preg_match('/<a(?: target="_top")? href="(.+?)">(.+)?<\/a>/', $link, $matches) ) {
              $link = $matches[1];
              $name = $matches[2];
            } else {
              $name = $link;
              $link = '';
            }
          }
          $name = preg_replace('/(\(.*?\))/', '', $name);
          $this->view->results .= $object_name.'.'.$name ." - ";
          $this->view->results .= $link ." - " . $is_dom.' - ';
          $this->view->results .= $desc ."\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $object_name.'.'.$name,
            'url' => $link,
            'short_description' => $desc,
            'scrapeable' => 1
          ));
        }
        if( !$is_dom ) {
          continue;
        }
      }

      $this->view->results .= 'We couldn\'t find the properties or methods...' . "\n";

    }
  }

  private function scrapeiPhoneDir() {
    $category = 'iPhone';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $hierarchies = array(
      /*'6' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/CocoaTouch/AddressBookUI',
      '7' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/CocoaTouch/UIKit',
      '81' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/Media/AudioToolbox',
      '82' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/Media/AudioUnit',
      '83' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/Media/AVFoundation',
      '84' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/Media/CoreAudio',
      '85' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/Media/CoreGraphics',
      '86' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/Media/MediaPlayer',
      '87' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/Media/OpenGLES',
      '88' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/Media/QuartzCore',*/
      '110' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/CoreServices/AddressBook',
      '111' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/CoreServices/CoreFoundation',
      '112' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/CoreServices/CoreLocation',
      '113' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/CoreServices/Foundation',
      '114' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/CoreServices/SystemConfiguration',
      '115' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/CoreOS/CFNetwork',
      '116' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/CoreOS/Security',
      '117' => 'http://developer.apple.com/iphone/library/navigation/Frameworks/CoreOS/System',
    );
    foreach( $hierarchies as $parent_id => $base_url ) {
      $contents = file_get_contents($base_url.'/docdata.js');
      $contents = str_replace('"', '\"', $contents);
      $contents = str_replace("'", '"', $contents);
      $data = Zend_Json::decode($contents);
      foreach( $data as $item ) {
        if( strpos($item['title'], 'Class Reference') !== FALSE ||
            strpos($item['title'], 'Protocol Reference') !== FALSE ) {
          $name = str_replace(' Reference', '', $item['title']);

          $ref_url = explode('/', $base_url);
          $navigator = explode('/', $item['installPath']);
          foreach( $navigator as $dir ) {
            if( $dir == '..' ) {
              $ref_url = array_splice($ref_url, 0, -1);
            } else {
              $ref_url []= $dir;
            }
          }

          $ref_url = implode('/', $ref_url);
          //$this->view->results .= $name."\n";
          //$this->view->results .= $ref_url."\n";

          $subdata = file_get_contents($ref_url);

          if( !preg_match('/<META ID="refresh" HTTP-EQUIV=refresh CONTENT="0; URL=(.+?)">/', $subdata, $matches) ) {
            $this->view->results .= 'Unable to get redirected link, skipping...'."\n";
            continue;
          }

          $ref_url = str_replace('index.html', '', $ref_url).$matches[1];
          //$this->view->results .= $ref_url."\n";

          $id = $this->getHierarchiesModel()->fetchByName($category_id, $parent_id, $name);
          if( !$id ) {
            $this->view->results .= $this->getHierarchiesModel()->insert($category_id, $parent_id, $name, $ref_url, 1)."\n";
          }
        }
      }
    }
  }

  private function scrapeiPhone() {
    $category = 'iPhone';

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
      $this->view->results .= "\n".$hierarchy['name'] . "\n";
      
      if( !$hierarchy['source_url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $hierarchy['source_url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents($source_url);

      if( !preg_match('/<BODY bgcolor="#ffffff" onload="initialize_page\(\);"><a name=".+?" title="(.+?)"><\/a>/', $contents, $matches) ) {
        $this->view->results .= 'We didn\'t find the name, skipping...' . "\n";
        continue;
      }

      $name = $matches[1];
      $this->view->results .= '  name: '.$name."\n";
      $this->view->results .= '  link: '.$source_url."\n";

      $OVERVIEW_START = '<h2>Overview</h2>';
      $start_index = strpos($contents, $OVERVIEW_START);
      if( $start_index === FALSE ) {
        $this->view->results .= 'We didn\'t find an overview, skipping...' . "\n";
        continue;
      }
      $start_index += strlen($OVERVIEW_START);
      $end_index = strpos($contents, '</p>', $start_index);
      if( $end_index === FALSE ) {
        $this->view->results .= 'We couldn\'t find the end of the overview, skipping...' . "\n";
        continue;
      }

      $overview = str_replace("\n", ' ', substr($contents, $start_index, $end_index - $start_index));
      $overview = strip_tags($overview, '<b><code>');

      $this->view->results .= '  desc: '.$overview."\n";

      $this->getFunctionsModel()->insertOrUpdateFunction(array(
        'category' => $category_id,
        'hierarchy' => $hierarchy['id'],
        'name' => $name,
        'url' => $source_url,
        'short_description' => $overview,
        'data' => '{}'
      ));

      $rest = substr($contents, $end_index);
      $subsections = array_slice(explode('></a><h2>', $rest), 1);

      $type_map = array(
        'Properties'        => 1,
        'Class Methods'     => 2,
        'Instance Methods'  => 3
      );

      foreach( $subsections as $subsection ) {
        $sub_name = substr($subsection, 0, strpos($subsection, '</h2>'));
        $this->view->results .= '<b>'.$sub_name."</b>\n";
        if( !isset($type_map[$sub_name]) ) {
          $this->view->results .= 'Invalid type of section, skipping...'."\n";
          continue;
        }
        $type = $type_map[$sub_name];
        $this->view->results .= $type."\n";
        $items = explode('<h3 class="verytight">', $subsection);
        for( $index = 1; $index < count($items); ++$index ) {
          $item = $items[$index];
          $prev_item = $items[$index-1];
          $iteration = 0;
          $anchor_index = strlen($prev_item);
          do {
            $new_index = strrpos($prev_item, '<a name=', -(strlen($prev_item) - $anchor_index + 1));
            if( $new_index === FALSE ) {
              break;
            }
            $anchor_index = $new_index;
            $iteration++;
          } while( $iteration < 4 );
          $anchor = substr($prev_item, $anchor_index, strpos($prev_item, '</a>', $anchor_index) - $anchor_index);
          $item_name = substr($item, 0, strpos($item, '</h3>'));

          if( !preg_match('/<a name="(.+?)"/', $anchor, $matches) ) {
            $this->view->results .= 'Couldn\'t find anchor, skipping...'."\n\n";
            continue;
          }

          $anchor = $source_url.'#'.trim($matches[1]);

          $this->view->results .= '  Name: '.$item_name."\n";
          $this->view->results .= '  Link: '.$anchor."\n";

          if( !preg_match('/<p class="spaceabove">(.+?)<\/p>/', str_replace("\n", ' ', $item), $matches) ) {
            $this->view->results .= 'Couldn\'t find item summary, skipping...'."\n\n";
            continue;
          }
          $summary = trim(strip_tags($matches[1]));
          $this->view->results .= '  Desc: '.$summary."\n";

          if( !preg_match('/<p class="spaceabovemethod">(.+?)<\/p>/', $item, $matches) ) {
            if( !preg_match('/<pre><code>(.+?)<\/code><br><\/pre>/', $item, $matches) ) {
              $this->view->results .= 'Couldn\'t find method info, filling with empty string...'."\n\n";
              $method_info = '';
            } else {
              $method_info = trim(strip_tags($matches[1]));
            }
          } else {
            $method_info = trim(strip_tags($matches[1]));
          }

          $data = Zend_Json::encode(array(
            'i' => $method_info,
            't' => $type
          ));
          $data = preg_replace('/"([a-z])"/', '$1', $data);

          $this->view->results .= '  data: '.$data."\n\n";

          $this->getFunctionsModel()->insertOrUpdateFunction(array(
            'category' => $category_id,
            'hierarchy' => $hierarchy['id'],
            'name' => $name.' '.$item_name,
            'url' => $anchor,
            'short_description' => $summary,
            'data' => $data
          ));
        }
      }

      $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
    }
  }

  private function scrapeDjango2() {
    $category = 'django';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $functions = array(
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#date-hierarchy", "admin.ModelAdmin.date_hierarchy"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#form", "admin.ModelAdmin.form"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#fieldsets", "admin.ModelAdmin.fieldsets"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#fields", "admin.ModelAdmin.fields"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#exclude", "admin.ModelAdmin.exclude"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#filter-horizontal", "admin.ModelAdmin.filter_horizontal"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#filter-vertical", "admin.ModelAdmin.filter_vertical"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#list-display", "admin.ModelAdmin.list_display"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#list-display-links", "admin.ModelAdmin.list_display_links"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#list-filter", "admin.ModelAdmin.list_filter"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#list-per-page", "admin.ModelAdmin.list_per_page"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#list-select-related", "admin.ModelAdmin.list_select_related"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#inlines", "admin.ModelAdmin.inlines"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#ordering", "admin.ModelAdmin.ordering"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#prepopulated-fields", "admin.ModelAdmin.prepopulated_fields"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#radio-fields", "admin.ModelAdmin.radio_fields"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#raw-id-fields", "admin.ModelAdmin.raw_id_fields"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#save-as", "admin.ModelAdmin.save_as"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#save-on-top", "admin.ModelAdmin.save_on_top"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#search-fields", "admin.ModelAdmin.search_fields"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#formfield-overrides", "admin.ModelAdmin.formfield_overrides"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#save-model-self-request-obj-form-change", "admin.ModelAdmin.save_model", "save_model(self, request, obj, form, change)"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#save-formset-self-request-form-formset-change", "admin.ModelAdmin.save_formset", "save_formset(self, request, form, formset, change)"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#get-urls-self", "admin.ModelAdmin.get_urls", "get_urls(self)"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#formfield-for-foreignkey-self-db-field-request-kwargs", "admin.ModelAdmin.formfield_for_foreignkey", "formfield_for_foreignkey(self, db_field, request, **kwargs)")
    );

    for( $index = 0; $index < count($functions); ++$index ) {
      $data = '';
      if( count($functions[$index]) > 2 ) {
        $data = $functions[$index][2];
      }
      $this->getFunctionsModel()->insertOrUpdateFunction(array(
        'category' => $category_id,
        'hierarchy' => 2,
        'name' => $functions[$index][1],
        'url' => $functions[$index][0],
        'short_description' => "",
        'data' => $data
      ));
    }
  }

  private function scrapeDjango1() {
    $category = 'django';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $categories = array(
      array("http://docs.djangoproject.com/en/dev/ref/contrib/admin/#module-django.contrib.admin", "django.contrib.admin",
        "Django's admin site."),
      array("http://docs.djangoproject.com/en/dev/topics/auth/#module-django.contrib.auth", "django.contrib.auth",
        "Django's authentication framework."),
      array("http://docs.djangoproject.com/en/dev/topics/auth/#module-django.contrib.auth.forms", "django.contrib.auth.forms",
        ""),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.contrib.auth.middleware", "django.contrib.auth.middleware",
        "Authentication middleware."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/comments/#module-django.contrib.comments", "django.contrib.comments",
        "Django's comment framework"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/comments/signals/#module-django.contrib.comments.signals", "django.contrib.comments.signals",
        "Signals sent by the comment module."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/contenttypes/#module-django.contrib.contenttypes", "django.contrib.contenttypes",
        "Provides generic interface to installed models."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/csrf/#module-django.contrib.csrf", "django.contrib.csrf",
        "Protects against Cross Site Request Forgeries"),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.contrib.csrf.middleware", "django.contrib.csrf.middleware",
        "Middleware adding protection against Cross Site Request Forgeries."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/databrowse/#module-django.contrib.databrowse", "django.contrib.databrowse",
        "Databrowse is a Django application that lets you browse your data."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/flatpages/#module-django.contrib.flatpages", "django.contrib.flatpages",
        "A framework for managing simple ?flat? HTML content in a database."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/formtools/form-preview/#module-django.contrib.formtools", "django.contrib.formtools",
        "Displays an HTML form, forces a preview, then does something with the submission."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/formtools/form-wizard/#module-django.contrib.formtools.wizard", "django.contrib.formtools.wizard",
        "Splits forms across multiple Web pages."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/humanize/#module-django.contrib.humanize", "django.contrib.humanize",
        "A set of Django template filters useful for adding a \"human touch\" to data."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/localflavor/#module-django.contrib.localflavor", "django.contrib.localflavor",
        "A collection of various Django snippets that are useful only for a particular country or culture."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/redirects/#module-django.contrib.redirects", "django.contrib.redirects",
        "A framework for managing redirects."),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.contrib.sessions.middleware", "django.contrib.sessions.middleware",
        "Session middleware."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/sitemaps/#module-django.contrib.sitemaps", "django.contrib.sitemaps",
        "A framework for generating Google sitemap XML files."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/sites/#module-django.contrib.sites", "django.contrib.sites",
        "Lets you operate multiple web sites from the same database and Django project"),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/syndication/#module-django.contrib.syndication", "django.contrib.syndication",
        "A framework for generating syndication feeds, in RSS and Atom, quite easily."),
      array("http://docs.djangoproject.com/en/dev/ref/contrib/webdesign/#module-django.contrib.webdesign", "django.contrib.webdesign",
        "Helpers and utilities targeted primarily at Web *designers* rather than Web *developers*."),
      array("http://docs.djangoproject.com/en/dev/ref/files/#module-django.core.files", "django.core.files",
        "File handling and storage"),
      array("http://docs.djangoproject.com/en/dev/topics/email/#module-django.core.mail", "django.core.mail",
        "Helpers to easily send e-mail."),
      array("http://docs.djangoproject.com/en/dev/topics/pagination/#module-django.core.paginator", "django.core.paginator",
        "Classes to help you easily manage paginated data."),
      array("http://docs.djangoproject.com/en/dev/ref/signals/#module-django.core.signals", "django.core.signals",
        "Core signals sent by the request/response system."),
      array("http://docs.djangoproject.com/en/dev/topics/db/models/#module-django.db.models", "django.db.models",
        ""),
      array("http://docs.djangoproject.com/en/dev/ref/models/fields/#module-django.db.models.fields", "django.db.models.fields",
        "Built-in field types."),
      array("http://docs.djangoproject.com/en/dev/ref/models/fields/#module-django.db.models.fields.related", "django.db.models.fields.related",
        "Related field types"),
      array("http://docs.djangoproject.com/en/dev/ref/signals/#module-django.db.models.signals", "django.db.models.signals",
        "Signals sent by the model system."),
      array("http://docs.djangoproject.com/en/dev/topics/signals/#module-django.dispatch", "django.dispatch",
        "Signal dispatch"),
      array("http://docs.djangoproject.com/en/dev/ref/forms/fields/#module-django.forms.fields", "django.forms.fields",
        "Django's built-in form fields."),
      array("http://docs.djangoproject.com/en/dev/ref/forms/widgets/#module-django.forms.widgets", "django.forms.widgets",
        "Django's built-in form widgets."),
      array("http://docs.djangoproject.com/en/dev/ref/request-response/#module-django.http", "django.http",
        "Classes dealing with HTTP requests and responses."),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.middleware", "django.middleware",
        "Django's built-in middleware classes."),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.middleware.cache", "django.middleware.cache",
        "Middleware for the site-wide cache."),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.middleware.common", "django.middleware.common",
        "Middleware adding \"common\" conveniences for perfectionists."),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.middleware.doc", "django.middleware.doc",
        "Middleware to help your app self-document."),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.middleware.gzip", "django.middleware.gzip",
        "Middleware to serve gziped content for performance."),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.middleware.http", "django.middleware.http",
        "Middleware handling advanced HTTP features."),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.middleware.locale", "django.middleware.locale",
        "Middleware to enable language selection based on the request."),
      array("http://docs.djangoproject.com/en/dev/ref/middleware/#module-django.middleware.transaction", "django.middleware.transaction",
        "Middleware binding a database transaction to each web request."),
      array("http://docs.djangoproject.com/en/dev/topics/testing/#module-django.test", "django.test",
        "Testing tools for Django applications."),
      array("http://docs.djangoproject.com/en/dev/topics/testing/#module-django.test.client", "django.test.client",
        "Django's test client."),
      array("http://docs.djangoproject.com/en/dev/ref/signals/#module-django.test.signals", "django.test.signals",
        "Signals sent during testing."),
      array("http://docs.djangoproject.com/en/dev/topics/testing/#module-django.test.utils", "django.test.utils",
        "Helpers to write custom test runners."),
      array("http://docs.djangoproject.com/en/dev/howto/static-files/#module-django.views.static", "django.views.static",
        "Serving of static files during development.")
    );

    for( $index = 0; $index < count($categories); ++$index ) {
      $this->view->results .= $this->getHierarchiesModel()->insert(
        $category_id,
        1,
        $categories[$index][1],
        $categories[$index][0])."\n";
    }
  }

  private function scrapeFacebookFbmlPhase2() {
    $category = 'Facebook API';

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
      if( $function['hierarchy'] < 5 || $function['hierarchy'] > 20 ) {
        continue;
      }

      $this->view->results .= $function['name'] . "\n";
      if( !$function['url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $function['url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents($source_url);

      $start_index = strpos($contents, '<a name="Description">');
      if( $start_index === FALSE ) {
        $this->view->results .= 'We didn\'t find a description, skipping...' . "\n";
        continue;
      }
      $start_index = strpos($contents, '<p>', $start_index);
      if( $start_index === FALSE ) {
        $this->view->results .= 'We couldn\'t find the beginning of the description, skipping...' . "\n";
        continue;
      }
      
      $end_index = strpos($contents, '</p>', $start_index);
      if( $end_index === FALSE ) {
        $this->view->results .= 'We couldn\'t find the end of the description, skipping...' . "\n";
        continue;
      }

      $line = str_replace("\n", '', substr($contents, $start_index, $end_index - $start_index));
      $line = strip_tags($line, '<b><code>');

      $name = $this->getFunctionsModel()->fetchName($category_id, $function['id']);

      $this->getFunctionsModel()->insertOrUpdateFunction(array(
        'category'  => $category_id,
        'hierarchy' => $function['hierarchy'],
        'name'      => $name,
        'short_description' => $line
      ));
/*
      $this->getFunctionsModel()->setData(array(
        'category' => $category_id,
        'id' => $function['id'],
        'data' => $line
      ));*/
    }
  }

  private function scrapeFacebookFbml() {
    $category = 'Facebook API';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $contents = file_get_contents('http://wiki.developers.facebook.com/index.php/FBML');

    $start_index = strpos($contents, '<div class="fbml_section">');
    if( $start_index === FALSE ) {
      $this->view->results .= 'We didn\'t find an fbml section, skipping...' . "\n";
      return;
    }

    $end_index = strpos($contents, '<p><br clear="all"/>', $start_index);
    if( $end_index === FALSE ) {
      $this->view->results .= 'We couldn\'t find the end of the list, skipping...' . "\n";
      return;
    }

    $list_data = substr($contents, $start_index, $end_index - $start_index);
    $list = array_slice(explode('<a name="', $list_data), 1);

    foreach( $list as $item ) {
      $link = substr($item, 0, strpos($item, '"'));
      if( !preg_match('/<span class="mw-headline">(.+?)<\/span>/', $item, $matches) ) {
        $this->view->results .= 'We couldn\'t find the headline, skipping...' . "\n";
        continue;
      }

      $title = trim($matches[1]);

      $item = str_replace("\n", '', $item);
      if( !preg_match_all('/<a href="(.+?)" title=".+?">(.+?)<\/a>/', $item, $matches) ) {
        $this->view->results .= 'We couldn\'t find links, skipping...' . "\n";
        continue;
      }

      // Scrape hierarchies.
      /*$this->view->results .= $this->getHierarchiesModel()->insert(
        $category_id,
        4,
        $title,
        'http://wiki.developers.facebook.com/index.php/FBML#'.$link
      )."\n";*/

      // Scrape functions.
      $hierarchy = $this->getHierarchiesModel()->fetchByName($category_id, 4, $title);
      for( $index = 0; $index < count($matches[0]); ++$index ) {
        echo $matches[2][$index] . "\n";
        $this->getFunctionsModel()->insertOrUpdateFunction(array(
          'category' => $category_id,
          'hierarchy' => $hierarchy,
          'name' => $matches[2][$index],
          'url' => 'http://wiki.developers.facebook.com'.$matches[1][$index]
        ));
      }
    }
  }

  private function scrapeFacebook() {
    $category = 'Facebook API';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $functions = array(
      array("http://wiki.developers.facebook.com/index.php/Admin.getAllocation", "admin.getAllocation",
        "Returns the current allocation limit for your application for the specified integration point."),
      array("http://wiki.developers.facebook.com/index.php/Admin.getAppProperties", "admin.getAppProperties",
        "Returns values of properties for your applications from the Facebook Developer application."),
      array("", "admin.getDailyMetrics",
        "This method is deprecated. Please use Admin.getMetrics instead."),
      array("http://wiki.developers.facebook.com/index.php/Admin.getMetrics", "admin.getMetrics",
        "Returns specified metrics for your application, given a time period."),
      array("http://wiki.developers.facebook.com/index.php/Admin.getRestrictionInfo", "admin.getRestrictionInfo",
        "Returns the demographic restrictions for the application."),
      array("http://wiki.developers.facebook.com/index.php/Admin.setAppProperties", "admin.setAppProperties",
        "Sets values for properties for your applications in the Facebook Developer application."),
      array("http://wiki.developers.facebook.com/index.php/Admin.setRestrictionInfo", "admin.setRestrictionInfo",
        "Sets the demographic restrictions for the application."),
      array("http://wiki.developers.facebook.com/index.php/Application.getPublicInfo", "application.getPublicInfo",
        "Returns public information about a given application (not necessarily your own)."),
      array("http://wiki.developers.facebook.com/index.php/Auth.createToken", "auth.createToken", 
        "Creates an auth_token to be passed in as a parameter to login.php and then to auth.getSession after the user has logged in."),
      array("http://wiki.developers.facebook.com/index.php/Auth.expireSession", "auth.expireSession", 
        "Expires the session indicated in the API call, for your application."),
      array("http://wiki.developers.facebook.com/index.php/Auth.getSession", "auth.getSession", 
        "Returns the session key bound to an auth_token, as returned by auth.createToken or in the callback URL."),
      array("http://wiki.developers.facebook.com/index.php/Auth.promoteSession", "auth.promoteSession", 
        "Returns a temporary session secret associated to the current existing session, for use in a client-side component to an application."),
      array("http://wiki.developers.facebook.com/index.php/Auth.revokeAuthorization", "auth.revokeAuthorization",
        "If this method is called for the logged in user, then no further API calls can be made on that user's behalf until the user decides to authorize the application again."),
      array("http://wiki.developers.facebook.com/index.php/Auth.revokeExtendedPermission", "auth.revokeExtendedPermission",
        "Removes a specific extended permission that a user explicitly granted to your application."),
      array("http://wiki.developers.facebook.com/index.php/Batch.run", "batch.run",
        "Execute a list of individual API calls in a single batch."),
      array("http://wiki.developers.facebook.com/index.php/Comments.get", "comments.get",
      	"Returns all comments for a given xid posted through fb:comments. This method is a wrapper for the FQL query on the comment FQL table."),
      array("http://wiki.developers.facebook.com/index.php/Data.getCookies", "data.getCookies",
        "Returns all cookies for a given user and application."),
      array("http://wiki.developers.facebook.com/index.php/Data.setCookie", "data.setCookie",
        "Sets a cookie for a given user and application."),
      array("http://wiki.developers.facebook.com/index.php/Events.cancel", "events.cancel", 
        "Cancels an event. The application must be an admin of the event."),
      array("http://wiki.developers.facebook.com/index.php/Events.create", "events.create", 
        "Creates an event on behalf of the user if the application has an active session; otherwise it creates an event on behalf of the application."),
      array("http://wiki.developers.facebook.com/index.php/Events.edit", "events.edit", 
        "Edits an existing event. The application must be an admin of the event."),
      array("http://wiki.developers.facebook.com/index.php/Events.get", "events.get", 
        "Returns all visible events according to the filters specified."),
      array("http://wiki.developers.facebook.com/index.php/Events.getMembers", "events.getMembers", 
        "Returns membership list data associated with an event."),
      array("http://wiki.developers.facebook.com/index.php/Events.rsvp", "events.rsvp", 
        "Sets the attendance option for the current user."),
      array("http://wiki.developers.facebook.com/index.php/Fbml.deleteCustomTags", "fbml.deleteCustomTags",
        "Deletes one or more custom tags you previously registered for the calling application with fbml.registerCustomTags"),
      array("http://wiki.developers.facebook.com/index.php/Fbml.getCustomTags", "fbml.getCustomTags",
        "Returns the custom tag definitions for tags that were previously defined using fbml.registerCustomTags"),
      array("http://wiki.developers.facebook.com/index.php/Fbml.refreshImgSrc", "fbml.refreshImgSrc", 
        "Fetches and re-caches the image stored at the given URL."),
      array("http://wiki.developers.facebook.com/index.php/Fbml.refreshRefUrl", "fbml.refreshRefUrl", 
        "Fetches and re-caches the content stored at the given URL."),
      array("http://wiki.developers.facebook.com/index.php/Fbml.registerCustomTags", "fbml.registerCustomTags",
        "Registers custom tags you can include in your that applications' FBML markup. Custom tags consist of FBML snippets that are rendered during parse time on the containing page that references the custom tag."),
      array("http://wiki.developers.facebook.com/index.php/Fbml.setRefHandle", "fbml.setRefHandle", 
        "Associates a given \"handle\" with FBML markup so that the handle can be used within the fb:ref FBML tag."),
      array("http://wiki.developers.facebook.com/index.php/Fbml.uploadNativeStrings", "fbml.uploadNativeStrings",
        "Lets you insert text strings into the Facebook Translations database so they can be translated."),
      array("http://wiki.developers.facebook.com/index.php/Feed.deactivateTemplateBundleByID", "feed.deactivateTemplateBundleByID",
        "Deactivates a previously registered template bundle."),
      array("http://wiki.developers.facebook.com/index.php/Feed.getRegisteredTemplateBundleByID", "feed.getRegisteredTemplateBundleByID",
        "Retrieves information about a specified template bundle previously registered by the requesting application."),
      array("http://wiki.developers.facebook.com/index.php/Feed.getRegisteredTemplateBundles", "feed.getRegisteredTemplateBundles",
        "Retrieves the full list of all the template bundles registered by the requesting application."),
      array("", "feed.publishActionOfUser",
        "This method is deprecated. Please use feed.publishUserAction instead."),
      array("", "feed.publishStoryToUser",
        "This method is deprecated. Please use feed.publishUserAction instead."),
      array("http://wiki.developers.facebook.com/index.php/Feed.publishTemplatizedAction", "feed.publishTemplatizedAction", 
        "Publishes a Mini-Feed story to the Facebook Page corresponding to the page_actor_id parameter. Note: This method is deprecated for actions taken by users only; it still works for actions taken by Facebook Pages."),
      array("http://wiki.developers.facebook.com/index.php/Feed.publishUserAction", "feed.publishUserAction",
        "Publishes a story on behalf of the user owning the session, using the specified template bundle."),
      array("http://wiki.developers.facebook.com/index.php/Feed.registerTemplateBundle", "feed.registerTemplateBundle",
        "Builds a template bundle around the specified templates, registers them on Facebook, and responds with a template bundle ID that can be used to identify your template bundle to other Feed-related API calls."),
      array("http://wiki.developers.facebook.com/index.php/Fql.query", "fql.query", 
        "Evaluates an FQL (Facebook Query Language) query."),
      array("http://wiki.developers.facebook.com/index.php/Friends.areFriends", "friends.areFriends", 
        "Returns whether or not each pair of specified users is friends with each other."),
      array("http://wiki.developers.facebook.com/index.php/Friends.get", "friends.get", 
        "Returns the identifiers for the current user's Facebook friends."),
      array("http://wiki.developers.facebook.com/index.php/Friends.getAppUsers", "friends.getAppUsers", 
        "Returns the identifiers for the current user's Facebook friends who have authorized the specific calling application."),
      array("http://wiki.developers.facebook.com/index.php/Friends.getLists", "friends.getLists", 
        "Returns the identifiers for the current user's Facebook friend lists."),
      array("http://wiki.developers.facebook.com/index.php/Groups.get", "groups.get", 
        "Returns all visible groups according to the filters specified."),
      array("http://wiki.developers.facebook.com/index.php/Groups.getMembers", "groups.getMembers", 
        "Returns membership list data associated with a group."),
      array("http://wiki.developers.facebook.com/index.php/Links.get", "links.get",
        "Returns all links the user has posted on their profile through your application."),
      array("http://wiki.developers.facebook.com/index.php/Links.post", "links.post",
        "Lets a user post a link on their Wall through your application."),
      array("http://wiki.developers.facebook.com/index.php/LiveMessage.send", "liveMessage.send",
        "Sends a \"message\" directly to a user's browser, which can be handled in FBJS."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.createListing", "marketplace.createListing", 
        "Create or modify a listing in Marketplace."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.getCategories", "marketplace.getCategories", 
        "Returns all the Marketplace categories."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.getListings", "marketplace.getListings", 
        "Return all Marketplace listings either by listing ID or by user."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.getSubCategories", "marketplace.getSubCategories", 
        "Returns the Marketplace subcategories for a particular category."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.removeListing", "marketplace.removeListing", 
        "Remove a listing from Marketplace."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.search", "marketplace.search", 
        "Search Marketplace for listings filtering by category, subcategory and a query string."),
      array("http://wiki.developers.facebook.com/index.php/Notes.create", "notes.create",
        "Lets a user write a Facebook note through your application."),
      array("http://wiki.developers.facebook.com/index.php/Notes.delete", "notes.delete",
        "Lets a user delete a Facebook note that was written through your application."),
      array("http://wiki.developers.facebook.com/index.php/Notes.edit", "notes.edit",
        "Lets a user edit a Facebook note through your application."),
      array("http://wiki.developers.facebook.com/index.php/Notes.get", "notes.get",
        "Returns a list of all of the visible notes written by the specified user."),
      array("http://wiki.developers.facebook.com/index.php/Notifications.get", "notifications.get", 
        "Returns information on outstanding Facebook notifications for current session user."),
      array("http://wiki.developers.facebook.com/index.php/Notifications.send", "notifications.send", 
        "Sends a notification to a set of users."),
      array("http://wiki.developers.facebook.com/index.php/Notifications.sendEmail", "notifications.sendEmail", 
        "Sends an email to the specified users who have the application."),
      array("http://wiki.developers.facebook.com/index.php/Pages.getInfo", "pages.getInfo", 
        "Returns all visible pages to the filters specified."),
      array("http://wiki.developers.facebook.com/index.php/Pages.isAdmin", "pages.isAdmin", 
        "Checks whether the logged-in user is the admin for a given Page."),
      array("http://wiki.developers.facebook.com/index.php/Pages.isAppAdded", "pages.isAppAdded", 
        "Checks whether the Page has added the application."),
      array("http://wiki.developers.facebook.com/index.php/Pages.isFan", "pages.isFan", 
        "Checks whether a user is a fan of a given Page."),
      array("http://wiki.developers.facebook.com/index.php/Photos.addTag", "photos.addTag", 
        "Adds a tag with the given information to a photo."),
      array("http://wiki.developers.facebook.com/index.php/Photos.createAlbum", "photos.createAlbum", 
        "Creates and returns a new album owned by the current session user."),
      array("http://wiki.developers.facebook.com/index.php/Photos.get", "photos.get", 
        "Returns all visible photos according to the filters specified."),
      array("http://wiki.developers.facebook.com/index.php/Photos.getAlbums", "photos.getAlbums", 
        "Returns metadata about all of the photo albums uploaded by the specified user."),
      array("http://wiki.developers.facebook.com/index.php/Photos.getTags", "photos.getTags", 
        "Returns the set of user tags on all photos specified."),
      array("http://wiki.developers.facebook.com/index.php/Photos.upload", "photos.upload", 
        "Uploads a photo owned by the current session user and returns the new photo."),
      array("http://wiki.developers.facebook.com/index.php/Profile.getFBML", "profile.getFBML", 
        "Gets the FBML that is currently set for a user's profile."),
      array("http://wiki.developers.facebook.com/index.php/Profile.getInfo", "profile.getInfo",
        "Returns the specified user's application info section for the calling application."),
      array("http://wiki.developers.facebook.com/index.php/Profile.getInfoOptions", "profile.getInfoOptions",
        "Returns the options associated with the specified field for an application info section."),
      array("http://wiki.developers.facebook.com/index.php/Profile.setFBML", "profile.setFBML", 
        "Sets the FBML for a user's profile, including the content for both the profile box and the profile actions."),
      array("http://wiki.developers.facebook.com/index.php/Profile.setInfo", "profile.setInfo",
        "Configures an application info section that the specified user can install on the Info tab of her profile."),
      array("http://wiki.developers.facebook.com/index.php/Profile.setInfoOptions", "profile.setInfoOptions",
        "Specifies the objects for a field for an application info section."),
      array("http://wiki.developers.facebook.com/index.php/Status.get", "status.get",
        "Returns the user's current and most recent statuses. This is a streamlined version of users.setStatus."),
      array("http://wiki.developers.facebook.com/index.php/Status.set", "status.set",
        "Updates a user's Facebook status through your application."),
      array("http://wiki.developers.facebook.com/index.php/Users.getInfo", "users.getInfo", 
        "Returns a wide array of user-specific information for each user identifier passed, limited by the view of the current user."),
      array("http://wiki.developers.facebook.com/index.php/Users.getLoggedInUser", "users.getLoggedInUser", 
        "Gets the user ID (uid) associated with the current session."),
      array("", "users.getStandardInfo",
        "Returns an array of user-specific information for use by the application itself."),
      array("http://wiki.developers.facebook.com/index.php/Users.hasAppPermission", "users.hasAppPermission", 
        "Checks whether the user has opted in to an extended application permission."),
      array("", "users.isAppAdded",
        "This method is deprecated. Please use users.isAppUser instead."),
      array("http://wiki.developers.facebook.com/index.php/Users.isAppUser", "users.isAppUser",
        "Returns whether the user (either the session user or user specified by UID) has authorized the calling application."),
      array("http://wiki.developers.facebook.com/index.php/Users.isVerified", "users.isVerified",
        "Returns whether the user is a verified Facebook user."),
      array("http://wiki.developers.facebook.com/index.php/Users.setStatus", "users.setStatus", 
        "Updates a user's Facebook status."),
      array("http://wiki.developers.facebook.com/index.php/Video.getUploadLimits", "video.getUploadLimits",
        "Returns the file size and length limits for a video that the current user can upload through your application."),
      array("http://wiki.developers.facebook.com/index.php/Video.upload", "video.upload",
        "Uploads a video owned by the current session user and returns the video.")
    );

    for( $index = 0; $index < count($functions); ++$index ) {
      $this->getFunctionsModel()->insertOrUpdateFunction(array(
        'category' => $category_id,
        'hierarchy' => 2,
        'name' => $functions[$index][1],
        'url' => $functions[$index][0],
        'short_description' => $functions[$index][2]
      ));
    }
  }

  private function scrapeZend() {
    $category = 'Zend';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $categories = array(
      "Zend_Acl",
      "Zend_Amf",
      "Zend_Auth",
      "Zend_Cache",
      "Zend_Captcha",
      "Zend_Config",
      "Zend_Config_Writer",
      "Zend_Console_Getopt",
      "Zend_Controller",
      "Zend_Currency",
      "Zend_Date",
      "Zend_Db",
      "Zend_Debug",
      "Zend_Dojo",
      "Zend_Dom",
      "Zend_Exception",
      "Zend_Feed",
      "Zend_File",
      "Zend_Filter",
      "Zend_Filter_Input",
      "Zend_Form",
      "Zend_Gdata",
      "Zend_Http",
      "Zend_Infocard",
      "Zend_Json",
      "Zend_Layout",
      "Zend_Ldap",
      "Zend_Loader",
      "Zend_Locale",
      "Zend_Log",
      "Zend_Mail",
      "Zend_Measure",
      "Zend_Memory",
      "Zend_Mime",
      "Zend_OpenId",
      "Zend_Paginator",
      "Zend_Pdf",
      "Zend_ProgressBar",
      "Zend_Registry",
      "Zend_Rest",
      "Zend_Search_Lucene",
      "Zend_Server_Reflection",
      "Zend_Service_Akismet",
      "Zend_Service_Amazon",
      "Zend_Service_Audioscrobbler",
      "Zend_Service_Delicious",
      "Zend_Service_Flickr",
      "Zend_Service_Nirvanix",
      "Zend_Service_ReCaptcha",
      "Zend_Service_Simpy",
      "Zend_Service_SlideShare",
      "Zend_Service_StrikeIron",
      "Zend_Service_Technorati",
      "Zend_Service_Twitter",
      "Zend_Service_Yahoo",
      "Zend_Session",
      "Zend_Soap",
      "Zend_Test",
      "Zend_Text",
      "Zend_Timesync",
      "Zend_Translate",
      "Zend_Uri",
      "Zend_Validate",
      "Zend_Version",
      "Zend_View",
      "Zend_Wildfire",
      "Zend_XmlRpc",
      "ZendX_Console_Process_Unix",
      "ZendX_JQuery"
    );

    $urls = array(
      "http://framework.zend.com/manual/en/zend.acl.html",
      "http://framework.zend.com/manual/en/zend.amf.html",
      "http://framework.zend.com/manual/en/zend.auth.html",
      "http://framework.zend.com/manual/en/zend.cache.html",
      "http://framework.zend.com/manual/en/zend.captcha.html",
      "http://framework.zend.com/manual/en/zend.config.html",
      "http://framework.zend.com/manual/en/zend.config.writer.html",
      "http://framework.zend.com/manual/en/zend.console.getopt.html",
      "http://framework.zend.com/manual/en/zend.controller.html",
      "http://framework.zend.com/manual/en/zend.currency.html",
      "http://framework.zend.com/manual/en/zend.date.html",
      "http://framework.zend.com/manual/en/zend.db.html",
      "http://framework.zend.com/manual/en/zend.debug.html",
      "http://framework.zend.com/manual/en/zend.dojo.html",
      "http://framework.zend.com/manual/en/zend.dom.html",
      "http://framework.zend.com/manual/en/zend.exception.html",
      "http://framework.zend.com/manual/en/zend.feed.html",
      "http://framework.zend.com/manual/en/zend.file.html",
      "http://framework.zend.com/manual/en/zend.filter.html",
      "http://framework.zend.com/manual/en/zend.filter.input.html",
      "http://framework.zend.com/manual/en/zend.form.html",
      "http://framework.zend.com/manual/en/zend.gdata.html",
      "http://framework.zend.com/manual/en/zend.http.html",
      "http://framework.zend.com/manual/en/zend.infocard.html",
      "http://framework.zend.com/manual/en/zend.json.html",
      "http://framework.zend.com/manual/en/zend.layout.html",
      "http://framework.zend.com/manual/en/zend.ldap.html",
      "http://framework.zend.com/manual/en/zend.loader.html",
      "http://framework.zend.com/manual/en/zend.locale.html",
      "http://framework.zend.com/manual/en/zend.log.html",
      "http://framework.zend.com/manual/en/zend.mail.html",
      "http://framework.zend.com/manual/en/zend.measure.html",
      "http://framework.zend.com/manual/en/zend.memory.html",
      "http://framework.zend.com/manual/en/zend.mime.html",
      "http://framework.zend.com/manual/en/zend.openid.html",
      "http://framework.zend.com/manual/en/zend.paginator.html",
      "http://framework.zend.com/manual/en/zend.pdf.html",
      "http://framework.zend.com/manual/en/zend.progressbar.html",
      "http://framework.zend.com/manual/en/zend.registry.html",
      "http://framework.zend.com/manual/en/zend.rest.html",
      "http://framework.zend.com/manual/en/zend.search.lucene.html",
      "http://framework.zend.com/manual/en/zend.server.reflection.html",
      "http://framework.zend.com/manual/en/zend.service.akismet.html",
      "http://framework.zend.com/manual/en/zend.service.amazon.html",
      "http://framework.zend.com/manual/en/zend.service.audioscrobbler.html",
      "http://framework.zend.com/manual/en/zend.service.delicious.html",
      "http://framework.zend.com/manual/en/zend.service.flickr.html",
      "http://framework.zend.com/manual/en/zend.service.nirvanix.html",
      "http://framework.zend.com/manual/en/zend.service.recaptcha.html",
      "http://framework.zend.com/manual/en/zend.service.simpy.html",
      "http://framework.zend.com/manual/en/zend.service.slideshare.html",
      "http://framework.zend.com/manual/en/zend.service.strikeiron.html",
      "http://framework.zend.com/manual/en/zend.service.technorati.html",
      "http://framework.zend.com/manual/en/zend.service.twitter.html",
      "http://framework.zend.com/manual/en/zend.service.yahoo.html",
      "http://framework.zend.com/manual/en/zend.session.html",
      "http://framework.zend.com/manual/en/zend.soap.html",
      "http://framework.zend.com/manual/en/zend.test.html",
      "http://framework.zend.com/manual/en/zend.text.html",
      "http://framework.zend.com/manual/en/zend.timesync.html",
      "http://framework.zend.com/manual/en/zend.translate.html",
      "http://framework.zend.com/manual/en/zend.uri.html",
      "http://framework.zend.com/manual/en/zend.validate.html",
      "http://framework.zend.com/manual/en/zend.version.html",
      "http://framework.zend.com/manual/en/zend.view.html",
      "http://framework.zend.com/manual/en/zend.wildfire.html",
      "http://framework.zend.com/manual/en/zend.xmlrpc.html",
      "http://framework.zend.com/manual/en/zendx.console.process.unix.html",
      "http://framework.zend.com/manual/en/zendx.jquery.html"
    );

    for( $index = 0; $index < count($categories); ++$index ) {
      $this->view->results .= $this->getHierarchiesModel()->insert($category_id, 1, $categories[$index], $urls[$index])."\n";
    }
  }

  private function scrapeCSSFunctions() {
    $category = 'CSS';

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

      $start_index = strpos($contents, '<h2>Possible Values</h2>');
      if( $start_index === FALSE ) {
        $this->view->results .= 'We didn\'t find possible values, skipping...' . "\n";
        continue;
      }
      $start_index += strlen('<h2>Possible Values</h2>');
      $end_index = strpos($contents, '</table>', $start_index);
      if( $end_index === FALSE ) {
        $this->view->results .= 'We couldn\'t find the end of the possible values, skipping...' . "\n";
        continue;
      }

      $data = substr($contents, $start, $end-$start);
      $this->view->results .= $data."\n";
/*
      $this->getFunctionsModel()->setData(array(
        'category' => $category_id,
        'id' => $function['id'],
        'data' => $line
      ));*/
    }
  }

  private function scrapePythonModules($createFunctions) {
    //scrapePythonModuleVersion($createFunctions, 'Python 3.0.1', ''http://docs.python.org/3.0/');
    $this->scrapePythonModuleVersion($createFunctions, 'Python 2.6.1', 'http://docs.python.org/');
  }

  private function scrapePythonModuleVersion($createFunctions, $version, $url_base) {
    $category = $version;

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $contents = str_replace("\n", ' ', file_get_contents($url_base.'modindex.html'));

    if( preg_match_all('/(?:<td>(?:&nbsp;&nbsp;&nbsp;)?      <a href="(.+?)"><tt class="xref">(.+?)<\/tt><\/a>(?: <em>.+?<\/em>)?<\/td><td>)|(?:     <tt class="xref">(.+?)<\/tt><\/td><td>)/', $contents, $matches) &&
        preg_match_all('/<em>(.*?)<\/em><\/td><\/tr>/', $contents, $desc_matches) ) {
      for( $index = 0; $index < count($matches[1]); ++$index ) {
        $link = $url_base.$matches[1][$index];
        $name = $matches[2][$index];

        if( !$name ) {
          $link = '';
          $name = $matches[3][$index];
        }

        $desc = $desc_matches[1][$index];

        if( $createFunctions ) {
          if( $link ) {
            $hierarchy_name = implode('', array_slice(explode('.', $name), 0, 1));
            $hierarchy = $this->getHierarchiesModel()->fetchByName($category_id, 1, $hierarchy_name);
            if( $hierarchy ) {
              $this->view->results .= 'Adding '.$name."\n";
              $this->view->results .= $link."\n";
              $this->view->results .= $desc."\n\n";
              $this->getFunctionsModel()->insertOrUpdateFunction(array(
                'category' => $category_id,
                'hierarchy' => $hierarchy,
                'name' => $name,
                'url' => $link,
                'short_description' => $desc,
                'scrapeable' => 1
              ));
            }
          }
        } else {
          if( strpos($name, '.') === false ) {
            $this->view->results .= $this->getHierarchiesModel()->insert(
              $category_id,
              1,
              $name,
              $link,
              1)."\n";
          }
        }
      }
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
