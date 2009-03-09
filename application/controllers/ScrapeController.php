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

      $this->scrapePythonConstants();
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

  private function scrapePythonConstants() {
    $category = 'Python';

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

      if( strpos($source_url, '#') !== FALSE ) {
        // Cool, let's grab this section's info.
        $parts = explode('#', $source_url);
        $base_url = $parts[0];
        $block = $parts[1];
        $contents = file_get_contents($source_url);

        $start_block = strpos($contents, 'id="'.$block.'"');
        if( $start_block === FALSE ) {
          $this->view->results .= 'We couldn\'t find the block, skipping...' . "\n";
          continue;
        }

        $done = false;

        $start = strpos($contents, '<dl class="data"', $start_block);
        if( $start !== FALSE ) {
          $end = strpos($contents, '</div>', $start);
          if( $end === FALSE ) {
            $this->view->results .= 'We couldn\'t find the end of the block, skipping...' . "\n";
            continue;
          }

          $push_back = $start;
          while( ($push_back = strpos($contents, '<div', $push_back)) !== FALSE ) {
            if( $push_back > $end ) {
              break;
            }
            $push_back++;
            $end = strpos($contents, '</div>', $end + 1);
          }

          $fail = false;
          $block_data = explode('<dl class="data">', substr($contents, $start, $end-$start));
          foreach( $block_data as $entry ) {
            $entry_data = explode("\n", $entry);
            if( count($entry_data) > 1 ) {
              $name = null;
              $href = null;
              $description = null;
              if( preg_match('/<dt id="(.+?)">/', $entry_data[1], $id) ) {
                $name = $id[1];
              }
              if( preg_match('/href="(#.+?)"/', $entry_data[2], $id) ) {
                $href = $id[1];
              }
              if( preg_match('/<dd>(.+?)<\/dd>/', str_replace("\n", ' ', $entry), $id) ) {
                $description = substr($id[1], 0, strpos($id[1], '</p>'));
                $description = preg_replace('/<.+?>/', '', $description);
              }

              if( !$name || !$href ) {
                $fail = true;
                break;
              }
              $this->getFunctionsModel()->insertOrUpdateFunction(array(
                'category' => $category_id,
                'hierarchy' => $hierarchy['id'],
                'name' => $name,
                'url' => $base_url.$href,
                'short_description' => $description
              ));
              $done = true;
            }
          }
          if( $fail ) {
            continue;
          } else {  
            $done = true;
          }
        }

        if( !$done ) {
          $start = strpos($contents, '<dl class="method"', $start_block);
          if( $start !== FALSE ) {
            $end = strpos($contents, '</div>', $start);
            if( $end === FALSE ) {
              $this->view->results .= 'We couldn\'t find the end of the block, skipping...' . "\n";
              continue;
            }

            $push_back = $start;
            while( ($push_back = strpos($contents, '<div', $push_back)) !== FALSE ) {
              if( $push_back > $end ) {
                break;
              }
              $push_back++;
              $end = strpos($contents, '</div>', $end + 1);
            }

            $fail = false;
            $block_data = explode('<dl class="method">', substr($contents, $start, $end-$start));
            foreach( $block_data as $entry ) {
              $entry_data = explode("\n", $entry);
              if( count($entry_data) > 1 ) {
                $name = null;
                $href = null;
                $description = null;
                if( preg_match('/<dt id="(.+?)">/', $entry_data[1], $id) ) {
                  $name = $id[1];
                }
                if( preg_match('/href="(#.+?)"/', $entry_data[2], $id) ) {
                  $href = $id[1];
                }
                if( preg_match('/<dd>(.+?)<\/dd>/', str_replace("\n", ' ', $entry), $id) ) {
                  $description = substr($id[1], 0, strpos($id[1], '</p>'));
                  $description = preg_replace('/<.+?>/', '', $description);
                }

                $this->view->results .= $name."\n";
                $this->view->results .= $href."\n";
                $this->view->results .= $description."\n\n";

                if( !$name || !$href ) {
                  $fail = true;
                  break;
                }
                $this->getFunctionsModel()->insertOrUpdateFunction(array(
                  'category' => $category_id,
                  'hierarchy' => $hierarchy['id'],
                  'name' => $name,
                  'url' => $base_url.$href,
                  'short_description' => $description
                ));
              }
            }
            if( $fail ) {
              continue;
            } else {
              $done = true;
            }
          }
        }

        if( !$done ) {
          $start = strpos($contents, '<dl class="function"', $start_block);
          if( $start === FALSE ) {
            $this->view->results .= 'We couldn\'t find the starting entry, skipping...' . "\n";
            continue;
          }

          $end = strpos($contents, '</div>', $start);
          if( $end === FALSE ) {
            $this->view->results .= 'We couldn\'t find the end of the block, skipping...' . "\n";
            continue;
          }

          $push_back = $start;
          while( ($push_back = strpos($contents, '<div', $push_back)) !== FALSE ) {
            if( $push_back > $end ) {
              break;
            }
            $push_back++;
            $end = strpos($contents, '</div>', $end + 1);
          }

          $fail = false;
          $block_data = explode('<dl class="function">', substr($contents, $start, $end-$start));
          foreach( $block_data as $entry ) {
            $entry_data = explode("\n", $entry);
            if( count($entry_data) > 1 ) {
              $name = null;
              $href = null;
              $description = null;
              if( preg_match('/<dt id="(.+?)">/', $entry_data[1], $id) ) {
                $name = $id[1];
              }
              if( preg_match('/href="(#.+?)"/', $entry_data[2], $id) ) {
                $href = $id[1];
              }
              if( preg_match('/<dd>(.+?)<\/dd>/', str_replace("\n", ' ', $entry), $id) ) {
                $description = substr($id[1], 0, strpos($id[1], '</p>'));
                $description = preg_replace('/<.+?>/', '', $description);
              }

              if( !$name || !$href ) {
                $fail = true;
                break;
              }
              $this->getFunctionsModel()->insertOrUpdateFunction(array(
                'category' => $category_id,
                'hierarchy' => $hierarchy['id'],
                'name' => $name,
                'url' => $base_url.$href,
                'short_description' => $description
              ));
            }
          }
          if( $fail ) {
            $this->view->results .= 'We failed, skipping...' . "\n";
            continue;
          } else {
            $done = true;
          }
        }

        if( !$done ) {
          $this->view->results .= 'We couldn\'t find the start of the block, skipping...' . "\n";
          continue;
        }

      }

      $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
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
