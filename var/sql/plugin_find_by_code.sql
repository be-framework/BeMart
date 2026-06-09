SELECT code, name, version, initialized, enabled
FROM dtb_plugin
WHERE code = :pluginCode
LIMIT 1
