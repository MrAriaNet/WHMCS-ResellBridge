<?php
/**
 * WHMCS ResellBridge — server module for the RESELLER WHMCS installation.
 *
 * Install to: modules/servers/resellery/
 * Connects to the main WHMCS via /includes/api_resellery.php
 * Languages: English + Farsi (modules/servers/resellery/lang/)
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lang.php';

/**
 * @return array
 */
function resellery_MetaData()
{
    return [
        'DisplayName' => resellery_lang('display_name'),
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'Author' => 'Aria Jahangiri Far',
    ];
}

/**
 * @return array
 */
function resellery_ConfigOptions()
{
    $jsPath = ROOTDIR . '/modules/servers/resellery/resellery.js';
    $js = is_readable($jsPath) ? file_get_contents($jsPath) : '';

    $langForJs = [
        'unable_groups' => resellery_lang('js_unable_groups'),
        'unable_products' => resellery_lang('js_unable_products'),
        'server_missing' => resellery_lang('js_server_missing'),
        'server_missing_groups' => resellery_lang('js_server_missing_groups'),
        'done' => resellery_lang('js_done'),
        'failed' => resellery_lang('js_failed'),
    ];
    $putJs = '<script>window.reselleryLang = ' . json_encode($langForJs, JSON_UNESCAPED_UNICODE)
        . ';' . $js . '</script>';

    $departmentNames = [''];
    $departmentStatus = [''];

    try {
        if (Capsule::schema()->hasTable('tblticketdepartments')
            && Capsule::schema()->hasColumn('tblticketdepartments', 'cartable')
        ) {
            $deps = Capsule::table('tblticketdepartments')->where('cartable', 1)->pluck('name');
            foreach ($deps as $name) {
                $departmentNames[] = $name;
            }
        }
        if (Capsule::schema()->hasTable('tblticketstatuses')
            && Capsule::schema()->hasColumn('tblticketstatuses', 'cartable')
        ) {
            $statuses = Capsule::table('tblticketstatuses')->where('cartable', 1)->pluck('title');
            foreach ($statuses as $title) {
                $departmentStatus[] = $title;
            }
        }
    } catch (\Exception $e) {
        // Optional UI fields
    }

    resellery_ensureMappingTable();

    return [
        'productType' => [
            'FriendlyName' => resellery_lang('remote_product_group'),
            'Type' => 'text',
            'Size' => '10',
            'Description' => $putJs
                . " <input type='button' value='" . htmlspecialchars(resellery_lang('load_groups'), ENT_QUOTES, 'UTF-8')
                . "' onclick='getReselleryProductGroups()'>"
                . ' <span class="text-muted">' . htmlspecialchars(resellery_lang('remote_product_group_help'), ENT_QUOTES, 'UTF-8') . '</span>',
        ],
        'serviceType' => [
            'FriendlyName' => resellery_lang('service_type'),
            'Type' => 'dropdown',
            'Options' => 'Service,Volume',
        ],
        'pid' => [
            'FriendlyName' => resellery_lang('remote_product'),
            'Type' => 'text',
            'Size' => '10',
            'Description' => " <input type='button' value='"
                . htmlspecialchars(resellery_lang('load_products'), ENT_QUOTES, 'UTF-8')
                . "' onclick='getReselleryPid()'>"
                . ' <span class="text-muted">' . htmlspecialchars(resellery_lang('remote_product_help'), ENT_QUOTES, 'UTF-8') . '</span>',
        ],
        'getcustomfield' => [
            'FriendlyName' => resellery_lang('sync_custom_fields'),
            'Description' => "<input type='button' value='"
                . htmlspecialchars(resellery_lang('fetch_fields'), ENT_QUOTES, 'UTF-8')
                . "' onclick='getCustomFields()'>",
        ],
        'departments' => [
            'FriendlyName' => resellery_lang('start_department'),
            'Type' => 'dropdown',
            'Options' => implode(',', $departmentNames),
        ],
        'departments_status' => [
            'FriendlyName' => resellery_lang('start_department_status'),
            'Type' => 'dropdown',
            'Options' => implode(',', $departmentStatus),
        ],
        'samfony_userid' => [
            'FriendlyName' => resellery_lang('remote_client_id'),
            'Type' => 'text',
            'Description' => resellery_lang('remote_client_id_help'),
        ],
    ];
}

