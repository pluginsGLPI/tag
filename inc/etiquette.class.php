<?php
class PluginTagEtiquette extends CommonDropdown {

   public static function getTypeName($nb=1) {
      return _n('Etiquette', 'Etiquettes', 'tag'); //_n('Header', 'Headers', $nb, 'formcreator');
   }
   
   public static function getTagName($id_etiquette) {
      $etiquette_obj = new self();
      $etiquette_obj->getFromDB($id_etiquette);
      return $etiquette_obj->fields['name'];
   }

   public function showForm($ID, $options = array()) {
      if (!$this->isNewID($ID)) {
         $this->check($ID, 'r');
      } else {
         $this->check(-1, 'w');
      }
      $options['colspan'] = 2;
      $options['target']  = Toolbox::getItemTypeFormURL(__CLASS__);
      $this->showTabs($options);
      $this->showFormHeader($options);
      echo '<table class="tab_cadre_fixe">';

      echo "<tr class='line0'><td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line1'><td>" . __('Content') . "</td>";
      echo "<td>";
      echo "<textarea name='comment' id ='comment' >" . $this->fields['comment'] . "</textarea>";
      Html::initEditorSystem('comment');
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }

   public static function install(Migration $migration) {
      $table = getTableForItemType(__CLASS__);
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `entities_id` int(11) NOT NULL DEFAULT '0',
                     `is_recursive` tinyint(1) NOT NULL DEFAULT '1',
                     `name` varchar(255) NOT NULL DEFAULT '',
                     `comment` text collate utf8_unicode_ci,
                     PRIMARY KEY (`id`),
                     KEY `name` (`name`)
                     ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
      }
      
      return true;
      }

   public static function uninstall() {
      $query = "DROP TABLE IF EXISTS `" . getTableForItemType(__CLASS__) . "`";
      return $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
   }
   
   public function cleanDBonPurge() { //nom de la fonction correct ?
      Toolbox::logDebug("cleanDBonPurge()"); //DEBUG
      //TODO : Coder cette partie
   }
}
