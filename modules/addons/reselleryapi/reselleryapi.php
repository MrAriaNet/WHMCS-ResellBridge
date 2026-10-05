<?php
/**
 * WHMCS ResellBridge addon — multi-reseller credentials and product-group access.
 * Languages: english + farsi (modules/addons/reselleryapi/lang/)
 *
 * Install: copy to modules/addons/reselleryapi/ on the MAIN WHMCS.
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;
use ReselleryApi\ResellerAccounts;

require_once __DIR__ . '/lib/ResellerAccounts.php';

/**
 * @param string $key
 * @return string
 */
function reselleryapi_t($key)
{
    global $_ADDONLANG;

    if (empty($_ADDONLANG) || !isset($_ADDONLANG[$key])) {
        reselleryapi_bootstrap_lang();
    }

    return isset($_ADDONLANG[$key]) ? $_ADDONLANG[$key] : $key;
}

/**
 * Ensure addon language array is loaded (english / farsi).
 */
function reselleryapi_bootstrap_lang()
{
    global $_ADDONLANG;

    $adminLang = 'english';
    if (isset($_SESSION['adminlang'])) {
        $adminLang = strtolower((string) $_SESSION['adminlang']);
    } elseif (isset($GLOBALS['CONFIG']['Language'])) {
        $adminLang = strtolower((string) $GLOBALS['CONFIG']['Language']);
    }

    if (in_array($adminLang, ['farsi', 'persian', 'fa'], true)) {
        $file = __DIR__ . '/lang/farsi.php';
    } else {
        $file = __DIR__ . '/lang/english.php';
    }

    if (is_readable($file)) {
        include $file;
    }
}

function reselleryapi_config()
{
    reselleryapi_bootstrap_lang();

    return [
        'name' => reselleryapi_t('addon_name'),
        'description' => reselleryapi_t('addon_description'),
        'version' => '1.2.0',
        'author' => 'Aria Jahangiri Far (https://github.com/MrAriaNet)',
        'language' => 'english',
        'fields' => [
            'api_username' => [
                'FriendlyName' => reselleryapi_t('cfg_global_username'),
                'Type' => 'text',
                'Size' => '40',
                'Default' => '',
                'Description' => reselleryapi_t('cfg_global_username_desc'),
            ],
            'api_password' => [
                'FriendlyName' => reselleryapi_t('cfg_global_password'),
                'Type' => 'password',
                'Size' => '40',
                'Default' => '',
                'Description' => reselleryapi_t('cfg_global_password_desc'),
            ],
            'access_hash' => [
                'FriendlyName' => reselleryapi_t('cfg_global_hash'),
                'Type' => 'text',
                'Size' => '64',
                'Default' => '',
                'Description' => reselleryapi_t('cfg_global_hash_desc'),
            ],
            'allowed_ips' => [
                'FriendlyName' => reselleryapi_t('cfg_default_ips'),
                'Type' => 'textarea',
                'Rows' => '3',
                'Cols' => '60',
                'Default' => '',
                'Description' => reselleryapi_t('cfg_default_ips_desc'),
            ],
            'allowed_product_groups' => [
                'FriendlyName' => reselleryapi_t('cfg_default_groups'),
                'Type' => 'text',
                'Size' => '80',
                'Default' => '',
                'Description' => reselleryapi_t('cfg_default_groups_desc'),
            ],
        ],
    ];
}

function reselleryapi_activate()
{
    try {
        ResellerAccounts::ensureSchema();
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => reselleryapi_t('activate_fail') . $e->getMessage(),
        ];
    }

    return [
        'status' => 'success',
        'description' => reselleryapi_t('activate_ok'),
    ];
}

function reselleryapi_deactivate()
{
    return [
        'status' => 'success',
        'description' => reselleryapi_t('deactivate_ok'),
    ];
}

