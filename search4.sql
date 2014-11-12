SELECT *
#, GROUP_CONCAT(`glpi_plugin_tag_etiquetteitems`.`plugin_tag_etiquettes_id`) AS texte
, GROUP_CONCAT(`glpi_plugin_tag_etiquetteitems`.`plugin_tag_etiquettes_id`) AS texte
, GROUP_CONCAT(`glpi_plugin_tag_etiquettes`.`name`) AS name
FROM `glpi_tickets`
LEFT JOIN `glpi_plugin_tag_etiquetteitems` ON (`glpi_tickets`.`id` = `glpi_plugin_tag_etiquetteitems`.`glpiobject_id`) #3,2
LEFT JOIN `glpi_plugin_tag_etiquettes` ON (`glpi_plugin_tag_etiquettes`.`id` = `glpi_plugin_tag_etiquetteitems`.`plugin_tag_etiquettes_id`) #name
GROUP BY `glpi_tickets`.id
