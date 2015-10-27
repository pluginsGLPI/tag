<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class PluginTagTagtype
class PluginTagTagtype extends CommonDropdown {

   static function getTypeName($nb=0) {
      return _n('Tag type', 'Tag types', $nb, 'tag');
   }

   public static function install(Migration $migration) {
      $table = getTableForItemType(__CLASS__);
      
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
						  `comment` text COLLATE utf8_unicode_ci,
						  PRIMARY KEY (`id`),
						  KEY `name` (`name`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
      }
      return true;
   }

   public static function uninstall() {
      $query = "DROP TABLE IF EXISTS `" . getTableForItemType(__CLASS__) . "`";
      return $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
   }

}
