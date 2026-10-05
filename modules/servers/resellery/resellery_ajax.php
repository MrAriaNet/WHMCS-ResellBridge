<?php
/**
 * Admin AJAX helpers for the WHMCS ResellBridge server module.
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

require_once dirname(dirname(dirname(__DIR__))) . '/init.php';
require_once __DIR__ . '/lang.php';

use WHMCS\Database\Capsule;

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['adminid']) || !(int) $_SESSION['adminid']) {
    http_response_code(403);
    echo json_encode(['result' => 'error', 'message' => resellery_lang('admin_required')]);
    exit;
}

$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
$serverGroupId = isset($_REQUEST['serverGroupId']) ? (int) $_REQUEST['serverGroupId'] : 0;
$reselleryPid = isset($_REQUEST['reselleryPid']) ? (string) $_REQUEST['reselleryPid'] : '';
$reselleryGid = isset($_REQUEST['reselleryGid']) ? (string) $_REQUEST['reselleryGid'] : '';
$pid = isset($_REQUEST['pid']) ? (int) $_REQUEST['pid'] : 0;

if ($action === 'getReselleryProductGroups') {
    $server = resellery_ajax_getServer($serverGroupId);
    if (!$server) {
        echo json_encode(['message' => resellery_lang('server_not_found')]);
        exit;
    }

    $result = resellery_ajax_call($server, [
        'action' => 'getreselleryproductgroups',
    ]);

    if (isset($result['result']) && $result['result'] === 'error') {
        echo json_encode(['message' => isset($result['message']) ? $result['message'] : resellery_lang('failed_load_groups')]);
        exit;
    }

    $items = isset($result['data']) && is_array($result['data']) ? $result['data'] : [];

    $txt = '<select name="packageconfigoption[1]" id="reselleryRemoteGroup" onchange="getReselleryPid()">';
    $txt .= '<option value="">' . htmlspecialchars(resellery_lang('all_allowed_groups'), ENT_QUOTES, 'UTF-8') . '</option>';
    foreach ($items as $value) {
        $id = isset($value['id']) ? (string) $value['id'] : '';
        $name = isset($value['name']) ? (string) $value['name'] : '';
        $selected = ($id !== '' && $id === (string) $reselleryGid) ? ' selected' : '';
        $txt .= '<option value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>'
            . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $txt .= '</select>';

    echo json_encode(['result' => $txt, 'count' => count($items)]);
    exit;
}

if ($action === 'getReselleryPid') {
    $server = resellery_ajax_getServer($serverGroupId);
    if (!$server) {
        echo json_encode(['message' => resellery_lang('server_not_found')]);
        exit;
    }

    $apiParams = ['action' => 'getresellerypackages'];
    if ($reselleryGid !== '' && ctype_digit((string) $reselleryGid)) {
        $apiParams['gid'] = (int) $reselleryGid;
    }

    $result = resellery_ajax_call($server, $apiParams);

    if (isset($result['result']) && $result['result'] === 'error') {
        echo json_encode(['message' => isset($result['message']) ? $result['message'] : resellery_lang('failed_load_products')]);
        exit;
    }

    $items = [];
    if (isset($result['data']) && is_array($result['data'])) {
        if (isset($result['data'][0]) && is_array($result['data'][0])) {
            $items = $result['data'];
        } elseif (isset($result['data']['id'])) {
            $items = [$result['data']];
        }
    }

    $txt = '<select name="packageconfigoption[3]">';
    if (empty($items)) {
        $txt .= '<option value="">' . htmlspecialchars(resellery_lang('no_products'), ENT_QUOTES, 'UTF-8') . '</option>';
    }
    foreach ($items as $value) {
        $id = isset($value['id']) ? (string) $value['id'] : '';
        $label = isset($value['label']) ? (string) $value['label'] : '';
        if ($label === '') {
            $groupName = isset($value['groupname']) ? (string) $value['groupname'] : '';
            $name = isset($value['name']) ? (string) $value['name'] : '';
            $label = $groupName !== '' ? ($groupName . ' — ' . $name) : $name;
        }
        $selected = ($id === (string) $reselleryPid) ? ' selected' : '';
        $txt .= '<option value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $txt .= '</select>';

    echo json_encode(['result' => $txt, 'count' => count($items)]);
    exit;
}

if ($action === 'getReselleryCustomFields') {
    $server = resellery_ajax_getServer($serverGroupId);
    if (!$server) {
        echo json_encode(['result' => 'error', 'message' => resellery_lang('server_not_found')]);
        exit;
    }

    if ($pid < 1) {
        echo json_encode(['result' => 'error', 'message' => resellery_lang('local_pid_missing')]);
        exit;
    }

    $result = resellery_ajax_call($server, [
        'action' => 'getcustomfields',
        'reselleryPid' => $reselleryPid,
    ]);

    if (isset($result['result']) && $result['result'] === 'error') {
        echo json_encode([
            'result' => 'error',
            'message' => isset($result['message']) ? $result['message'] : resellery_lang('failed_fetch_fields'),
        ]);
        exit;
    }

    if (!isset($result['result']) || $result['result'] !== 'success' || !isset($result['data']) || !is_array($result['data'])) {
        echo json_encode(['result' => 'error', 'message' => resellery_lang('unexpected_response')]);
        exit;
    }

    try {
        Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', $pid)
            ->delete();

        foreach ($result['data'] as $value) {
            Capsule::table('tblcustomfields')->insert([
                'type' => 'product',
                'relid' => $pid,
                'fieldname' => isset($value['fieldname']) ? (string) $value['fieldname'] : '',
                'fieldtype' => isset($value['fieldtype']) ? (string) $value['fieldtype'] : 'text',
                'description' => isset($value['description']) ? (string) $value['description'] : '',
                'fieldoptions' => isset($value['fieldoptions']) ? (string) $value['fieldoptions'] : '',
                'regexpr' => isset($value['regexpr']) ? (string) $value['regexpr'] : '',
                'adminonly' => isset($value['adminonly']) ? (string) $value['adminonly'] : '',
                'required' => isset($value['required']) ? (string) $value['required'] : '',
                'showorder' => isset($value['showorder']) ? (string) $value['showorder'] : '',
                'showinvoice' => isset($value['showinvoice']) ? (string) $value['showinvoice'] : '',
                'sortorder' => 0,
            ]);
        }
    } catch (\Exception $e) {
        echo json_encode(['result' => 'error', 'message' => resellery_lang('fields_save_failed')]);
        exit;
    }

    echo json_encode(['result' => 'success', 'message' => resellery_lang('fields_synced')]);
    exit;
}

echo json_encode(['result' => 'error', 'message' => resellery_lang('unknown_action')]);
exit;

/**
 * @param int $serverGroupId
 * @return object|null
 */
