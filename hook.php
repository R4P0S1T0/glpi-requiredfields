<?php

// No tables: rules live in GLPI_PLUGIN_DOC_DIR/requiredfields/rules.php, which survives plugin upgrades.
function plugin_requiredfields_install()
{
    $dir = dirname(PluginRequiredfieldsRules::file());
    return is_dir($dir) || mkdir($dir, 0750, true);
}

// Keeps rules.php on purpose so a reinstall picks the same rules back up.
function plugin_requiredfields_uninstall()
{
    return true;
}
