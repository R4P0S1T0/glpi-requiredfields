<?php

/**
 * Required-field rules: [itemtype => [field => label]].
 * A key like "have_hdmi|have_dvi" means at least one of those fields must be filled.
 */
final class PluginRequiredfieldsRules
{
    /** Native item types configured through rules.php. Custom assets use their own definition, see forCustomAsset(). */
    public const SUPPORTED = ['Computer', 'Monitor', 'Printer', 'NetworkEquipment', 'Phone', 'Line', 'Rack', 'User'];

    public static function file(): string
    {
        return GLPI_PLUGIN_DOC_DIR . '/requiredfields/rules.php';
    }

    public static function load(): array
    {
        static $rules = null;
        if ($rules === null) {
            $loaded = is_readable(self::file()) ? include self::file() : [];
            $rules = array_intersect_key(is_array($loaded) ? $loaded : [], array_flip(self::SUPPORTED));
        }
        return $rules;
    }

    /**
     * Fields marked "Required" in a GLPI 11 custom asset definition (Fields tab).
     * GLPI only enforces that in the browser, and empty dropdowns ("0") slip through.
     */
    public static function forCustomAsset(\Glpi\Asset\AssetDefinition $definition): array
    {
        $labels = $definition->getAllFields();
        $rules = [];
        foreach ($definition->getDecodedFieldsField() as $field) {
            $key = $field['key'] ?? '';
            // shortcut: custom fields (custom_*) are skipped, their values live in a JSON column; add when someone needs them
            if (!empty($field['field_options']['required']) && !str_starts_with($key, 'custom_') && isset($labels[$key])) {
                $rules[$key] = $labels[$key]['text'];
            }
        }
        return $rules;
    }

    /**
     * Labels of the rules not satisfied.
     * $input = submitted form data; $current = values already stored (empty when creating).
     */
    public static function missing(array $rules, array $input, array $current): array
    {
        $missing = [];
        foreach ($rules as $fields => $label) {
            $ok = false;
            foreach (explode('|', $fields) as $field) {
                $value = array_key_exists($field, $input) ? $input[$field] : ($current[$field] ?? null);
                if (!self::isEmpty($value)) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $missing[] = $label;
            }
        }
        return $missing;
    }

    private static function isEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                if (!self::isEmpty($v)) {
                    return false;
                }
            }
            return true;
        }
        // empty dropdown = 0, unchecked checkbox = 0
        return $value === null || trim((string) $value) === '' || (string) $value === '0';
    }
}
