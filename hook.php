<?php

// Plugin hook after *Uninstall*
function plugin_uninstall_after_tag($item) {
   $tagitem = new PluginTagTagItem();
   $tagitem->deleteByCriteria(array('itemtype' => $item->getType(),
                                    'items_id' => $item->getID()
                                    )
   );
}

function plugin_pre_item_update_tag($parm) {
   global $DB;
   
   if (isset($_REQUEST['plugin_tag_tag_id']) && isset($_REQUEST['plugin_tag_tag_itemtype'])) {

      $already_present = array();

      $itemtype = PluginTagTag::getItemtype($_REQUEST['plugin_tag_tag_itemtype'], $_REQUEST['plugin_tag_tag_id']);

      $query_part = "`items_id`=".$_REQUEST['plugin_tag_tag_id']." 
                     AND `itemtype` = '".$itemtype."'";

      $item = new PluginTagTagItem();
      foreach ($item->find($query_part) as $indb) {
         if (isset($_REQUEST["_plugin_tag_tag_values"]) &&
               in_array($indb["plugin_tag_tags_id"], $_REQUEST["_plugin_tag_tag_values"])) {
            $already_present[] = $indb["plugin_tag_tags_id"];
         } else {
            $item->delete(array("id" => $indb['id']));
         }
         
      }
   
      if (isset($_REQUEST["_plugin_tag_tag_values"])) {
         foreach ($_REQUEST["_plugin_tag_tag_values"] as $tag_id) {
            if (!in_array($tag_id, $already_present)) {
               $item->add(array(
                     'plugin_tag_tags_id' => $tag_id,
                     'items_id' => $_REQUEST['plugin_tag_tag_id'],
                     'itemtype' => ucfirst($itemtype), //get_class($parm)
               ));
            }
         }
      }
   }
   return $parm;
}

function plugin_pre_item_purge_tag($object) {
   
   if (isset($object->input["plugin_tag_tag_itemtype"])) { // Example : TicketTask no have tag
      $tagitem = new PluginTagTagItem();
      $result = $tagitem->deleteByCriteria(array(
         "items_id" => $object->fields["id"],
         "itemtype" => ucfirst($object->input["plugin_tag_tag_itemtype"]),
      ));
   }
}

function plugin_tag_getAddSearchOptions($itemtype) {
   $sopt = array();
   
   if (! Session::haveRight("itilcategory", READ)) {
      return array();
   }
   
   if ($itemtype === 'PluginTagTag' 
         || $itemtype === 'CronTask' //Because no have already tag in CronTask interface
         || $itemtype === 'PluginFormcreatorFormanswer' //No have tag associated
         || $itemtype === 'QueuedMail'
         || strpos($itemtype, 'PluginPrintercounters') !== false) {
      return array();
   }
   
   $rng1 = PluginTagTag::TAG_SEARCH_NUM;
   //$sopt[strtolower($itemtype)] = ''; //self::getTypeName(2);

   $sopt[$rng1]['table']         = getTableForItemType('PluginTagTag');
   $sopt[$rng1]['field']         = 'name';
   $sopt[$rng1]['name']          = PluginTagTag::getTypeName(2);
   $sopt[$rng1]['datatype']      = 'string';
   $sopt[$rng1]['searchtype']    = "contains";
   $sopt[$rng1]['massiveaction'] = false;
   $sopt[$rng1]['forcegroupby']  = true;
   $sopt[$rng1]['usehaving']     = true;
   $sopt[$rng1]['joinparams']    = array('beforejoin' => array('table'      => 'glpi_plugin_tag_tagitems',
                                                               'joinparams' => array('jointype' => "itemtype_item")));
   
   //array('jointype' => "itemtype_item");
   
   return $sopt;
}

function plugin_tag_giveItem($type, $field, $data, $num, $linkfield = "") {
   switch ($field) {
      case PluginTagTag::TAG_SEARCH_NUM: //Note : can declare a const for "10500"
         $out = '<div id="s2id_tag_select" class="select2-container select2-container-multi chosen-select-no-results" style="width: 100%;">
                 <ul class="select2-choices">';
         $separator = '';
         $plugintagtag = new PluginTagTag();
         
         foreach ($data[$num] as $tag) {
            if (isset($tag['id']) && isset($tag['name'])) {
               $id    = $tag['id'];
               $name  = $tag['name'];
               
               $plugintagtag->getFromDB($id);
               $color = $plugintagtag->fields["color"];

               $style = "";
               if (!empty($color)) {
                  $style .= "border-width:2px; border-color:$color;";
               }

               $out .= '<li class="select2-search-choice" style="padding-left:5px;'.$style.'">'.$separator.$name.'</li>';
               $separator = '<span style="display:none">, </span>'; //For export (CSV, PDF) of GLPI core
            }
         }
         $out .= '</ul></div>';
         return $out;
         break;
      case 6: //Type de tag
         if ($type == 'PluginTagTag') { //for future
            $key = $data[$num][0]['name'];
            if (! is_array(json_decode($key))) { //for when $key value is "0"
               return __("None");
            }

            $itemtype_names = array();
            foreach (json_decode($key) as $itemtype) {
               $item = getItemForItemtype($itemtype);
               $itemtype_names[] = $item->getTypeName();
            }
            $out = implode(", ", $itemtype_names);
            return $out;
         }
   }
   
   return "";
}

function plugin_tag_addHaving($link, $nott, $type, $id, $val, $num) {

   $values = explode(",", $val);
   $where = "$link `ITEM_$num` LIKE '%".$values[0]."%'";
   array_shift($values);
   foreach ($values as $value) {
      $value = trim($value);
      $where .= " OR `ITEM_$num` LIKE '%$value%'";
   }
   return $where;
}

function plugin_tag_addWhere($link, $nott, $itemtype, $ID, $val, $searchtype) {
   // "Types d'élément associé"
   if ($itemtype == 'PluginTagTag' && $ID == 6) {
      switch ($searchtype) {
         case 'equals':
            return "`glpi_plugin_tag_tags`.`type_menu` LIKE '%\"$val\"%'";

         case 'notequals':
            return "`glpi_plugin_tag_tags`.`type_menu` NOT LIKE '%\"$val\"%'";
      }
   }

   return "";
}


/**
 * Define Dropdown tables to be manage in GLPI :
 */
function plugin_tag_getDropdown() {
   return array('PluginTagTag' => PluginTagTag::getTypeName(2));
}

/**
 * Install all necessary elements for the plugin
 *
 * @return boolean (True if success)
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
