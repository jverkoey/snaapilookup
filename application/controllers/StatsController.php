<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class StatsController extends SnaapiController {

  public function indexAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      echo '<pre>';

      $table = $this->getHierarchiesModel()->getTable();
      $info = $table->info();
      $db = $table->getAdapter();
      $start_day = 4;
      $start_month = 3;
      $start_year = 2009;
      $start_hour = 16;
      $start_minute = 0;

      $time = strtotime($start_month.'/'.$start_day.'/'.$start_year.' '.$start_hour.':'.$start_minute.':00');

      while( $time < time() ) {
        $stamp = date('Y-m-d H:i:s', $time);
        $sql = 'SELECT COUNT(*) as n FROM functions WHERE time_added < "'.$stamp.'"';
        $result = $db->query($sql)->fetchAll();
        echo $stamp.','.$result[0]['n']."\n";
        $time += 60 * 60;
      }

      $this->_helper->viewRenderer->setNoRender();
    } else {
      $this->_forward('error', 'error');
    }
  }

}
