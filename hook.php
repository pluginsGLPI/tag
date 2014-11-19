<?php

function plugin_pre_item_update_tag($parm) {
   global $DB;
   
   $plugin = new Plugin();
   //if ($plugin->isActivated('tag')) {
      $item = new PluginTagTagItem();
      //Delete into base all tags :
      $DB->query("DELETE FROM `glpi_plugin_tag_tagitems`
         WHERE `items_id`=".$_REQUEST['plugin_tag_tag_id']." 
            AND `itemtype` = '".$_REQUEST['plugin_tag_tag_itemtype']."'");
      
      if (isset($_REQUEST['_plugin_tag_tag_values'])) { //if no value is selected in HTML '<select>'
         //Insert into base the tags :
         foreach ($_REQUEST['_plugin_tag_tag_values'] as $tag_id) {
            $item->add(array(
                  'plugin_tag_tags_id' => $tag_id,
                  'items_id' => $_REQUEST['plugin_tag_tag_id'],
                  'itemtype' => $_REQUEST['plugin_tag_tag_itemtype'], //get_class($parm)
            ));
         }
      }
   //}
   return $parm;
}

function plugin_tag_getAddSearchOptions($itemtype) {
   $sopt = array();
   
   if (strpos($itemtype, 'Plugin') !== false) { //'PluginTagTag'
      return $sopt;
   }
   
   $rng1 = 10500;
   //$sopt[strtolower($itemtype)] = ''; //self::getTypeName(2);

   $sopt[$rng1]['table']     = 'glpi_plugin_tag_tags';
   $sopt[$rng1]['field']     = 'name';
   $sopt[$rng1]['name']      = 'Tag';
   $sopt[$rng1]['datatype']  = 'string';
   $sopt[$rng1]['searchtype'] = "contains";
   $sopt[$rng1]['massiveaction'] = false;
   $sopt[$rng1]['forcegroupby']  = true;
   $sopt[$rng1]['usehaving']     = true;
   $sopt[$rng1]['joinparams']    = array('beforejoin' => array('table'      => 'glpi_plugin_tag_tagitems',
                                                               'joinparams' => array('jointype' => "itemtype_item")));
   
   
   //array('jointype' => "itemtype_item");
   
   return $sopt;
}

function plugin_tag_giveItem($type, $field, $data, $num, $linkfield = "") {

   if ($data["ITEM_$num"] == '') {
      return "";
   }
   
   switch ($field) {
      case "10500":
         //tag3$$3$$$$tag2$$2
         $tags = explode("$$$$", $data["ITEM_$num"]);
         //array('tag3$$3', 'tag2$$2')
         $out = '<div class="chzn-container chzn-container-multi" title="">
               <ul class="chzn-choices">';
         foreach ($tags as $tag) {
            $tmp = explode("$$", $tag);
            $out .= '<li class="search-choice"><span>'.$tmp[0].'</span></li>';
         }
         $out .= '</ul>
               </div>';
         return $out;
         break;
   }
   return "";
}

function plugin_tag_addHaving($link, $nott, $type, $id, $val, $num) {

   $valeurs = explode(",", $val);
   $out = "$link `ITEM_$num` LIKE '%".$valeurs[0]."%'";
   array_shift($valeurs);
   foreach ($valeurs as $valeur) {
      $valeur = trim($valeur);
      $out .= " AND `ITEM_$num` LIKE '%$valeur%'";
   }
   return $out;
}

/**
 * Install all necessary elements for the plugin
 *
 * @return boolean True if success
 */
function plugin_tag_install() {
   $version   = plugin_version_tag();
   $migration = new Migration($version['version']);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginTag' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'install')) {
            $classname::install($migration);
         }
      }
   }
   return true;
}

/**
 * Define Dropdown tables to be manage in GLPI :
 */
function plugin_tag_getDropdown() {
   return array('PluginTagTag'   => __('Tag', 'tag'),
         'PluginTagItem' => _n('Tag item', 'Tag items', 2, 'tag'),
   );
}

/**
 * Uninstall previously installed elements of the plugin
 *
 * @return boolean True if success
 */
function plugin_tag_uninstall() {
   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginTag' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'uninstall')) {
            $classname::uninstall();
         }
      }
   }
   return true;
}
