<?php
include ('../../../inc/includes.php');

function in_arrayi($needle, $haystack) {
   return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

if (! in_arrayi($_REQUEST['itemtype'], getItemtypes()) ) {
   return '';
}

$selected_id = array();
$tag_item = new PluginTagEtiquetteItem();
$found_items = $tag_item->find('items_id='.$_REQUEST['id'].' AND itemtype="'.$_REQUEST['itemtype'].'"');

foreach ($found_items as $found_item) {
   $selected_id[] = $found_item['plugin_tag_etiquettes_id'];
}

$itemtype = $_REQUEST['itemtype'];
$obj = new $itemtype();
$params = $obj->canUpdate() ? '' : ' disabled ';

$class = ($_REQUEST['itemtype'] == 'ticket') ? "tab_bg_1" : '';
echo "<tr class='$class'>
         <th>Tags</th>
         <td>
            <select data-placeholder='Choisir les tags associés...' name='_plugin_tag_etiquette_values[]'
                style='width:350px;' multiple class='chosen-select-no-results' $params >
             <option value=''></option>";

$tag = new PluginTagEtiquette();
$found = $tag->find('entities_id LIKE "' . $_SESSION['glpiactive_entity'] . '"');

foreach ($found as $label) {
   $param = in_array($label['id'], $selected_id) ? ' selected ' : '';
   echo '<option value="'.$label['id'].'" '.$param.'>'.$label['name'].'</option>';
}

echo     "</td>
      </tr>";

