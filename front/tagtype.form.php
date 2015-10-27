<?php
include ('../../../inc/includes.php');

Plugin::load('tag', true);

$dropdown = new PluginTagTagtype();
include (GLPI_ROOT . "/front/dropdown.common.form.php");
