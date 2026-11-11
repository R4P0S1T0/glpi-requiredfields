# Required Fields for GLPI

GLPI 11 plugin that enforces required fields on native assets and users when they are saved from the web form, leaving inventory, API, imports and mass actions untouched.

This plugin covers two cases:

- **Native item types** (computers, monitors, printers…) have no "required" option in GLPI, so the plugin adds one, configured in `rules.php`.
- **Custom assets** in GLPI 11 already have a "Required" option per field, in the definition's Fields tab. But GLPI only enforces it in the browser, and empty dropdowns still pass. The plugin enforces those same options on the server, with no extra configuration.

## How it works

- The plugin hooks into `pre_item_add` and `pre_item_update`.
- It only checks web form submissions, which GLPI marks with `_add` or `_update`.
- If a rule is not satisfied, the save is cancelled and the user sees: *Please fill in the required fields: Location, Group…*
- When editing, any field missing from the submitted form is checked against the value already stored.
- These are never blocked:
  - automatic inventory (agent, SNMP, `glpiinventory`);
  - mass actions, imports such as `datainjection`, LDAP sync, the REST API and rules;
  - users saving their own preferences.

Item types supported through `rules.php`: `Computer`, `Monitor`, `Printer`, `NetworkEquipment`, `Phone`, `Line`, `Rack`, `User`. Every active custom asset is also covered, using its own definition.

Things that cannot be required on save, because they live in tabs and not in the form:

- user groups and authorizations;
- documents and attachments.

## Install

```sh
cd /path/to/glpi/plugins
git clone https://github.com/R4P0S1T0/glpi-requiredfields.git requiredfields
cd ..
sudo -u www-data php bin/console plugin:install --username=glpi requiredfields
sudo -u www-data php bin/console plugin:activate requiredfields
```

The folder must be named `requiredfields`.

## Configure

Rules live outside the plugin folder, so upgrades never overwrite them:

```sh
sudo -u www-data cp plugins/requiredfields/rules.example.php files/_plugins/requiredfields/rules.php
```

Edit that file. Each key is the field's database column, and each value is the label shown in the error message. For checkboxes, `a|b|c` means at least one of them must be filled.

Without a `rules.php` file, native item types are not checked. Custom assets still are.

## Development

```sh
php -d zend.assertions=1 -d assert.exception=1 tests/rules_test.php
python3 tools/po2mo.py locales/*.po   # after editing translations
```

## License

GPLv3+
