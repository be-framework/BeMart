INSERT INTO dtb_plugin
(name, code, enabled, version, source, initialized,
 create_date, update_date, discriminator_type)
VALUES (:name, :code, 0, :version, :source, 1,
        NOW(), NOW(), :discriminator)
