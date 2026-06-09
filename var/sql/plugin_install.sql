INSERT INTO dtb_plugin (
  name, code, version, source, initialized,
  enabled, create_date, update_date,
  discriminator_type
)
VALUES
  (
    :pluginName,
    :pluginCode,
    :version,
    0,
    1,
    0,
    NOW(),
    NOW(),
    'plugin'
  ) ON DUPLICATE KEY
UPDATE
  initialized = 1,
  update_date = NOW()
