<?php

function plugin_tag_getAddSearchOptions($itemtype) {
   $sopt = array();
   
   //Reserved Range 10500-10530
   $rng1 = 10500;
   $sopt[strtolower($itemtype)] = //self::getTypeName(2);

   $sopt[$rng1]['table']     = 'glpi_plugin_tag_etiquettes'; //'glpi_plugin_tag_etiquettes';
   $sopt[$rng1]['field']     = 'name';
   $sopt[$rng1]['name']      = 'Tag';
   $sopt[$rng1]['datatype']  = 'string';
   $sopt[$rng1]['searchtype'] = "contains";
   $sopt[$rng1]['massiveaction'] = false;
   $sopt[$rng1]['forcegroupby']  = true;
   $sopt[$rng1]['usehaving']     = true;
   $sopt[$rng1]['joinparams']    = array('beforejoin' => array('table'      => 'glpi_plugin_tag_etiquetteitems',
                                                               'joinparams' => array('jointype' => "itemtype_item")));
   
   
   //array('jointype' => "itemtype_item");
   
   return $sopt;
}

function plugin_tag_giveItem($type, $field, $data, $num, $linkfield = "") {
   global $CFG_GLPI, $INFOFORM_PAGES;

   Toolbox::logDebug("giveItem : ".$field);
   switch ($field) {
      case "glpi_plugin_example.name" :
         $out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data['ID']."\">";
         $out.= $data["ITEM_$num"];
         if ($CFG_GLPI["view_ID"]||empty($data["ITEM_$num"])) $out.= " (".$data["ID"].")";
         $out.= "</a>";
         return $out;
         break;
      case "10500":
         $tab = array();
         //tag3$$3$$$$tag2$$2
         $tags = explode("$$$$", $data["ITEM_$num"]);
         //array(tag3$$3, tag2$$2)
         foreach ($tags as $tag) {
            $tmp = explode("$$", $tag);
            $tab[] = $tmp[0];
         }
         $out = "<span class='tag_list'>" . implode(",", $tab). "</span>";
         return $out;
         break;
   }
   return "";
}

/*
function casParticulier() {
   if (isset($_REQUEST["contains"]) && isset($_REQUEST["field"])) {
      foreach ($_REQUEST["field"] as $index => $field) {
         if ($field == "10500" && $_REQUEST["contains"][$index] == "") {
            return false;
         }
      }
   }
   return false;
}

function plugin_tag_addSelect($type, $id, $num) {
   if (casParticulier()) {
      return "'' as ITEM_$num, ";
   }
   return "GROUP_CONCAT(`glpi_plugin_tag_etiquettes`.`name`) AS ITEM_$num, ";
}

function plugin_tag_addLeftJoin($itemtype, $ref_table, $new_table, $linkfield,
      &$already_link_tables) {
   
   return "LEFT JOIN `glpi_plugin_tag_etiquetteitems` ON (`glpi_".strtolower($itemtype)."s`.`id` = `glpi_plugin_tag_etiquetteitems`.`items_id`) 
            LEFT JOIN `glpi_plugin_tag_etiquettes` ON (`glpi_plugin_tag_etiquettes`.`id` = `glpi_plugin_tag_etiquetteitems`.`plugin_tag_etiquettes_id`) ";
}

function plugin_tag_addWhere($link, $nott, $type, $id, $val) {
      
   if ($link == ' OR') {
      $link = ' AND';
   }
  
   return "$link ( `glpi_plugin_tag_etiquettes`.`entities_id` IS NOT NULL)";
}
*/
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
   return array('PluginTagEtiquette'   => __('Etiquette', 'tag'),
         'PluginEtiquetteItem' => _n('Etiquette item', 'Etiquette items', 2, 'formcreator'),
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
         $classname = 'PluginFormcreator' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'uninstall')) {
            $classname::uninstall();
         }
      }
   }
   return true;
}
