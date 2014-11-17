<?php
include ('../../../inc/includes.php');

Plugin::load('tag', true);

$dropdown = new PluginTagTag();
include (GLPI_ROOT . "/front/dropdown.common.php");
