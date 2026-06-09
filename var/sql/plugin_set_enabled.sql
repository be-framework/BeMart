SELECT
  COUNT(*) INTO @bemart_plugin_found
FROM
  dtb_plugin
WHERE
  code = :pluginCode;

SELECT
  COALESCE(
    MAX(initialized),
    0
  ) INTO @bemart_plugin_installed
FROM
  dtb_plugin
WHERE
  code = :pluginCode;

SELECT
  COALESCE(
    MAX(enabled),
    0
  ) INTO @bemart_plugin_before_enabled
FROM
  dtb_plugin
WHERE
  code = :pluginCode;

UPDATE
  dtb_plugin
SET
  enabled = :enabled,
  update_date = NOW()
WHERE
  code = :pluginCode
  AND initialized = 1
  AND enabled <> :enabled;

SELECT
  @bemart_plugin_found AS found,
  @bemart_plugin_installed AS installed,
  CASE WHEN @bemart_plugin_before_enabled <> :enabled
  AND @bemart_plugin_installed = 1 THEN
    1
  ELSE
    0
  END AS changed