function reselleryapi_output($vars)
{
    reselleryapi_bootstrap_lang();
    $moduleLink = 'addonmodules.php?module=reselleryapi';

    try {
        ResellerAccounts::ensureSchema();
    } catch (\Exception $e) {
        echo '<div class="errorbox">' . htmlspecialchars(reselleryapi_t('schema_error') . $e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_token('WHMCS.admin.default');

        if (isset($_POST['reselleryapi_save_defaults'])) {
            reselleryapi_save_setting('allowed_product_groups', reselleryapi_posted_group_csv());
            $vars['allowed_product_groups'] = reselleryapi_posted_group_csv();
            echo '<div class="successbox"><strong>' . htmlspecialchars(reselleryapi_t('success'), ENT_QUOTES, 'UTF-8')
                . '</strong> ' . htmlspecialchars(reselleryapi_t('defaults_saved'), ENT_QUOTES, 'UTF-8') . '</div>';
        }

        if (isset($_POST['reselleryapi_create_reseller'])) {
            echo reselleryapi_handle_create_reseller()['html'];
        }

        if (isset($_POST['reselleryapi_update_reseller'])) {
            echo reselleryapi_handle_update_reseller()['html'];
        }

        if (isset($_POST['reselleryapi_delete_reseller'])) {
            $id = isset($_POST['reseller_id']) ? (int) $_POST['reseller_id'] : 0;
            if ($id > 0) {
                ResellerAccounts::delete($id);
                echo '<div class="successbox"><strong>' . htmlspecialchars(reselleryapi_t('success'), ENT_QUOTES, 'UTF-8')
                    . '</strong> ' . htmlspecialchars(reselleryapi_t('deleted'), ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }
    }

    $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
    $editRow = $editId > 0 ? ResellerAccounts::find($editId) : null;

    echo '<p>' . htmlspecialchars(reselleryapi_t('intro'), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p>' . htmlspecialchars(reselleryapi_t('endpoint'), ENT_QUOTES, 'UTF-8')
        . ' <code>/includes/api_resellery.php</code></p>';

    echo '<h2>' . htmlspecialchars(reselleryapi_t('defaults_title'), ENT_QUOTES, 'UTF-8') . '</h2>';
    echo '<p>' . htmlspecialchars(reselleryapi_t('defaults_help'), ENT_QUOTES, 'UTF-8') . '</p>';
    reselleryapi_render_group_form(
        $moduleLink,
        'reselleryapi_save_defaults',
        isset($vars['allowed_product_groups']) ? (string) $vars['allowed_product_groups'] : '',
        reselleryapi_t('save_defaults')
    );

    echo '<h2>' . htmlspecialchars(reselleryapi_t('accounts_title'), ENT_QUOTES, 'UTF-8') . '</h2>';
    $accounts = ResellerAccounts::all();
    echo '<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">';
    echo '<tr>'
        . '<th>' . htmlspecialchars(reselleryapi_t('col_id'), ENT_QUOTES, 'UTF-8') . '</th>'
        . '<th>' . htmlspecialchars(reselleryapi_t('col_name'), ENT_QUOTES, 'UTF-8') . '</th>'
        . '<th>' . htmlspecialchars(reselleryapi_t('col_username'), ENT_QUOTES, 'UTF-8') . '</th>'
        . '<th>' . htmlspecialchars(reselleryapi_t('col_enabled'), ENT_QUOTES, 'UTF-8') . '</th>'
        . '<th>' . htmlspecialchars(reselleryapi_t('col_groups'), ENT_QUOTES, 'UTF-8') . '</th>'
        . '<th>' . htmlspecialchars(reselleryapi_t('col_ips'), ENT_QUOTES, 'UTF-8') . '</th>'
        . '<th>' . htmlspecialchars(reselleryapi_t('col_actions'), ENT_QUOTES, 'UTF-8') . '</th>'
        . '</tr>';

    if ($accounts->isEmpty()) {
        echo '<tr><td colspan="7">' . htmlspecialchars(reselleryapi_t('no_accounts'), ENT_QUOTES, 'UTF-8') . '</td></tr>';
    } else {
        foreach ($accounts as $account) {
            $groupsLabel = trim((string) $account->allowed_product_groups);
            $groupsLabel = $groupsLabel === ''
                ? '<em>' . htmlspecialchars(reselleryapi_t('inherit_default'), ENT_QUOTES, 'UTF-8') . '</em>'
                : htmlspecialchars($groupsLabel, ENT_QUOTES, 'UTF-8');
            $ipsLabel = trim((string) $account->allowed_ips);
            $ipsLabel = $ipsLabel === ''
                ? '<em>' . htmlspecialchars(reselleryapi_t('inherit_ips'), ENT_QUOTES, 'UTF-8') . '</em>'
                : htmlspecialchars($ipsLabel, ENT_QUOTES, 'UTF-8');
            $enabled = (int) $account->enabled
                ? reselleryapi_t('yes')
                : reselleryapi_t('no');

            echo '<tr>';
            echo '<td>' . (int) $account->id . '</td>';
            echo '<td>' . htmlspecialchars($account->name, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td><code>' . htmlspecialchars($account->username, ENT_QUOTES, 'UTF-8') . '</code></td>';
            echo '<td>' . htmlspecialchars($enabled, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . $groupsLabel . '</td>';
            echo '<td>' . $ipsLabel . '</td>';
            echo '<td>';
            echo '<a class="btn btn-default btn-sm" href="'
                . htmlspecialchars($moduleLink . '&edit=' . (int) $account->id, ENT_QUOTES, 'UTF-8')
                . '">' . htmlspecialchars(reselleryapi_t('edit'), ENT_QUOTES, 'UTF-8') . '</a> ';
            echo '<form method="post" action="' . htmlspecialchars($moduleLink, ENT_QUOTES, 'UTF-8')
                . '" style="display:inline" onsubmit="return confirm(\''
                . htmlspecialchars(reselleryapi_t('delete_confirm'), ENT_QUOTES, 'UTF-8') . '\');">';
            echo generate_token('form');
            echo '<input type="hidden" name="reselleryapi_delete_reseller" value="1" />';
            echo '<input type="hidden" name="reseller_id" value="' . (int) $account->id . '" />';
            echo '<button type="submit" class="btn btn-danger btn-sm">'
                . htmlspecialchars(reselleryapi_t('delete'), ENT_QUOTES, 'UTF-8') . '</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
    }
    echo '</table>';

    if ($editRow) {
        echo '<h2>' . htmlspecialchars(reselleryapi_t('edit_title') . (int) $editRow->id, ENT_QUOTES, 'UTF-8') . '</h2>';
        reselleryapi_render_reseller_form($moduleLink, $editRow, true);
        echo '<p><a href="' . htmlspecialchars($moduleLink, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars(reselleryapi_t('cancel_edit'), ENT_QUOTES, 'UTF-8') . '</a></p>';
    } else {
        echo '<h2>' . htmlspecialchars(reselleryapi_t('add_title'), ENT_QUOTES, 'UTF-8') . '</h2>';
        echo '<p>' . htmlspecialchars(reselleryapi_t('add_help'), ENT_QUOTES, 'UTF-8') . '</p>';
        reselleryapi_render_reseller_form($moduleLink, null, false);
    }
}

function reselleryapi_posted_group_csv()
{
    $selected = isset($_POST['allowed_gids']) && is_array($_POST['allowed_gids'])
        ? $_POST['allowed_gids']
        : [];
    $ids = [];
    foreach ($selected as $gid) {
        $gid = (int) $gid;
        if ($gid > 0) {
            $ids[$gid] = $gid;
        }
    }
    return implode(',', array_values($ids));
}

function reselleryapi_save_setting($setting, $value)
{
    $exists = Capsule::table('tbladdonmodules')
        ->where('module', 'reselleryapi')
        ->where('setting', $setting)
        ->exists();

    if ($exists) {
        Capsule::table('tbladdonmodules')
            ->where('module', 'reselleryapi')
            ->where('setting', $setting)
            ->update(['value' => $value]);
    } else {
        Capsule::table('tbladdonmodules')->insert([
            'module' => 'reselleryapi',
            'setting' => $setting,
            'value' => $value,
        ]);
    }
}

function reselleryapi_render_group_form($moduleLink, $submitName, $selectedCsv, $buttonLabel)
{
    $allowedIds = [];
    foreach (ResellerAccounts::parseGroupIds($selectedCsv) as $id) {
        $allowedIds[$id] = $id;
    }

    $groups = Capsule::table('tblproductgroups')
        ->orderBy('order', 'asc')
        ->orderBy('name', 'asc')
        ->get(['id', 'name', 'hidden']);

    echo '<form method="post" action="' . htmlspecialchars($moduleLink, ENT_QUOTES, 'UTF-8') . '">';
    echo generate_token('form');
    echo '<input type="hidden" name="' . htmlspecialchars($submitName, ENT_QUOTES, 'UTF-8') . '" value="1" />';
    echo '<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">';
    echo '<tr><td class="fieldlabel" width="20%">' . htmlspecialchars(reselleryapi_t('group'), ENT_QUOTES, 'UTF-8')
        . '</td><td class="fieldarea">' . htmlspecialchars(reselleryapi_t('allow'), ENT_QUOTES, 'UTF-8') . '</td></tr>';

    if ($groups->isEmpty()) {
        echo '<tr><td colspan="2">' . htmlspecialchars(reselleryapi_t('no_groups'), ENT_QUOTES, 'UTF-8') . '</td></tr>';
    } else {
        foreach ($groups as $group) {
            $gid = (int) $group->id;
            $checked = isset($allowedIds[$gid]) ? ' checked' : '';
            $hiddenNote = (!empty($group->hidden) && (string) $group->hidden === '1')
                ? ' <em>' . htmlspecialchars(reselleryapi_t('hidden_store'), ENT_QUOTES, 'UTF-8') . '</em>'
                : '';
            echo '<tr>';
            echo '<td class="fieldlabel">' . htmlspecialchars($group->name, ENT_QUOTES, 'UTF-8')
                . ' <small>(ID ' . $gid . ')</small>' . $hiddenNote . '</td>';
            echo '<td class="fieldarea"><input type="checkbox" name="allowed_gids[]" value="'
                . $gid . '"' . $checked . ' /></td>';
            echo '</tr>';
        }
    }

    echo '</table>';
    echo '<p><input type="submit" value="' . htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8')
        . '" class="btn btn-primary" /></p>';
    echo '</form>';
}

function reselleryapi_render_reseller_form($moduleLink, $row, $isEdit)
{
    $name = $row ? (string) $row->name : '';
    $username = $row ? (string) $row->username : '';
    $accessHash = $row ? (string) $row->access_hash : bin2hex(random_bytes(16));
    $allowedIps = $row ? (string) $row->allowed_ips : '';
    $notes = $row ? (string) $row->notes : '';
    $enabled = $row ? (int) $row->enabled : 1;
    $groupsCsv = $row ? (string) $row->allowed_product_groups : '';

    echo '<form method="post" action="' . htmlspecialchars($moduleLink, ENT_QUOTES, 'UTF-8') . '">';
    echo generate_token('form');
    if ($isEdit) {
        echo '<input type="hidden" name="reselleryapi_update_reseller" value="1" />';
        echo '<input type="hidden" name="reseller_id" value="' . (int) $row->id . '" />';
    } else {
        echo '<input type="hidden" name="reselleryapi_create_reseller" value="1" />';
    }

    echo '<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">';
    echo '<tr><td class="fieldlabel">' . htmlspecialchars(reselleryapi_t('field_name'), ENT_QUOTES, 'UTF-8')
        . '</td><td class="fieldarea"><input type="text" name="name" value="'
        . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="form-control input-400" required /></td></tr>';
    echo '<tr><td class="fieldlabel">' . htmlspecialchars(reselleryapi_t('field_username'), ENT_QUOTES, 'UTF-8')
        . '</td><td class="fieldarea"><input type="text" name="username" value="'
        . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '" class="form-control input-400" required /></td></tr>';
    echo '<tr><td class="fieldlabel">' . htmlspecialchars(reselleryapi_t('field_password'), ENT_QUOTES, 'UTF-8')
        . '</td><td class="fieldarea"><input type="password" name="password" value="" class="form-control input-400" '
        . ($isEdit ? '' : 'required ')
        . 'placeholder="' . htmlspecialchars(reselleryapi_t('field_password_keep'), ENT_QUOTES, 'UTF-8')
        . '" /></td></tr>';
    echo '<tr><td class="fieldlabel">' . htmlspecialchars(reselleryapi_t('field_hash'), ENT_QUOTES, 'UTF-8')
        . '</td><td class="fieldarea"><input type="text" name="access_hash" value="'
        . htmlspecialchars($accessHash, ENT_QUOTES, 'UTF-8') . '" class="form-control input-400" required /></td></tr>';
    echo '<tr><td class="fieldlabel">' . htmlspecialchars(reselleryapi_t('field_ips'), ENT_QUOTES, 'UTF-8')
        . '</td><td class="fieldarea"><textarea name="allowed_ips" rows="2" class="form-control input-400">'
        . htmlspecialchars($allowedIps, ENT_QUOTES, 'UTF-8')
        . '</textarea><br /><small>' . htmlspecialchars(reselleryapi_t('field_ips_help'), ENT_QUOTES, 'UTF-8')
        . '</small></td></tr>';
    echo '<tr><td class="fieldlabel">' . htmlspecialchars(reselleryapi_t('field_notes'), ENT_QUOTES, 'UTF-8')
        . '</td><td class="fieldarea"><textarea name="notes" rows="2" class="form-control input-400">'
        . htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') . '</textarea></td></tr>';
    echo '<tr><td class="fieldlabel">' . htmlspecialchars(reselleryapi_t('field_enabled'), ENT_QUOTES, 'UTF-8')
        . '</td><td class="fieldarea"><label><input type="checkbox" name="enabled" value="1"'
        . ($enabled ? ' checked' : '') . ' /> '
        . htmlspecialchars(reselleryapi_t('field_active'), ENT_QUOTES, 'UTF-8') . '</label></td></tr>';
    echo '</table>';

    echo '<h3>' . htmlspecialchars(reselleryapi_t('groups_for_reseller'), ENT_QUOTES, 'UTF-8') . '</h3>';
    echo '<p>' . htmlspecialchars(reselleryapi_t('groups_for_reseller_help'), ENT_QUOTES, 'UTF-8') . '</p>';

    $allowedIds = [];
    foreach (ResellerAccounts::parseGroupIds($groupsCsv) as $id) {
        $allowedIds[$id] = $id;
    }
    $groups = Capsule::table('tblproductgroups')
        ->orderBy('order', 'asc')
        ->orderBy('name', 'asc')
        ->get(['id', 'name', 'hidden']);

    echo '<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">';
    foreach ($groups as $group) {
        $gid = (int) $group->id;
        $checked = isset($allowedIds[$gid]) ? ' checked' : '';
        echo '<tr><td class="fieldlabel">' . htmlspecialchars($group->name, ENT_QUOTES, 'UTF-8')
            . ' <small>(ID ' . $gid . ')</small></td>';
        echo '<td class="fieldarea"><input type="checkbox" name="allowed_gids[]" value="'
            . $gid . '"' . $checked . ' /></td></tr>';
    }
    echo '</table>';

    $btn = $isEdit ? reselleryapi_t('btn_update') : reselleryapi_t('btn_create');
    echo '<p><input type="submit" value="' . htmlspecialchars($btn, ENT_QUOTES, 'UTF-8')
        . '" class="btn btn-primary" /></p>';
    echo '</form>';
}

function reselleryapi_handle_create_reseller()
{
    $name = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
    $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $accessHash = isset($_POST['access_hash']) ? trim((string) $_POST['access_hash']) : '';

    if ($name === '' || $username === '' || $password === '' || $accessHash === '') {
        return ['html' => '<div class="errorbox">' . htmlspecialchars(reselleryapi_t('err_required'), ENT_QUOTES, 'UTF-8') . '</div>'];
    }

    if (ResellerAccounts::findByUsername($username)) {
        return ['html' => '<div class="errorbox">' . htmlspecialchars(reselleryapi_t('err_username_taken'), ENT_QUOTES, 'UTF-8') . '</div>'];
    }

    try {
        ResellerAccounts::create([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'access_hash' => $accessHash,
            'allowed_ips' => isset($_POST['allowed_ips']) ? (string) $_POST['allowed_ips'] : '',
            'allowed_product_groups' => reselleryapi_posted_group_csv(),
            'enabled' => !empty($_POST['enabled']),
            'notes' => isset($_POST['notes']) ? (string) $_POST['notes'] : '',
        ]);
    } catch (\Exception $e) {
        return ['html' => '<div class="errorbox">' . htmlspecialchars(reselleryapi_t('err_create') . $e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>'];
    }

    return ['html' => '<div class="successbox"><strong>' . htmlspecialchars(reselleryapi_t('success'), ENT_QUOTES, 'UTF-8')
        . '</strong> ' . htmlspecialchars(reselleryapi_t('ok_created'), ENT_QUOTES, 'UTF-8') . '</div>'];
}

function reselleryapi_handle_update_reseller()
{
    $id = isset($_POST['reseller_id']) ? (int) $_POST['reseller_id'] : 0;
    $row = $id > 0 ? ResellerAccounts::find($id) : null;
    if (!$row) {
        return ['html' => '<div class="errorbox">' . htmlspecialchars(reselleryapi_t('err_not_found'), ENT_QUOTES, 'UTF-8') . '</div>'];
    }

    $name = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
    $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $accessHash = isset($_POST['access_hash']) ? trim((string) $_POST['access_hash']) : '';

    if ($name === '' || $username === '' || $accessHash === '') {
        return ['html' => '<div class="errorbox">' . htmlspecialchars(reselleryapi_t('err_required_update'), ENT_QUOTES, 'UTF-8') . '</div>'];
    }

    $other = ResellerAccounts::findByUsername($username);
    if ($other && (int) $other->id !== $id) {
        return ['html' => '<div class="errorbox">' . htmlspecialchars(reselleryapi_t('err_username_taken'), ENT_QUOTES, 'UTF-8') . '</div>'];
    }

    $data = [
        'name' => $name,
        'username' => $username,
        'access_hash' => $accessHash,
        'allowed_ips' => isset($_POST['allowed_ips']) ? (string) $_POST['allowed_ips'] : '',
        'allowed_product_groups' => reselleryapi_posted_group_csv(),
        'enabled' => !empty($_POST['enabled']),
        'notes' => isset($_POST['notes']) ? (string) $_POST['notes'] : '',
    ];
    if ($password !== '') {
        $data['password'] = $password;
    }

    try {
        ResellerAccounts::update($id, $data);
    } catch (\Exception $e) {
        return ['html' => '<div class="errorbox">' . htmlspecialchars(reselleryapi_t('err_update') . $e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>'];
    }

    return ['html' => '<div class="successbox"><strong>' . htmlspecialchars(reselleryapi_t('success'), ENT_QUOTES, 'UTF-8')
        . '</strong> ' . htmlspecialchars(reselleryapi_t('ok_updated'), ENT_QUOTES, 'UTF-8') . '</div>'];
}
