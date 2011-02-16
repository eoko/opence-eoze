DELETE `root` FROM `contacts` `root`
LEFT JOIN `contacts` AS `Conjoint` ON `Conjoint`.`id` = (SELECT IF(`root`.`id` = `conjoints`.`conjoint1__contacts_id`, `conjoints`.`conjoint2__contacts_id`, `conjoints`.`conjoint1__contacts_id`) FROM `conjoints` AS `conjoints` WHERE (`root`.`id` = `conjoints`.`conjoint1__contacts_id` OR `root`.`id` = `conjoints`.`conjoint2__contacts_id`))
LEFT JOIN `membres` AS `Membre` ON `root`.`id` = `Membre`.`contacts_id`
LEFT JOIN `enfants` AS `Enfant` ON `root`.`id` = `Enfant`.`enfant__contacts_id`
WHERE `Conjoint`.`id` IS NULL AND `Membre`.`id` IS NULL AND `Enfant`.`id` IS NULL;