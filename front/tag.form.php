<?php
include ('../../../inc/includes.php');

Session::checkRight("config", "w");

Plugin::load('tag', true);

$dropdown = new PluginTagTag();

if (isset($_POST['add'])) {
   $item = new PluginTagTagItem();
   //$_REQUEST['itemtype'] = strtolower($_REQUEST['itemtype']);
   
   // Check unicity :
   $found = $item->find('plugin_tag_tags_id = '. $_REQUEST['plugin_tag_tags_id'] .'
         AND items_id = ' . $_REQUEST['items_id'].'
         AND itemtype = "' . $_REQUEST['itemtype'].'"');
   
   if (count($found) == 0) {
      $item->add($_REQUEST);
   }
}

include (GLPI_ROOT . "/front/dropdown.common.form.php");
