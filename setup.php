<?php

function plugin_version_tag() {
   return array('name'       => _n('Form', 'Forms', 2, 'formcreator'),
            'version'        => '0.84-2.0',
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

function plugin_init_tag() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['formcreator'] = true;

   // Add specific CSS
   //$PLUGIN_HOOKS['add_css']['formcreator'][] = "css/styles.css";

   if (strpos($_SERVER['REQUEST_URI'], "front/helpdesk.public.php") !== false) {
      $PLUGIN_HOOKS['add_javascript']['pdf'][] = 'scripts/helpdesk.js';
   } elseif(strpos($_SERVER['REQUEST_URI'], "front/central.php") !== false) {
      $PLUGIN_HOOKS['add_javascript']['pdf'][] = 'scripts/homepage.js';
   }

   if (isset($_SESSION['glpiactiveprofile'])) {
      if ($_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
         $PLUGIN_HOOKS['add_javascript']['pdf'][] = 'scripts/helpdesk-menu.js';
      }
   }
   $PLUGIN_HOOKS['add_javascript']['formcreator'][] = 'scripts/forms-validation.js.php';

   // Add a link in the main menu plugins for technician and admin panel
   $PLUGIN_HOOKS['menu_entry']['formcreator'] = 'front/formlist.php';

   // Config page
   $plugin = new Plugin();
   $links  = array();
   if (Session::haveRight('config','w') && $plugin->isActivated("formcreator")) {
      $PLUGIN_HOOKS['config_page']['formcreator'] = 'front/form.php';
      $links['config'] = '/plugins/formcreator/front/form.php';
      $links['add']    = '/plugins/formcreator/front/form.form.php';
   }

   // Set options for pages (title, links, buttons...)
   $links['search'] = '/plugins/formcreator/front/formlist.php';
   $PLUGIN_HOOKS['submenu_entry']['formcreator']['options'] = array(
      'config'       => array('title'  => __('Setup'),
                              'page'   => '/plugins/formcreator/front/form.php',
                              'links'  => $links),
      'options'      => array('title'  => _n('Form', 'Forms', 2, 'formcreator'),
                              'links'  => $links),
   );
   // Load field class and all its method to manage fields
   Plugin::registerClass('PluginFormcreatorFields');
}
