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
$html_input = '<select data-placeholder="Choisir les tags associés..." style="width:350px;"
                multiple class="chosen-select-no-results">';
$html_input .= '<option value=""></option>';

$etiquette = new PluginTagEtiquette();
$found = $etiquette->find("1=1");
foreach ($found as $label) {
   $html_input .= '<option value="'.$label['name'].'">'.$label['name'].'</option>';
}

echo "<tr class='$class'>
         <th>Tags</th>
         <td>$html_input</td>
      </tr>";

