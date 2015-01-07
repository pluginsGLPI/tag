<?php
include ('../../../inc/includes.php');

// check if itemtype can display tag control
if (in_array(strtolower($_REQUEST['itemtype']), 
             array_map('strtolower', getBlacklistItemtype()))) {
   return '';
}

$class = ($_REQUEST['itemtype'] == 'ticket') ? "tab_bg_1" : '';

echo "<tr class='$class'>";
echo "<th>"._n('Tag', 'Tags', 2, 'tag')."</th>";
echo "<td colspan='3'>";
PluginTagTag::tagDropdownMultiple();
echo "</td>";
echo "</tr>";
