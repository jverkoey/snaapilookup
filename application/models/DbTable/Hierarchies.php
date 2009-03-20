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

  SELECT @parentRight := rgt FROM `hierarchies` WHERE category = 34 AND id = 2;

  UPDATE hierarchies SET rgt = rgt + 2 WHERE rgt >= @parentRight AND category = 34;
  UPDATE hierarchies SET lft = lft + 2 WHERE lft > @parentRight AND category = 34;

  INSERT INTO hierarchies( category, lft, rgt, scrapeable, name, source_url ) VALUES( 
    34, @parentRight, @parentRight + 1, 0,
'Status Methods',
'http://apiwiki.twitter.com/REST+API+Documentation#StatusMethods'
);

  UNLOCK TABLES;
  
  
  LOCK TABLE hierarchies WRITE;

  SELECT @parentRight := rgt FROM `hierarchies` WHERE category = 25 AND id = 7;

  UPDATE hierarchies SET rgt = rgt + 2 WHERE rgt >= @parentRight AND category = 25;
  UPDATE hierarchies SET lft = lft + 2 WHERE lft > @parentRight AND category = 25;

  INSERT INTO hierarchies( category, lft, rgt, scrapeable, name, source_url ) VALUES( 
    25, @parentRight, @parentRight + 1, 1,
    're class',
    'http://docs.python.org/library/re.html#module-contents');

  UNLOCK TABLES;

*/