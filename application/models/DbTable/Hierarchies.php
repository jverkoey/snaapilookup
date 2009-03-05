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

  LOCK TABLE hierarchies WRITE;

  SELECT @parentRight := rgt FROM `hierarchies` WHERE category = 9 AND id = 1;

  UPDATE hierarchies SET rgt = rgt + 2 WHERE rgt >= @parentRight;
  UPDATE hierarchies SET lft = lft + 2 WHERE lft > @parentRight;

  INSERT INTO hierarchies( category, lft, rgt, scrapeable, name, source_url ) VALUES( 
    9, @parentRight, @parentRight + 1, 0,
    'XML Manipulation',
    'http://us3.php.net/manual/en/refs.xml.php');

  UNLOCK TABLES;
  
  
  LOCK TABLE hierarchies WRITE;

  SELECT @parentRight := rgt FROM `hierarchies` WHERE category = 9 AND id = 205;

  UPDATE hierarchies SET rgt = rgt + 2 WHERE rgt >= @parentRight;
  UPDATE hierarchies SET lft = lft + 2 WHERE lft > @parentRight;

  INSERT INTO hierarchies( category, lft, rgt, scrapeable, name, source_url ) VALUES( 
    9, @parentRight, @parentRight + 1, 1,
    'DOM',
    'http://us3.php.net/manual/en/ref.dom.php');

  UNLOCK TABLES;

*/