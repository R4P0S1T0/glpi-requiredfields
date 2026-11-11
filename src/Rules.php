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

    private static function raw(): array
    {
        static $raw = null;
        if ($raw === null) {
            $loaded = is_readable(self::file()) ? include self::file() : [];
            $raw = is_array($loaded) ? $loaded : [];
        }
        return $raw;
    }

    public static function load(): array
    {
        return array_intersect_key(self::raw(), array_flip(self::SUPPORTED));
    }

    /** The "_options" entry of rules.php (see rules.example.php). */
    public static function options(): array
    {
        return is_array(self::raw()['_options'] ?? null) ? self::raw()['_options'] : [];
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
