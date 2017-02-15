<?php
include ('../../../inc/includes.php');

switch ($_POST['action']) {
   case 'tag_values':
      // check if itemtype can display tag control
      if (!PluginTagTag::canItemtype($_POST['itemtype'])) {
         exit;
      }

      $class = ($_POST['itemtype'] == 'ticket') ? "tab_bg_1" : '';

      echo "<tr class='$class tab_bg_1'>";
      echo "<th>"._n('Tag', 'Tags', 2, 'tag')."</th>";
      echo "<td colspan='3'>";
      PluginTagTag::showTagDropdown($_REQUEST);
      echo "</td>";
      echo "</tr>";
      break;

   case 'add_subtypes':
      // Sub type add
      $itemtypes = array();
      foreach (PluginTagTagItem::getItemtypes($_POST['type_menu']) as $itemtype) {
         $item                 = getItemForItemtype($itemtype);
         $itemtypes[$itemtype] = $item->getTypeName();
      }
      Dropdown::showFromArray("add_subtypes", $itemtypes, array('multiple' => true, 'rand' => $_POST['rand'], 'width' => '50%'));

      // Add subtypes button
      echo " <a class=\"vsubmit\" onclick=\"pluginTagAddSubType('dropdown_subtypes".$_POST['rand']."', 'dropdown_add_subtypes".$_POST['rand']."', '".$CFG_GLPI['root_doc']."/plugins/tag/ajax/tag.php');\">".__('Add type', 'tag')."</a>";
      break;

   case 'list_subtypes':
      // Sub type list
      $itemtypes = array();
      if (!empty($_POST['subtypes'])) {
         foreach ($_POST['subtypes'] as $key => $itemtype) {
            $item                = getItemForItemtype($itemtype);
            $itemtypes[$key]['value']   = $itemtype;
            $itemtypes[$key]['text'] = $item->getTypeName();
         }
      }
      echo json_encode($itemtypes);
      break;
}
