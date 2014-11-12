<?php
include ('../../../inc/includes.php');

Session::checkRight("config", "w");

Plugin::load('tag', true);

$dropdown = new PluginTagEtiquette();
/*
if (isset($_POST['add'])) {
   $founded = $dropdown->find('entities_id LIKE "' . $_SESSION['glpiactive_entity'] . '"');
   if (!empty($founded)) {
      Session::addMessageAfterRedirect(__('A tag already exists for this entity! You can have only one header per entity.', 'formcreator'), false, ERROR);
      Html::back();
   }
}
*/
include (GLPI_ROOT . "/front/dropdown.common.form.php");
