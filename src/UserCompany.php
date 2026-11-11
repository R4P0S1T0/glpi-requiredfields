<?php

/**
 * Options "user_group_on_create" and "user_require_company":
 * - the new-user form gets a required Group field; on save the user joins that group, it becomes the
 *   default group, the default entity becomes the group's entity and the authorization entity is
 *   pre-selected to it (still editable, e.g. root + recursive for staff who see every company);
 * - on later edits nothing is changed, but a user with no group, or whose default entity is the
 *   root / full structure, cannot be saved until fixed. Users holding an exempt profile are skipped.
 */
final class PluginRequiredfieldsUserCompany
{
    public const FIELD = '_requiredfields_groups_id';

    public static function onCreate(): bool
    {
        return !empty(PluginRequiredfieldsRules::options()['user_group_on_create']);
    }

    public static function onEdit(): bool
    {
        return !empty(PluginRequiredfieldsRules::options()['user_require_company']);
    }

    public static function showField(array $params): void
    {
        $item = $params['item'] ?? null;
        if (!self::onCreate() || !($item instanceof User) || !$item->isNewItem()) {
            return;
        }
        $groups = [];
        foreach (getAllDataFromTable('glpi_groups', ['is_usergroup' => 1]) as $g) {
            $groups[$g['id']] = [(int) $g['entities_id'], Dropdown::getDropdownName('glpi_entities', $g['entities_id'])];
        }
        $rand = mt_rand();
        $label = htmlspecialchars(__('Group (department)', 'requiredfields'));
        echo "<div class='form-field row col-12 col-sm-6 mb-2'>"
            . "<label class='col-form-label col-xxl-5 text-xxl-end required' for='dropdown_" . self::FIELD . "$rand'>$label<span class='required'>*</span></label>"
            . "<div class='col-xxl-7 field-container'>";
        Group::dropdown([
            'name'        => self::FIELD,
            'entity'      => $_SESSION['glpiactiveentities'] ?? 0,
            'condition'   => ['is_usergroup' => 1],
            'rand'        => $rand,
            'specific_tags' => ['required' => 'required'],
        ]);
        echo '</div></div>';
        $map = json_encode($groups);
        $field = self::FIELD;
        // Pre-select the authorization entity with the group's entity; the user can still change it.
        echo <<<HTML
<script>
$(function () {
    const map = $map;
    $('#dropdown_{$field}{$rand}').on('change', function () {
        const g = map[this.value];
        const s = $(this).closest('form').find('select[name="_entities_id"]');
        if (!g || !s.length) { return; }
        if (!s.find('option[value="' + g[0] + '"]').length) { s.append(new Option(g[1], g[0], true, true)); }
        s.val(String(g[0])).trigger('change');
    });
});
</script>
HTML;
    }

    /** Labels missing on creation; fills default group and default entity from the chosen group. */
    public static function prepareAdd(array &$input): array
    {
        if (!self::onCreate()) {
            return [];
        }
        $group = new Group();
        if (!$group->getFromDB((int) ($input[self::FIELD] ?? 0))) {
            return [__('Group (department)', 'requiredfields')];
        }
        $input['groups_id']   = $group->getID();
        $input['entities_id'] = $group->fields['entities_id'];
        return [];
    }

    public static function afterAdd(User $user): void
    {
        $groups_id = (int) ($user->input[self::FIELD] ?? 0);
        if (self::onCreate() && $groups_id > 0) {
            (new Group_User())->add(['users_id' => $user->getID(), 'groups_id' => $groups_id]);
        }
    }

    /** Labels missing on a later edit. Never changes anything. */
    public static function checkEdit(User $user, array $input): array
    {
        if (!self::onEdit() || self::isExempt($user->getID())) {
            return [];
        }
        $missing = [];
        if (countElementsInTable('glpi_groups_users', ['users_id' => $user->getID()]) === 0) {
            $missing[] = __('Group (department)', 'requiredfields');
        }
        $default = (int) ($input['entities_id'] ?? $user->fields['entities_id'] ?? 0);
        if ($default <= 0) { // 0 = root entity, -1 = full structure
            $missing[] = __('Default entity (company)', 'requiredfields');
        }
        return $missing;
    }

    private static function isExempt(int $users_id): bool
    {
        $names = PluginRequiredfieldsRules::options()['exempt_profiles'] ?? [];
        if (!$names) {
            return false;
        }
        global $DB;
        $row = $DB->request([
            'COUNT'      => 'n',
            'FROM'       => 'glpi_profiles_users',
            'INNER JOIN' => ['glpi_profiles' => ['ON' => ['glpi_profiles' => 'id', 'glpi_profiles_users' => 'profiles_id']]],
            'WHERE'      => ['glpi_profiles_users.users_id' => $users_id, 'glpi_profiles.name' => $names],
        ])->current();
        return $row['n'] > 0;
    }
}
