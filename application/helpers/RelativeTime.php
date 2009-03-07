<?php

class Fur_View_Helper_RelativeTime extends Zend_View_Helper_Abstract {

  public function relativeTime() {
    return $this;
  }

  public function sqlDateRelativeToNow($date) {
    // sql dates are in the form: YYYY-MM-DD
    $start = strtotime($date);
    $end = strtotime(date('Y-m-d', time()));
    $seconds = ($end - $start);

    $days = intval($seconds / 60 / 60 / 24);
    if( $days == 0 )      return 'less than a day';
    if( $days == 1 )      return 'a day';
    if( $days == 2 )      return 'a couple days';
    if( $days < 7 )       return $days.' days';

    $weeks = intval($seconds / 60 / 60 / 24 / 7);
    if( $weeks == 1 )     return 'a week';
    if( $weeks == 2 )     return 'a couple weeks';
    if( $weeks < 4 )      return $weeks.' weeks';

    $months = intval($seconds / 60 / 60 / 24 / 7 / 4);
    if( $months == 1 )     return 'a month';
    if( $months == 2 )     return 'a couple months';
    if( $months <= 11 )    return $months.' months';
    if( $days < 365 )      return 'almost a year';

    $years = intval($seconds / 60 / 60 / 24 / 365);
    if( $years == 1 )      return 'a year';
    if( $years == 2 )      return 'a couple years';
    if( $years >= 3 )      return $years.' years';

    return 'quite a while';
  }

}
