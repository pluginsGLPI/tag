<?php
include ('../../../inc/includes.php');

$class = '';
$params = '';

function in_arrayi($needle, $haystack) {
   return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

if (! in_arrayi($_REQUEST['itemtype'], getItemtypes()) ) {
   return '';
}

if ($_REQUEST['itemtype'] == 'ticket') {
   $ticket = new Ticket();
   $ticket->getFromDB($_REQUEST['id']);
   if ($ticket->fields['status'] == CommonITILObject::CLOSED) {
      $params = ' disabled';
   }
   $class = "tab_bg_1";
}

$selected_id = array();
$etiquette_item = new PluginTagEtiquetteItem();
$found_items = $etiquette_item->find('items_id='.$_REQUEST['id'].' AND itemtype="'.$_REQUEST['itemtype'].'"');

foreach ($found_items as $found_item) {
   $selected_id[] = $found_item['plugin_tag_etiquettes_id'];
}

$itemtype = $_REQUEST['itemtype'];
$obj = new $itemtype();
if (! $obj->canUpdate()) {
   $params .= ' disabled ';
}

$etiquette = new PluginTagEtiquette();
$found = $etiquette->find('entities_id LIKE "' . $_SESSION['glpiactive_entity'] . '"');

$html_input = '<select data-placeholder="Choisir les tags associés..." name="_plugin_tag_etiquette_values[]" style="width:350px;"
                multiple class="chosen-select-no-results" '.$params.' >';
$html_input .= '<option value=""></option>';

foreach ($found as $label) {
   $param = in_array($label['id'], $selected_id) ? ' selected ' : '';
   $html_input .= '<option value="'.$label['id'].'" '.$param.'>'.$label['name'].'</option>';
}

echo "<tr class='$class'>
         <th>Tags</th>
         <td>$html_input</td>
      </tr>";

