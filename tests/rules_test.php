<?php

// php -d zend.assertions=1 -d assert.exception=1 tests/rules_test.php
require __DIR__ . '/../src/Rules.php';

$R = PluginRequiredfieldsRules::class;
$example = require __DIR__ . '/../rules.example.php';
assert(array_diff(array_keys($example), $R::SUPPORTED, ['_options']) === []);

$pc = $example['Computer'];
$full = ['name' => 'PC-01', 'locations_id' => 3, 'users_id' => 7, 'groups_id' => ['5'], 'states_id' => 1, 'otherserial' => 'INV-1'];

assert($R::missing($pc, $full, []) === []);
assert($R::missing($pc, ['locations_id' => '0'] + $full, []) === ['Location']);
assert($R::missing($pc, ['groups_id' => ['0']] + $full, []) === ['Group']);
assert($R::missing($pc, ['otherserial' => '  '] + $full, []) === ['Inventory number']);
// editing: fields not in the form fall back to the stored value
assert($R::missing($pc, ['name' => 'PC-01'], $full) === []);
assert($R::missing($pc, ['name' => ''], $full) === ['Name']);

$mon = $example['Monitor'];
$m = ['name' => 'M', 'locations_id' => 1, 'states_id' => 1];
assert($R::missing($mon, $m + ['have_subd' => 0, 'have_hdmi' => 0], []) === ['Input (VGA, BNC, DVI, HDMI or DisplayPort)']);
assert($R::missing($mon, $m + ['have_subd' => 0, 'have_hdmi' => 1], []) === []);

assert($R::missing($example['User'], ['name' => 'a', 'firstname' => 'b', 'realname' => 'c', '_useremails' => ['-1' => '']], []) === ['Email']);

echo "ok\n";
