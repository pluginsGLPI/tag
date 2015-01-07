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
   $PLUGIN_HOOKS['add_css']['tag'][] = "lib/chosen/chosen.css";

   // only on itemtype form
   if (preg_match_all("/.*\/(.*)\.form\.php/", $_SERVER['REQUEST_URI'], $matches) !== false) {

      if (strpos($_SERVER['REQUEST_URI'], "/front/dropdown.php") === false &&
         strpos($_SERVER['REQUEST_URI'], ".form.php") !== false) {
         $PLUGIN_HOOKS['add_javascript']['tag'] = array('lib/chosen/chosen.native.js', 
                                                        'js/show_tags.js');
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

            $PLUGIN_HOOKS['pre_item_update']['tag'][$itemtype] = 'plugin_pre_item_update_tag';
         }
      }
   }
}
