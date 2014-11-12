<?php
include ('../../../inc/includes.php');

$class = '';
$params = '';

if ($_REQUEST['itemtype'] == 'ticket') {
   $ticket = new Ticket();
   $ticket->getFromDB($_REQUEST['id']);
   if ($ticket->fields['status'] == CommonITILObject::CLOSED) {
      $params = ' disabled';
   }
   $class = "tab_bg_1";
}

$value = PluginTagEtiquetteItem::getValue($_REQUEST['id'], $_REQUEST['itemtype']);
$html_input = "<input type='text' name='_plugin_tag_etiquette_value' value='$value' placeholder='Etiquette(s)' $params>";

echo "<tr class='$class'>
         <th>Tags</th>
         <td>$html_input</td>
      </tr>";