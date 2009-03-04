<?php

/**
 * This is the DbTable class for the hierarchies table.
 */
class Model_DbTable_Hierarchies extends Zend_Db_Table_Abstract {

  /** Table name */
  protected $_name    = 'hierarchies';
  protected $_primary = array('category', 'id');

}

/*
  LOCK TABLE hierarchies WRITE;# MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).


  SELECT @parentRight := rgt FROM `hierarchies` WHERE category = 9 AND id = 2;# Rows: 1
  # Rows: 1
  # Rows: 1
  # Rows: 1
  # Rows: 1


  UPDATE hierarchies SET rgt = rgt + 2 WHERE rgt >= @parentRight;# 3 row(s) affected.
  # 3 row(s) affected.
  # 3 row(s) affected.
  # 3 row(s) affected.
  # 3 row(s) affected.

  UPDATE hierarchies SET lft = lft + 2 WHERE lft > @parentRight;# MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).

  INSERT INTO hierarchies( category, lft, rgt, scrapeable, name, source_url ) VALUES( 9, @parentRight, @parentRight + 1, 1, 'xattr', 'http://us3.php.net/manual/en/ref.xattr.php');# 1 row(s) affected.
  # 1 row(s) affected.
  # 1 row(s) affected.
  # 1 row(s) affected.
  # 1 row(s) affected.


  UNLOCK TABLES;# MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).
  # MySQL returned an empty result set (i.e. zero rows).

*/