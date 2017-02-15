<?php
include ('../../../inc/includes.php');

switch ($_POST['action']) {
   case 'add_subtypes':
      // Sub type add
      $itemtypes = [];
      foreach (PluginTagTagItem::getItemtypes($_POST['type_menu']) as $itemtype) {
         $item                 = getItemForItemtype($itemtype);
         $itemtypes[$itemtype] = $item->getTypeName();
      }
      Dropdown::showFromArray("add_subtypes", $itemtypes, ['multiple' => true,
                                                           'rand'     => $_POST['rand'],
                                                           'width'    => '50%']);

      // Add subtypes button
      echo "<a class='vsubmit'
               onclick=\"pluginTagAddSubType('dropdown_subtypes".$_POST['rand']."', 'dropdown_add_subtypes".$_POST['rand']."', '".$CFG_GLPI['root_doc']."/plugins/tag/ajax/tag.php');\">".
            __('Add type', 'tag')."</a>";
      break;

   case 'list_subtypes':
      // Sub type list
      $itemtypes = [];
      if (!empty($_POST['subtypes'])) {
         foreach ($_POST['subtypes'] as $key => $itemtype) {
            $item                     = getItemForItemtype($itemtype);
            $itemtypes[$key]['value'] = $itemtype;
            $itemtypes[$key]['text']  = $item->getTypeName();
         }
      }
      echo json_encode($itemtypes);
      break;
}
