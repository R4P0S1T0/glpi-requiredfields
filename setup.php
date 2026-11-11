<?php

define('PLUGIN_REQUIREDFIELDS_VERSION', '1.2.0');

require_once __DIR__ . '/src/Rules.php';
require_once __DIR__ . '/src/UserCompany.php';

function plugin_version_requiredfields()
{
    return [
        'name'         => 'Required Fields',
        'version'      => PLUGIN_REQUIREDFIELDS_VERSION,
        'author'       => 'Renan Morano',
        'license'      => 'GPLv3+',
        'homepage'     => 'https://github.com/R4P0S1T0/glpi-requiredfields',
        'requirements' => ['glpi' => ['min' => '11.0', 'max' => '11.1']],
    ];
}

function plugin_requiredfields_check_prerequisites()
{
    return true;
}

function plugin_requiredfields_check_config()
{
    return true;
}

function plugin_init_requiredfields()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['requiredfields'] = true;
    $types = array_fill_keys(array_keys(PluginRequiredfieldsRules::load()), 'plugin_requiredfields_validate');
    // Custom asset definitions are not loaded yet when plugins init, so read them from the table.
    global $DB;
    if ($DB instanceof DBmysql && $DB->connected && $DB->tableExists('glpi_assets_assetdefinitions')) {
        foreach (getAllDataFromTable('glpi_assets_assetdefinitions', ['is_active' => 1]) as $row) {
            $definition = new \Glpi\Asset\AssetDefinition();
            $definition->fields = $row;
            $types[$definition->getAssetClassName()] = 'plugin_requiredfields_validate';
        }
    }
    if (PluginRequiredfieldsUserCompany::onCreate() || PluginRequiredfieldsUserCompany::onEdit()) {
        $types['User'] = 'plugin_requiredfields_validate';
        $PLUGIN_HOOKS['post_item_form']['requiredfields'] = [PluginRequiredfieldsUserCompany::class, 'showField'];
        $PLUGIN_HOOKS['item_add']['requiredfields']       = ['User' => [PluginRequiredfieldsUserCompany::class, 'afterAdd']];
    }
    $PLUGIN_HOOKS['pre_item_add']['requiredfields']    = $types;
    $PLUGIN_HOOKS['pre_item_update']['requiredfields'] = $types;
}

/**
 * Only checks web form submissions (GLPI renames the add/update button to _add/_update).
 * Inventory, mass actions, imports, LDAP sync and the API never send it and are left alone.
 */
function plugin_requiredfields_validate(CommonDBTM $item)
{
    if (!is_array($item->input)) {
        return;
    }
    $creating = isset($item->input['_add']);
    if (!$creating && !isset($item->input['_update'])) {
        return;
    }
    // A user saving their own preferences is never blocked.
    if ($item instanceof User && !$creating && (int) ($item->input['id'] ?? 0) === (int) Session::getLoginUserID()) {
        return;
    }

    $current = $creating ? [] : $item->fields;
    if ($item instanceof User && !$creating) {
        $current['_useremails'] = UserEmail::getDefaultForUser($item->getID());
    }

    $rules = $item instanceof \Glpi\Asset\Asset
        ? PluginRequiredfieldsRules::forCustomAsset($item::getDefinition())
        : PluginRequiredfieldsRules::load()[$item::class] ?? [];
    $missing = PluginRequiredfieldsRules::missing($rules, $item->input, $current);
    if ($item instanceof User) {
        $input = $item->input;
        $missing = array_merge($missing, $creating
            ? PluginRequiredfieldsUserCompany::prepareAdd($input)
            : PluginRequiredfieldsUserCompany::checkEdit($item, $input));
        $item->input = $input;
    }
    if ($missing) {
        Session::addMessageAfterRedirect(
            sprintf(__('Please fill in the required fields: %s', 'requiredfields'), implode(', ', $missing)),
            false,
            ERROR
        );
        $item->input = false;
    }
}
