SELECT *, GROUP_CONCAT(`glpi_plugin_tag_etiquetteitems`.`plugin_tag_etiquettes_id`) AS texte
FROM `glpi_tickets`
LEFT JOIN `glpi_plugin_tag_etiquetteitems` ON (`glpi_tickets`.`id` = `glpi_plugin_tag_etiquetteitems`.`glpiobject_id`) 
GROUP BY `glpi_tickets`.id
