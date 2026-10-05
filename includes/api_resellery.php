<?php
/**
 * WHMCS ResellBridge API HTTP entrypoint for the MAIN WHMCS.
 *
 * Install this file to: includes/api_resellery.php
 * Requires the reselleryapi addon to be activated and configured.
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(__DIR__) . '/init.php';
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'result' => 'error',
        'code' => 500,
        'message' => 'Unable to bootstrap WHMCS.',
    ]);
    exit;
}

$libDir = ROOTDIR . '/modules/addons/reselleryapi/lib';
require_once $libDir . '/Messages.php';
require_once $libDir . '/ResellerAccounts.php';
require_once $libDir . '/Auth.php';
require_once $libDir . '/Provisioning.php';
require_once $libDir . '/ApiHandler.php';

use ReselleryApi\ApiHandler;

// Prefer POST for credentials; allow non-auth fields from GET for convenience.
$request = $_POST;
foreach ($_GET as $key => $value) {
    if (!isset($request[$key]) && !in_array($key, ['username', 'password', 'accesshash'], true)) {
        $request[$key] = $value;
    }
}

try {
    $response = ApiHandler::handle($request);
} catch (\Throwable $e) {
    $response = [
        'result' => 'error',
        'code' => 500,
        'message' => 'Internal API error.',
    ];
    if (function_exists('logActivity')) {
        logActivity('WHMCS ResellBridge API error: ' . $e->getMessage());
    }
}

if (!is_array($response)) {
    $response = [
        'result' => 'error',
        'code' => 500,
        'message' => 'Invalid API response.',
    ];
}

$status = 200;
if (isset($response['result']) && $response['result'] === 'error') {
    $code = isset($response['code']) ? (int) $response['code'] : 400;
    if ($code >= 400 && $code < 600) {
        $status = $code;
    } else {
        $status = 400;
    }
}
http_response_code($status);

echo json_encode($response);
exit;
