<?php

/**
 * Copy to <GLPI files dir>/_plugins/requiredfields/rules.php and adjust.
 * Keys are database columns from the GLPI form; values are the labels shown in the error message.
 * "a|b|c" => at least one of those fields must be filled (handy for checkboxes).
 * Supported item types: Computer, Monitor, Printer, NetworkEquipment, Phone, Line, Rack, User.
 */
return [
    // Optional user/company behaviour, see README.
    '_options' => [
        'user_group_on_create' => true,           // new-user form gets a required Group field
        'user_require_company' => true,           // later edits need a group and a non-root default entity
        'exempt_profiles'      => ['Super-Admin'], // users holding these profiles skip the edit check
    ],
    'Computer' => [
        'name'         => 'Name',
        'locations_id' => 'Location',
        'users_id'     => 'User',
        'groups_id'    => 'Group',
        'states_id'    => 'Status',
        'otherserial'  => 'Inventory number',
    ],
    'Monitor' => [
        'name'         => 'Name',
        'locations_id' => 'Location',
        'states_id'    => 'Status',
        'have_subd|have_bnc|have_dvi|have_hdmi|have_displayport' => 'Input (VGA, BNC, DVI, HDMI or DisplayPort)',
    ],
    'Printer' => [
        'name'             => 'Name',
        'locations_id'     => 'Location',
        'manufacturers_id' => 'Manufacturer',
        'printermodels_id' => 'Model',
        'serial'           => 'Serial number',
        'have_serial|have_parallel|have_usb|have_ethernet|have_wifi' => 'Ports',
    ],
    'Rack' => [
        'name'         => 'Name',
        'locations_id' => 'Location',
        'number_units' => 'Number of units',
    ],
    // Groups and authorizations are tabs, not form fields, so they cannot be required on save.
    'User' => [
        'name'        => 'Login',
        'firstname'   => 'First name',
        'realname'    => 'Last name',
        '_useremails' => 'Email',
    ],
];
