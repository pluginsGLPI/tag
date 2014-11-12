<?php

function plugin_tag_getAddSearchOptions($itemtype) {
   //global $LANG;

   $sopt = array();
   //if ($itemtype == 'Ticket') {
      //Reserved Range 10500-10530
      $rng1 = 10500;
      //$table = strtolower($itemtype)."s";

      $sopt[$rng1]['table']     = 'glpi_plugin_tag_etiquettes';
      $sopt[$rng1]['field']     = 'name';
      //$sopt[$rng1]['linkfield'] = 'id';
      $sopt[$rng1]['name']      = 'Tag';
      $sopt[$rng1]['datatype']  = 'string'; //'bool';
      $sopt[$rng1]['searchtype'] = "contains";
      $sopt[$rng1]['massiveaction'] = false;

   //}
   return $sopt;
}
/*
function plugin_tag_forceGroupBy($type) {
   //return " GROUP BY `glpi_tickets`.id ";
   return true;
}*/

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
   Toolbox::logDebug("addSelect", $type, $id, $num);

   //$searchopt = &Search::getOptions($type);
   //$table = $searchopt[$id]["table"];
   //$field = $searchopt[$id]["field"];

   switch ($type) {
      default:
         if (casParticulier()) {
            return "'' as ITEM_$num, ";
         }
         return "GROUP_CONCAT(`glpi_plugin_tag_etiquettes`.`name`) AS ITEM_$num, ";
   }
}

function plugin_tag_addLeftJoin($itemtype, $ref_table, $new_table, $linkfield,
      &$already_link_tables) {
   
   switch ($itemtype) { //echo $new_table.".".$linkfield."<br/>";
      default :
         return "LEFT JOIN `glpi_plugin_tag_etiquetteitems` ON (`glpi_".strtolower($itemtype)."s`.`id` = `glpi_plugin_tag_etiquetteitems`.`items_id`) 
                  LEFT JOIN `glpi_plugin_tag_etiquettes` ON (`glpi_plugin_tag_etiquettes`.`id` = `glpi_plugin_tag_etiquetteitems`.`plugin_tag_etiquettes_id`) ";
   }
}
/*
function plugin_tag_addDefaultWhere($type) {
   return "`glpi_plugin_tag_etiquettes`.`name` IN ('tag3', 'tag2')";
   //if ($type == 'PluginFusioninventoryTaskjob') {
      return " ( select count(*) FROM `glpi_plugin_fusioninventory_taskjobstates`
         WHERE plugin_fusioninventory_taskjobs_id= `glpi_plugin_fusioninventory_taskjobs`.`id`
         AND `state`!='3' )";
   //}
}
*/

function plugin_tag_addWhere($link, $nott, $type, $id, $val) {

   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$id]["table"];
   $field = $searchopt[$id]["field"];
   
   //Toolbox::logDebug("searchopt :");
   //Toolbox::logDebug($searchopt);
   
   Toolbox::logDebug($link, $nott, $type, $id, $val);
   
   //echo "link";
   //print_r($link);
   //echo "/link";
   
   //if ($link == ' ')
      
   if ($link == ' OR') {
      $link = ' AND';
   }
   
   /*
   $ids = explode(',', $val);
   if (count($ids) >= 1) {
      return $link." `$table`.`id` IN (".implode(',', $ids).")";
   } else {
      return "";
   }*/

   switch ($type) {
      default:
         return "$link ( `glpi_plugin_tag_etiquettes`.`entities_id` IS NOT NULL)";
         return "$link `glpi_plugin_tag_etiquettes`.`name` IN ('$val')";
   }
}

/*
 function plugin_tag_addWhere($link, $nott, $type, $id, $val) {

//$searchopt = &Search::getOptions($type);
//$table = $searchopt[$id]["table"];
//$field = $searchopt[$id]["field"];

switch ($type) {
default:
return "1=1";
}
}*/
   
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

/**
 * Define Dropdown tables to be manage in GLPI :
 */
function plugin_tag_getDropdown() {
   return array('PluginTagEtiquette'   => __('Etiquette', 'tag'),
         'PluginEtiquetteItem' => _n('Etiquette item', 'Etiquette items', 2, 'formcreator'),
   );
}