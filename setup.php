<?php
/**
 * Check plugin's config before activation
 */
function plugin_tag_check_config($verbose=false) {
   return true;
}

function getBlacklistItemtype() {
   return array('Tag');
}

function plugin_init_tag() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['tag'] = true;

   Plugin::registerClass('PluginTagTagItem',
            array('addtabon' => array('PluginTagTag')));

   $plugin = new Plugin();
   if ($plugin->isInstalled("tag") && $plugin->isActivated("tag")) {

      // add link on plugin name in Configuration > Plugin
      $PLUGIN_HOOKS['config_page']['tag'] = "front/tag.php";

      // Plugin uninstall : after uninstall action

      $UNINSTALL_TYPES = array('Computer', 'Monitor', 'NetworkEquipment',
                           'Peripheral', 'Phone', 'Printer');
      foreach ($UNINSTALL_TYPES as $uninstall_itemtype) {
         $PLUGIN_HOOKS['plugin_uninstall_after']['tag'][$uninstall_itemtype] = 'plugin_uninstall_after_tag';
      }

      $PLUGIN_HOOKS['add_javascript']['tag'][] = 'js/scripts.js';
   }
   
   // only on itemtype form
   if (preg_match_all("/.*\/(.*)\.form\.php/", $_SERVER['REQUEST_URI'], $matches) !== false) {

      if (strpos($_SERVER['REQUEST_URI'], "/front/dropdown.php") === false && 
         strpos($_SERVER['REQUEST_URI'], ".form.php?") !== false && 
         strpos($_SERVER['REQUEST_URI'], "id=-1") === false && //for Computer
         strpos($_SERVER['REQUEST_URI'], "withtemplate=") === false && //exclude template
         strpos($_SERVER['REQUEST_URI'], "?new=1") === false && //for exemple : for checklistconfig in plugin resources
         strpos($_SERVER['REQUEST_URI'], "popup=1&rand=") === false && //item no exist
         strpos($_SERVER['REQUEST_URI'], "/front/queuedmail") === false && //quemail items are temporary
         strpos($_SERVER['REQUEST_URI'], "plugins/tag/front/tag.form.php") === false && 
         strpos($_SERVER['REQUEST_URI'], "plugins/tag/front/tagtype") === false && 
         strpos($_SERVER['REQUEST_URI'], "plugins/datainjection/front/model.form.php") === false &&
         strpos($_SERVER['REQUEST_URI'], "plugins/webservices/front/client.form.php?new=1") === false &&
         strpos($_SERVER['REQUEST_URI'], "plugins/printercounters/") === false &&
         isset ($_SESSION["glpiroot"]) && 
         strpos($_SERVER['REQUEST_URI'], $_SESSION["glpiroot"]."/front/reservation.form.php") === false && 
         strpos($_SERVER['REQUEST_URI'], $_SESSION["glpiroot"]."/front/config.form.php") === false) { //for ?forcetab=PluginBehaviorsConfig%241
         if (Session::haveRight("itilcategory", READ)) {
            $PLUGIN_HOOKS['add_javascript']['tag'][] = 'js/show_tags.js';
         }
      }

      if (isset($matches[1][0])) {
         $itemtype = $matches[1][0];

         if (preg_match_all("/plugins\/(.*)\//U", $_SERVER['REQUEST_URI'], $matches_plugin) !== false) {
            if (isset($matches_plugin[1][0])) {
               $itemtype = "Plugin" . ucfirst($matches_plugin[1][0]) . ucfirst($itemtype);
            }
         }

         // stop on blaclisted itemtype
         if (in_array($itemtype, array_map('strtolower', getBlacklistItemtype()))) {
            return '';
         }

         if (class_exists($itemtype)) {
            
            //normalize classname case
            $obj = new $itemtype();
            $itemtype = get_class($obj);

            // Tag have no tag associated
            if ($itemtype != 'PluginTagTag') {
               $PLUGIN_HOOKS['pre_item_update']['tag'][$itemtype] = 'plugin_pre_item_update_tag';
               $PLUGIN_HOOKS['pre_item_purge']['tag'][$itemtype]  = 'plugin_pre_item_purge_tag';
            }
         }
      }
   }
}

function plugin_version_tag() {
   return array('name'       => __('Tag Management', 'tag'),
            'version'        => '0.90-1.1',
            'author'         => 'Emmanuel Haguet (Teclib\') - Ludovic Dupont (Infotel conseil)',
            'homepage'       => 'http://www.teclib.com',
            'license'        => '<a href="../plugins/tag/LICENSE" target="_blank">GPLv2+</a>',
            'minGlpiVersion' => "0.90");
}

/**
 * Check plugin's prerequisites before installation
 */
function plugin_tag_check_prerequisites() {
   if (version_compare(GLPI_VERSION,'0.90','lt')) {
      echo __('This plugin requires GLPI >= 0.90');
   } else {
      return true;
   }
   return false;
}