#SELECT 'glpi' AS currentuser, `glpi_tickets`.`id` AS ITEM_0, `glpi_tickets`.`name` AS ITEM_1, `glpi_tickets`.`id` AS ITEM_1_2, `glpi_tickets`.`content` AS ITEM_1_3, `glpi_tickets`.`status` AS ITEM_1_4, `glpi_entities`.`completename` AS ITEM_2, `glpi_tickets`.`status` AS ITEM_3, `glpi_tickets`.`date_mod` AS ITEM_4, `glpi_tickets`.`date` AS ITEM_5, `glpi_tickets`.`priority` AS ITEM_6, GROUP_CONCAT(DISTINCT `glpi_users_647c2805c3795643b0f52f520e7cdb86`.`id` SEPARATOR '$$$$') AS ITEM_7, GROUP_CONCAT(DISTINCT CONCAT(`glpi_tickets_users_a900a61824c3906cc82f90407e525192`.`users_id`, ' ', `glpi_tickets_users_a900a61824c3906cc82f90407e525192`.`alternative_email`) SEPARATOR '$$$$') AS ITEM_7_2, GROUP_CONCAT(DISTINCT `glpi_users_c5e682856a6d6fe48b5aed8f8b238708`.`id` SEPARATOR '$$$$') AS ITEM_8, GROUP_CONCAT(DISTINCT CONCAT(`glpi_tickets_users_74690f2626744a37ace4c70dd87cea83`.`users_id`, ' ', `glpi_tickets_users_74690f2626744a37ace4c70dd87cea83`.`alternative_email`) SEPARATOR '$$$$') AS ITEM_8_2, `glpi_itilcategories`.`completename` AS ITEM_9, `glpi_tickets`.`due_date` AS ITEM_10, `glpi_tickets`.`status` AS ITEM_10_2, `glpi_plugin_vip_tickets_id`.`isvip` AS ITEM_11, `glpi_plugin_tag_tickets_id`.`isvip` AS ITEM_13, `glpi_tickets`.`id` AS id 
SELECT `glpi_tickets`.id, 
#glpi_plugin_tag_etiquettes.*
#glpi_plugin_tag_etiquetteitem_id.*
glpi_plugin_tag_etiquetteitems.*
FROM `glpi_tickets`
#LEFT JOIN `glpi_plugin_vip_tickets` AS glpi_plugin_vip_tickets_id ON (`glpi_tickets`.`id` = `glpi_plugin_vip_tickets_id`.`id` )  

#LEFT JOIN `glpi_plugin_tag_etiquetteitems` AS glpi_plugin_tag_etiquettes ON (`plugin_tag_etiquettes_id` = `glpi_plugin_tag_etiquettes`.`id`) 
#LEFT JOIN `glpi_plugin_tag_etiquetteitems` AS glpi_plugin_tag_etiquetteitem_id ON (`glpi_tickets`.`id` = `glpi_plugin_tag_etiquettes`.`id`)

LEFT JOIN `glpi_plugin_tag_etiquetteitems` AS glpi_plugin_tag_etiquettes ON (`plugin_tag_etiquettes_id` = `glpi_plugin_tag_etiquettes`.`id`) 
LEFT JOIN `glpi_plugin_tag_etiquetteitems` ON (`glpi_tickets`.`id` = `glpi_plugin_tag_etiquettes`.`id`)

#LEFT JOIN `glpi_plugin_tag_etiquetteitems` AS glpi_plugin_tag_etiquettes ON (`plugin_tag_etiquettes_id` = `glpi_plugin_tag_etiquettes`.`id`) 
#glpiobject_id = `glpi_tickets`.id
#WHERE glpiobject_id = `glpi_tickets`.id
#WHERE `glpi_tickets`.`is_deleted` = '0' AND ( GROUP_CONCAT( `itemtype` ) ) GROUP BY `glpi_tickets`.`id` 
