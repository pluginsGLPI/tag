<?php
include ('../../../inc/includes.php');

Session::checkRight("config", "w");

Plugin::load('tag', true);

$dropdown = new PluginTagEtiquette();

if (isset($_POST['add'])) {
   $item = new PluginTagEtiquetteItem();
   $_REQUEST['itemtype'] = strtolower($_REQUEST['itemtype']);
   $item->add($_REQUEST);
}

include (GLPI_ROOT . "/front/dropdown.common.form.php");
