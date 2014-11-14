<?php
function plugin_version_tag() {
   return array('name'       => __('Gestion des tags', 'tag'),
            'version'        => '1.0',
            'author'         => 'Emmanuel Haguet <a href="http://www.teclib.com">Teclib\'</a>',
            'homepage'       => 'http://www.teclib.com',
            'license'        => '',
            'minGlpiVersion' => "0.84");
}

/**
 * Check plugin's prerequisites before installation
 */
function plugin_tag_check_prerequisites() {
   if (version_compare(GLPI_VERSION,'0.84','lt') || version_compare(GLPI_VERSION,'0.85','ge')) {
      echo __('This plugin requires GLPI >= 0.84 and GLPI < 0.85', 'formcreator');
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

function getItemtypes() {
   return array('Computer', 'Monitor', 'Software', 'Networkequipment', 'Peripheral', 'Printer', 
               'Cartridgeitem', 'Consumableitem', 'Phone', 'Ticket', 'Problem', 'TicketRecurrent', 
               'Budget', 'Supplier', 'Contact', 'Contract', 'Document', 'Notes', 'RSSFeed', 'User',
               'Group', 'Entity', 'Profile', ); //, 'KnowbaseItem'
}

function plugin_init_tag() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['tag'] = true;
   
   //if (in_array($itemtype, getItemtypes()))
   if (strpos($_SERVER['REQUEST_URI'], "/plugins/") === false
      && strpos($_SERVER['REQUEST_URI'], ".form.php?id=") !== false
      && strpos($_SERVER['REQUEST_URI'], "id=-1") === false) { //line/condition for Computer
      $PLUGIN_HOOKS['add_javascript']['tag'] = array('lib/chosen/chosen.native.min.js', 'js/show_tags.js');
      $PLUGIN_HOOKS['add_css']['tag'][] = "lib/chosen/chosen.css";
   } elseif (strpos($_SERVER['REQUEST_URI'], "/front/ticket.php") !== false) {
      $PLUGIN_HOOKS['add_javascript']['tag'] = array('lib/chosen/chosen.native.min.js');
      $PLUGIN_HOOKS['add_css']['tag'][] = "lib/chosen/chosen.css";
   }
   
   //Plugin::registerClass('PluginTagEtiquetteItem',
   //         array('addtabon' => PluginTagEtiquetteItem::getTabNameForItem()) //getTabNameForItem //showForm($ID, $options=array()) {
   //         );
   
   Plugin::registerClass('PluginTagEtiquetteItem',
            array('addtabon' => array('PluginTagEtiquette')));
    
   
   $itemtypes = getItemtypes(); //TODO
   foreach ($itemtypes as $itemtype) {
      $PLUGIN_HOOKS['pre_item_update']['tag'][$itemtype] = 'plugin_pre_item_update_tag';
      //$PLUGIN_HOOKS['pre_item_purge']['tag'][$itemtype]  = 'plugin_pre_item_purge_tag';
   }
   
   /*
   $PLUGIN_HOOKS['item_update']['tag'] = 'plugin_item_update_tag';
   $PLUGIN_HOOKS['item_delete']['tag'] = 'plugin_item_delete_tag';
   */
   //$PLUGIN_HOOKS['item_purge']['tag'] = 'plugin_item_purge_tag';
   //$PLUGIN_HOOKS['item_restore']['tag'] = 'plugin_item_restore_tag';

}
