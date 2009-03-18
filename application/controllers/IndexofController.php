<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class IndexofController extends SnaapiController {
  public function indexAction() {
    $category_name = $this->_request->getParam(1);

    if( $category_name ) {
      $category = $this->getCategoriesModel()->fetchCategoryInfoByName($category_name);
      if( $category ) {
        $this->view->category_name = $category['name'];
        $category['type'][0] = strtoupper($category['type'][0]);
        $this->view->category_type = $category['type'];
        $this->view->category_id = $category['id'];
        $this->_helper->viewRenderer->setRender('category');
        $this->getLogsModel()->add('indexof', $category['name']);
        return;
      } else {
        $this->getLogsModel()->add('invalidlog', $category_name);
        $this->_helper->getHelper('Redirector')
                        ->setGotoSimple('index', 'indexof');
        return;
      }
    }  
    $this->getLogsModel()->add('indices');
    $languages = $this->getCategoriesModel()->fetchAllLanguages();
    $frameworks = $this->getCategoriesModel()->fetchAllFrameworks();

    function sort_category($left, $right) {
      return strcasecmp($left['name'], $right['name']);
    }
    usort($languages, 'sort_category');
    usort($frameworks, 'sort_category');

    $this->view->languages = $languages;
    $this->view->frameworks = $frameworks;
  }

}
