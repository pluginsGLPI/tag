<?php
include ('../../../inc/includes.php');

Plugin::load('tag', true);

$dropdown = new PluginTagEtiquette();
include (GLPI_ROOT . "/front/dropdown.common.php");
