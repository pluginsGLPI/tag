<?php
function plugin_version_tag() {
   return array('name'       => __('Gestion des tags', 'tag'), //_n('Form', 'Forms', 2, 'formcreator'),
            'version'        => '0.1',
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
               'Group', 'Entity', 'Profile');
}

function plugin_init_tag() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['tag'] = true;
   
   if (strpos($_SERVER['REQUEST_URI'], "/plugins/") === false
      && strpos($_SERVER['REQUEST_URI'], ".form.php?id=") !== false
      && strpos($_SERVER['REQUEST_URI'], "id=-1") === false) { //this line is for Computer
      $PLUGIN_HOOKS['add_javascript']['tag'] = array('js/jquery-1.11.1.min.js',
       'js/chosen/chosen.native.min.js', 'js/show_tags.js');
   } elseif (strpos($_SERVER['REQUEST_URI'], "/front/ticket.php") !== false) {
      $PLUGIN_HOOKS['add_javascript']['tag'] = array('js/jquery-1.11.1.min.js', 
      'js/chosen/chosen.native.min.js');
   }
   
   $PLUGIN_HOOKS['add_css']['tag'][] = "js/chosen/chosen.css";
   
   
   //$PLUGIN_HOOKS['item_add']['tag']    = 'plugin_item_add_tag';
   
   //$PLUGIN_HOOKS['item_add']['tag'] = array(
   //      'User'  => 'plugin_calltoclick_item_add_user'
   //);
   
   $itemtypes = getItemtypes(); //TODO
   foreach ($itemtypes as $itemtype) {
      //$PLUGIN_HOOKS['item_add']['tag'][$itemtype]        = 'plugin_item_add_tag';
      $PLUGIN_HOOKS['pre_item_update']['tag'][$itemtype]='plugin_pre_item_update_tag';
      //$PLUGIN_HOOKS['pre_item_purge']['tag'][$itemtype]  = array(//"PluginFieldsContainer",
      //                                                                    "preItemPurge");
   }
   
   /*
   $PLUGIN_HOOKS['item_update']['tag'] = 'plugin_item_update_tag';
   $PLUGIN_HOOKS['item_delete']['tag'] = 'plugin_item_delete_tag';
   */
   //$PLUGIN_HOOKS['item_purge']['tag'] = 'plugin_item_purge_tag';
   //$PLUGIN_HOOKS['item_restore']['tag'] = 'plugin_item_restore_tag';

   // Add a link in the main menu plugins for technician and admin panel
   $PLUGIN_HOOKS['menu_entry']['tag'] = 'front/formlist.php';

   // Config page
   $links  = array();
   $plugin = new Plugin();
   if (Session::haveRight('config','w') && $plugin->isActivated("tag")) {
      $PLUGIN_HOOKS['config_page']['tag'] = 'front/form.php';
      $links['config'] = '/plugins/formcreator/front/form.php';
      $links['add']    = '/plugins/formcreator/front/form.form.php';
   }

   // Set options for pages (title, links, buttons...)
   $links['search'] = '/plugins/tag/front/formlist.php';
   $PLUGIN_HOOKS['submenu_entry']['formcreator']['options'] = array(
      'config'       => array('title'  => __('Setup'),
                              'page'   => '/plugins/tag/front/form.php',
                              'links'  => $links),
      'options'      => array('title'  => __('Tags'), #_n('Form', 'Forms', 2, 'formcreator'),
                              'links'  => $links),
   );
   // Load field class and all its method to manage fields
   Plugin::registerClass('PluginTagFields');
}