function resellery_ajax_getServer($serverGroupId)
{
    if ($serverGroupId < 1) {
        return null;
    }

    $serverId = Capsule::table('tblservergroupsrel')
        ->where('groupid', $serverGroupId)
        ->value('serverid');

    if (!$serverId) {
        return null;
    }

    return Capsule::table('tblservers')->where('id', (int) $serverId)->first();
}

/**
 * @param object $server
 * @return string
 */
function resellery_ajax_baseUrl($server)
{
    $host = '';
    if (!empty($server->hostname)) {
        $host = trim($server->hostname);
    } elseif (!empty($server->ipaddress)) {
        $host = trim($server->ipaddress);
    }

    $host = rtrim($host, '/');
    if ($host === '') {
        return '';
    }

    if (!preg_match('#^https?://#i', $host)) {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $host = 'http://' . $host;
        } else {
            $host = 'https://' . $host;
        }
    }

    return rtrim($host, '/');
}

/**
 * @param object $server
 * @param array $postfields
 * @return array
 */
function resellery_ajax_call($server, array $postfields)
{
    $base = resellery_ajax_baseUrl($server);
    if ($base === '') {
        return ['result' => 'error', 'message' => resellery_lang('server_host_empty')];
    }

    $password = $server->password;
    if (function_exists('decrypt')) {
        $password = decrypt($password);
    }

    $postfields['username'] = $server->username;
    $postfields['password'] = md5($password);
    $postfields['accesshash'] = $server->accesshash;
    $postfields['responsetype'] = 'json';
    $postfields['language'] = resellery_api_language_param();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base . '/includes/api_resellery.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return ['result' => 'error', 'message' => resellery_lang('connect_failed') . ': ' . $errno . ' - ' . $error];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['result' => 'error', 'message' => resellery_lang('invalid_json')];
    }

    return $decoded;
}
