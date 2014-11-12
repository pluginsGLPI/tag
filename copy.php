
function plugin_tag_addLeftJoin($itemtype, $ref_table, $new_table, $linkfield,
      &$already_link_tables) {
   
   switch ($itemtype) { //echo $new_table.".".$linkfield."<br/>";
      default :
         return "LEFT JOIN `glpi_plugin_tag_etiquetteitems` ON (`glpi_tickets`.`id` = `glpi_plugin_tag_etiquetteitems`.`glpiobject_id`) 
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
