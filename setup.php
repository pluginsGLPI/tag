<?php
function plugin_version_tag() {
   return array('name'       => __('Tag Management', 'tag'),
            'version'        => '1.0',
            'author'         => 'Emmanuel Haguet - <a href="http://www.teclib.com">Teclib\'</a>',
            'homepage'       => 'http://www.teclib.com',
            'license'        => '',
            'minGlpiVersion' => "0.84");
}

/**
 * Check plugin's prerequisites before installation
 */
function plugin_tag_check_prerequisites() {
   if (version_compare(GLPI_VERSION,'0.84','lt') || version_compare(GLPI_VERSION,'0.85','ge')) {
      echo __('This plugin requires GLPI >= 0.84 and GLPI < 0.85', 'tag');
   } else {
      return true;
   }
   return false;
}

/**
 * Check plugin's config before activation
 */
function plugin_tag_check_config($verbose=false) {
   return true;
}

function getBlacklistItemtype() {
   return array('KnowbaseItem', 'Tag');
}

function plugin_init_tag() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['tag'] = true;

   Plugin::registerClass('PluginTagTagItem',
            array('addtabon' => array('PluginTagTag')));

   // add link on plugin name in configuration > plugin 
   $PLUGIN_HOOKS['config_page']['tag'] = "front/tag.php";

   // charge chosen css when needed
   $PLUGIN_HOOKS['add_css']['tag'][] = "tag.css";
   $PLUGIN_HOOKS['add_css']['tag'][] = "lib/chosen/chosen.css";
   
   $PLUGIN_HOOKS['add_javascript']['tag'][] = 'lib/chosen/chosen.native.js';
   
   // for choise color of a tag
   if (strpos($_SERVER['REQUEST_URI'], "plugins/tag/front/tag.form.php") !== false) {
      $PLUGIN_HOOKS['add_javascript']['tag'][]    = 'lib/colortools/ext.ux.color3.js';
      $PLUGIN_HOOKS['add_css']['tag'][]           = 'lib/colortools/ext.ux.color3.css';
   }

   // only on itemtype form
   if (preg_match_all("/.*\/(.*)\.form\.php/", $_SERVER['REQUEST_URI'], $matches) !== false) {

      if (strpos($_SERVER['REQUEST_URI'], "/front/dropdown.php") === false && 
         strpos($_SERVER['REQUEST_URI'], ".form.php?") !== false && 
         strpos($_SERVER['REQUEST_URI'], "id=-1") === false && //for Computer
         strpos($_SERVER['REQUEST_URI'], "withtemplate=") === false && //exclude template
         strpos($_SERVER['REQUEST_URI'], "?new=1") === false && //for exemple : for checklistconfig in plugin resources
         strpos($_SERVER['REQUEST_URI'], "popup=1&rand=") === false && //item no exist
         strpos($_SERVER['REQUEST_URI'], "plugins/tag/front/tag.form.php") === false && 
         strpos($_SERVER['REQUEST_URI'], "plugins/datainjection/front/model.form.php") === false &&
         strpos($_SERVER['REQUEST_URI'], "plugins/webservices/front/client.form.php?new=1") === false &&
         strpos($_SERVER['REQUEST_URI'], "plugins/printercounters/") === false &&
         isset ($_SESSION["glpiroot"]) && 
         strpos($_SERVER['REQUEST_URI'], $_SESSION["glpiroot"]."/front/reservation.form.php") === false && 
         strpos($_SERVER['REQUEST_URI'], $_SESSION["glpiroot"]."/front/config.form.php") === false) { //for ?forcetab=PluginBehaviorsConfig%241
         if (Session::haveRight("entity_dropdown", "r")) {
            $PLUGIN_HOOKS['add_javascript']['tag'][] = 'js/show_tags.js.php';
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
            $obj = new $itemtype;
            $itemtype = get_class($obj);

            // Tag have no tag associated
            if ($itemtype != 'PluginTagTag') {
               $PLUGIN_HOOKS['pre_item_update']['tag'][$itemtype] = 'plugin_pre_item_update_tag';
               $PLUGIN_HOOKS['pre_item_purge']['tag'][$itemtype] = 'plugin_pre_item_purge_tag';
            }
         }
      }
   }
}