/**
 * Create mapping table if missing.
 */
function resellery_ensureMappingTable()
{
    try {
        if (Capsule::schema()->hasTable('tblsamfonyresellery')) {
            return;
        }
        Capsule::schema()->create('tblsamfonyresellery', function ($table) {
            $table->increments('id');
            $table->integer('local_orderid')->default(0);
            $table->integer('local_serviceid')->default(0);
            $table->string('remote_orderid', 32)->default('0');
            $table->string('remote_serviceid', 32)->default('0');
            $table->timestamp('date')->useCurrent();
            $table->index('local_serviceid');
        });
    } catch (\Exception $e) {
        full_query("CREATE TABLE IF NOT EXISTS `tblsamfonyresellery` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `local_orderid` int(11) NOT NULL DEFAULT 0,
            `local_serviceid` int(11) NOT NULL DEFAULT 0,
            `remote_orderid` varchar(32) NOT NULL DEFAULT '0',
            `remote_serviceid` varchar(32) NOT NULL DEFAULT '0',
            `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `local_serviceid` (`local_serviceid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

/**
 * @param array $params
 * @return array
 */
function resellery_TestConnection(array $params)
{
    $result = resellery_callApi($params, ['action' => 'ping']);

    if (isset($result['result']) && $result['result'] === 'success') {
        return ['success' => true, 'error' => ''];
    }

    $message = isset($result['message']) ? $result['message'] : resellery_lang('connect_failed');
    return ['success' => false, 'error' => $message];
}

/**
 * Safe subset of client fields for upstream provisioning.
 *
 * @param object|array $client
 * @return array
 */
function resellery_safeClientPayload($client)
{
    $client = (array) $client;
    $allow = [
        'firstname', 'lastname', 'companyname', 'email',
        'address1', 'address2', 'city', 'state', 'postcode', 'country',
        'phonenumber', 'tax_id',
    ];
    $out = [];
    foreach ($allow as $key) {
        if (isset($client[$key])) {
            $out[$key] = $client[$key];
        }
    }
    return $out;
}

/**
 * @param array $params
 * @return string
 */
function resellery_CreateAccount(array $params)
{
    $userid = (int) $params['userid'];
    $serviceid = (int) $params['serviceid'];
    $pid = $params['configoption3'];
    $samfonyUserid = isset($params['configoption7']) ? $params['configoption7'] : '';

    $existing = Capsule::table('tblsamfonyresellery')->where('local_serviceid', $serviceid)->first();
    if ($existing && !empty($existing->remote_serviceid) && $existing->remote_serviceid !== '0') {
        return 'success';
    }

    $hostingData = Capsule::table('tblhosting')->where('id', $serviceid)->first(['orderid', 'billingcycle']);
    if (!$hostingData) {
        return resellery_lang('local_service_missing');
    }

    $client = Capsule::table('tblclients')->where('id', $userid)->first();
    if (!$client) {
        return resellery_lang('local_client_missing');
    }

    $clientData = base64_encode(json_encode(resellery_safeClientPayload($client)));
    $customfields = base64_encode(serialize(isset($params['customfields']) ? $params['customfields'] : []));

    $apiParams = [
        'action' => 'reselleryadd',
        'clientData' => $clientData,
        'pid' => $pid,
        'serviceusername' => isset($params['username']) ? $params['username'] : '',
        'servicepassword' => isset($params['password']) ? $params['password'] : '',
        'domain' => isset($params['domain']) ? $params['domain'] : '',
        'ns1' => isset($params['ns1']) ? $params['ns1'] : '',
        'ns2' => isset($params['ns2']) ? $params['ns2'] : '',
        'customfields' => $customfields,
        'samfony_userid' => $samfonyUserid,
        'billingcycle' => $hostingData->billingcycle,
    ];

    $result = resellery_callApi($params, $apiParams);

    if (isset($result['result']) && $result['result'] === 'success') {
        if ($existing) {
            Capsule::table('tblsamfonyresellery')->where('id', $existing->id)->update([
                'local_orderid' => (int) $hostingData->orderid,
                'remote_orderid' => (string) $result['orderid'],
                'remote_serviceid' => (string) $result['serviceid'],
            ]);
        } else {
            Capsule::table('tblsamfonyresellery')->insert([
                'local_orderid' => (int) $hostingData->orderid,
                'local_serviceid' => $serviceid,
                'remote_orderid' => (string) $result['orderid'],
                'remote_serviceid' => (string) $result['serviceid'],
            ]);
        }

        Capsule::table('tblhosting')->where('id', $serviceid)->update([
            'dedicatedip' => isset($result['dedicatedip']) ? $result['dedicatedip'] : '',
            'assignedips' => isset($result['assignedips']) ? $result['assignedips'] : '',
        ]);

        return 'success';
    }

    return resellery_formatError($result);
}

/**
 * @param array $params
 * @return array|null
 */
function resellery_AdminServicesTabFields(array $params)
{
    $remoteServiceId = resellery_getRemoteServiceId((int) $params['serviceid']);
    if ($remoteServiceId === '') {
        return [resellery_lang('details') => resellery_lang('no_remote_mapping')];
    }

    $result = resellery_callApi($params, [
        'action' => 'resellerygetoverview',
        'serviceid' => $remoteServiceId,
    ]);

    if (isset($result['result']) && $result['result'] === 'success') {
        return [resellery_lang('details') => $result['data']];
    }

    return [resellery_lang('details') => resellery_formatError($result)];
}

/**
 * @param array $params
 * @return array
 */
function resellery_ClientArea(array $params)
{
    $tab = resellery_AdminServicesTabFields($params);
    $detailsKey = resellery_lang('details');
    $fulltable = isset($tab[$detailsKey]) ? $tab[$detailsKey] : '';
    $fulltable = str_replace(
        '<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form">',
        '<table class="blueTable">',
        $fulltable
    );

    $serviceid = (int) $params['serviceid'];
    $remote = Capsule::table('tblsamfonyresellery')->where('local_serviceid', $serviceid)->first();

    $iframe = '';
    if ($remote) {
        $result = resellery_callApi($params, [
            'action' => 'resellerycontrolpanel',
            'serviceid' => $remote->remote_serviceid,
            'orderid' => $remote->remote_orderid,
        ]);
        if (isset($result['result']) && $result['result'] === 'success') {
            $iframe = $result['data'];
        }
    }

    return [
        'templatefile' => 'clientarea',
        'vars' => [
            'fulltable' => $fulltable,
            'iframe' => $iframe,
        ],
    ];
}

/**
 * @param array $params
 * @return string
 */
function resellery_AdminLink(array $params)
{
    $result = resellery_callApi($params, ['action' => 'ping']);
    $ok = isset($result['result']) && $result['result'] === 'success';
    $label = htmlspecialchars(resellery_lang('check_api'), ENT_QUOTES, 'UTF-8');
    $msg = $ok
        ? htmlspecialchars(isset($result['message']) ? $result['message'] : 'OK', ENT_QUOTES, 'UTF-8')
        : htmlspecialchars(resellery_formatError($result), ENT_QUOTES, 'UTF-8');
    $color = $ok ? 'green' : 'red';

    return '<button type="button" class="btn btn-default" onclick="alert(\''
        . addslashes($msg) . '\')">' . $label . '</button>'
        . ' <span style="color:' . $color . '">' . ($ok ? 'OK' : 'Error') . '</span>';
}

/**
 * @param array $params
 * @return string
 */
function resellery_TerminateAccount(array $params)
{
    $remoteServiceId = resellery_getRemoteServiceId((int) $params['serviceid']);
    if ($remoteServiceId === '') {
        return resellery_lang('no_remote_mapping');
    }

    $reason = isset($params['suspendreason']) ? $params['suspendreason'] : '';
    $result = resellery_callApi($params, [
        'action' => 'reselleryterminateservice',
        'serviceid' => $remoteServiceId,
        'reason' => $reason,
    ]);

    return (isset($result['result']) && $result['result'] === 'success')
        ? 'success'
        : resellery_formatError($result);
}

/**
 * @param array $params
 * @return string
 */
function resellery_UnsuspendAccount(array $params)
{
    $remoteServiceId = resellery_getRemoteServiceId((int) $params['serviceid']);
    if ($remoteServiceId === '') {
        return resellery_lang('no_remote_mapping');
    }

    $result = resellery_callApi($params, [
        'action' => 'reselleryunsuspendservice',
        'serviceid' => $remoteServiceId,
    ]);

    return (isset($result['result']) && $result['result'] === 'success')
        ? 'success'
        : resellery_formatError($result);
}

/**
 * @param array $params
 * @return string
 */
function resellery_SuspendAccount(array $params)
{
    $remoteServiceId = resellery_getRemoteServiceId((int) $params['serviceid']);
    if ($remoteServiceId === '') {
        return resellery_lang('no_remote_mapping');
    }

    $reason = isset($params['suspendreason']) ? $params['suspendreason'] : '';
    $result = resellery_callApi($params, [
        'action' => 'resellerysuspendservice',
        'serviceid' => $remoteServiceId,
        'reason' => $reason,
    ]);

    return (isset($result['result']) && $result['result'] === 'success')
        ? 'success'
        : resellery_formatError($result);
}

/**
 * @param int $localServiceId
 * @return string
 */
function resellery_getRemoteServiceId($localServiceId)
{
    $value = Capsule::table('tblsamfonyresellery')
        ->where('local_serviceid', (int) $localServiceId)
        ->orderBy('id', 'desc')
        ->value('remote_serviceid');

    return $value !== null ? (string) $value : '';
}

/**
 * @param array $params
 * @return string
 */
function resellery_serverBaseUrl(array $params)
{
    $host = '';
    if (!empty($params['serverhostname'])) {
        $host = trim($params['serverhostname']);
    } elseif (!empty($params['serverip'])) {
        $host = trim($params['serverip']);
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
 * @param array $serverParams
 * @param array $apiParams
 * @return array
 */
function resellery_callApi(array $serverParams, array $apiParams)
{
    $base = resellery_serverBaseUrl($serverParams);
    if ($base === '') {
        return ['result' => 'error', 'code' => 400, 'message' => resellery_lang('server_not_configured')];
    }

    $apiParams['responsetype'] = 'json';
    $apiParams['username'] = isset($serverParams['serverusername']) ? $serverParams['serverusername'] : '';
    $apiParams['password'] = md5(isset($serverParams['serverpassword']) ? $serverParams['serverpassword'] : '');
    $apiParams['accesshash'] = isset($serverParams['serveraccesshash']) ? $serverParams['serveraccesshash'] : '';
    $apiParams['language'] = resellery_api_language_param();

    $url = $base . '/includes/api_resellery.php';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiParams));
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return [
            'result' => 'error',
            'code' => 502,
            'message' => resellery_lang('connect_failed') . ': ' . $errno . ' - ' . $error,
        ];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return [
            'result' => 'error',
            'code' => $httpCode ?: 500,
            'message' => resellery_lang('invalid_json'),
        ];
    }

    return $decoded;
}

/**
 * @param array $result
 * @return string
 */
function resellery_formatError(array $result)
{
    $code = isset($result['code']) ? $result['code'] : '';
    $message = isset($result['message']) ? $result['message'] : 'Unknown error';
    if ($code !== '') {
        return '#' . $code . ':' . $message;
    }
    return $message;
}
