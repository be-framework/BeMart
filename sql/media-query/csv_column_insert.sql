INSERT INTO dtb_csv
(csv_type_id, creator_id, entity_name, field_name,
 reference_field_name, disp_name, sort_no, enabled,
 create_date, update_date, discriminator_type)
VALUES (:csvType, NULL, :entityName, :fieldName,
        NULL, :dispName, :sortNo, :enabled,
        NOW(), NOW(), :discriminator)
