<?php
include ('../../../inc/includes.php');

function in_arrayi($needle, $haystack) {
   return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

// Old :
if (! in_arrayi($_REQUEST['itemtype'], getItemtypes()) ) {
   return '';
}

global $CFG_GLPI;

$itemtype = $_REQUEST['itemtype'];
$obj = new $itemtype();

// Warning : Ticket is need but parent class of Ticket isn't CommonDBTM
//if (get_parent_class($obj) != 'CommonDBTM') {
//   return;
//}

$selected_id = array();
$tag_item = new PluginTagTagItem();
$found_items = $tag_item->find('items_id='.$_REQUEST['id'].' AND itemtype="'.$_REQUEST['itemtype'].'"');

foreach ($found_items as $found_item) {
   $selected_id[] = $found_item['plugin_tag_tags_id'];
}

$obj->getFromDB($_REQUEST['id']);
$params = $obj->canUpdateItem() ? '' : ' disabled ';

$class = ($_REQUEST['itemtype'] == 'ticket') ? "tab_bg_1" : '';
$width = '350px';
if ($itemtype == 'group') {
   $width = '177px';
}
echo "<tr class='$class'>
         <th>"._n('Tag', 'Tags', 2, 'tag')."</th>
         <td>
            <select data-placeholder='Choisir les tags associés...' name='_plugin_tag_tag_values[]'
                style='width:$width;' multiple class='chosen-select-no-results' $params >
             <option value=''></option>";

$tag = new PluginTagTag();
switch ($itemtype) {
   case 'reminder':
   case 'rssfeed':
      $found = $tag->find('1=1');
      break;
   default:
      $found = $tag->find(getEntitiesRestrictRequest(" ", '', '', $obj->fields['entities_id'], true));
      break;
}

foreach ($found as $label) {
   $param = in_array($label['id'], $selected_id) ? ' selected ' : '';
   echo '<option value="'.$label['id'].'" '.$param.'>'.$label['name'].'</option>';
}

echo '</select>';
echo "</td>";
// Show '+' button : 
if (PluginTagTag::canCreate()) {
   echo "<td><a href='".$CFG_GLPI['url_base']."/plugins/tag/front/tag.form.php'>
         <img src='../pics/add_dropdown.png' alt='Add' /></a></td>";
}
echo "</tr>";
