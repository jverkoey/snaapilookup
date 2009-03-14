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

  SELECT @parentRight := rgt FROM `hierarchies` WHERE category = 32 AND id = 12;

  UPDATE hierarchies SET rgt = rgt + 2 WHERE rgt >= @parentRight AND category = 32;
  UPDATE hierarchies SET lft = lft + 2 WHERE lft > @parentRight AND category = 32;

  INSERT INTO hierarchies( category, lft, rgt, scrapeable, name, source_url ) VALUES( 
    32, @parentRight, @parentRight + 1, 0,
    'Draggable',
    'http://docs.jquery.com/UI/API/1.7/Draggable');

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