<?php
/**
 * Provisioning helpers for WHMCS ResellBridge on the main WHMCS.
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

namespace ReselleryApi;

use WHMCS\Database\Capsule;

class Provisioning
{
    /**
     * List allowed product groups for the authenticated reseller.
     * Per-reseller groups override the global default when set.
     * If neither is set, all product groups are returned.
     *
     * @param array|null $reseller
     * @return array
     */
    public static function getProductGroups($reseller = null)
    {
        $allowed = self::allowedGroupIds($reseller);

        $query = Capsule::table('tblproductgroups')->orderBy('order', 'asc')->orderBy('name', 'asc');
        if (!empty($allowed)) {
            $query->whereIn('id', $allowed);
        }

        $groups = $query->get(['id', 'name', 'headline', 'hidden']);

        $data = [];
        foreach ($groups as $group) {
            if (isset($group->hidden) && (string) $group->hidden === '1') {
                continue;
            }
            $data[] = [
                'id' => (int) $group->id,
                'name' => (string) $group->name,
            ];
        }

        return ['result' => 'success', 'data' => $data];
    }

    /**
     * List sellable products from allowed product groups.
     *
     * @param int $gid Optional filter to a single product group ID
     * @param array|null $reseller
     * @return array
     */
    public static function getPackages($gid = 0, $reseller = null)
    {
        $allowed = self::allowedGroupIds($reseller);
        $gid = (int) $gid;

        if ($gid > 0) {
            if (!empty($allowed) && !in_array($gid, $allowed, true)) {
                return self::error(403, Messages::get('group_not_allowed'));
            }
            $groupFilter = [$gid];
        } elseif (!empty($allowed)) {
            $groupFilter = $allowed;
        } else {
            $groupFilter = null;
        }

        $query = Capsule::table('tblproducts as p')
            ->leftJoin('tblproductgroups as g', 'g.id', '=', 'p.gid')
            ->orderBy('g.order', 'asc')
            ->orderBy('g.name', 'asc')
            ->orderBy('p.name', 'asc')
            ->select([
                'p.id',
                'p.name',
                'p.gid',
                'g.name as groupname',
            ]);

        if ($groupFilter !== null) {
            $query->whereIn('p.gid', $groupFilter);
        }

        if (Capsule::schema()->hasColumn('tblproducts', 'hidden')) {
            $query->where(function ($q) {
                $q->where('p.hidden', 0)->orWhereNull('p.hidden')->orWhere('p.hidden', '');
            });
        }

        $products = $query->get();

        $data = [];
        foreach ($products as $product) {
            $groupName = (string) ($product->groupname ?: Messages::get('ungrouped'));
            $data[] = [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'gid' => (int) $product->gid,
                'groupname' => $groupName,
                'label' => $groupName . ' — ' . (string) $product->name,
            ];
        }

        return ['result' => 'success', 'data' => $data];
    }

    /**
     * Product group IDs allowed for the current reseller.
     * Prefer per-reseller groups; otherwise use the global addon default.
     *
     * @param array|null $reseller
     * @return int[]
     */
    protected static function allowedGroupIds($reseller = null)
    {
        if ($reseller === null) {
            $reseller = Auth::currentReseller();
        }

        if (is_array($reseller) && trim((string) ($reseller['allowed_product_groups'] ?? '')) !== '') {
            return ResellerAccounts::parseGroupIds($reseller['allowed_product_groups']);
        }

        $settings = Auth::loadSettings();
        if ($settings === null) {
            return [];
        }

        return ResellerAccounts::parseGroupIds($settings['allowed_product_groups'] ?? '');
    }

    /**
     * @param array|null $reseller
     * @param int $serviceId
     * @return array|null Error payload or null if OK
     */
    protected static function assertCanManage($reseller, $serviceId)
    {
        if ($reseller === null) {
            $reseller = Auth::currentReseller();
        }
        if (!is_array($reseller)) {
            return self::error(401, Messages::get('unauthenticated'));
        }
        if (!ResellerAccounts::canManageService($reseller, (int) $serviceId)) {
            return self::error(403, Messages::get('not_owned'));
        }
        return null;
    }

    /**
     * Custom fields for a product.
     *
     * @param int $pid
     * @param array|null $reseller
     * @return array
     */
    public static function getCustomFields($pid, $reseller = null)
    {
        $pid = (int) $pid;
        if ($pid < 1) {
            return self::error(400, Messages::get('invalid_pid'));
        }

        $product = Capsule::table('tblproducts')->where('id', $pid)->first();
        if (!$product) {
            return self::error(404, Messages::get('product_not_found'));
        }

        $allowed = self::allowedGroupIds($reseller);
        if (!empty($allowed) && !in_array((int) $product->gid, $allowed, true)) {
            return self::error(403, Messages::get('product_group_not_allowed'));
        }

        $fields = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', $pid)
            ->orderBy('sortorder', 'asc')
            ->get();

        $data = [];
        foreach ($fields as $field) {
            $data[] = [
                'fieldname' => (string) $field->fieldname,
                'fieldtype' => (string) $field->fieldtype,
                'description' => (string) $field->description,
                'required' => (string) $field->required,
                'fieldoptions' => (string) $field->fieldoptions,
                'regexpr' => (string) $field->regexpr,
                'adminonly' => (string) $field->adminonly,
                'showorder' => (string) $field->showorder,
                'showinvoice' => (string) $field->showinvoice,
            ];
        }

        return ['result' => 'success', 'data' => $data];
    }

    /**
     * Create client (if needed), order, and service.
     *
     * @param array $request
     * @param array|null $reseller
     * @return array
     */
    public static function addService(array $request, $reseller = null)
    {
        if ($reseller === null) {
            $reseller = Auth::currentReseller();
        }

        $pid = isset($request['pid']) ? (int) $request['pid'] : 0;
        if ($pid < 1) {
            return self::error(400, Messages::get('invalid_pid'));
        }

        $product = Capsule::table('tblproducts')->where('id', $pid)->first();
        if (!$product) {
            return self::error(404, Messages::get('product_not_found'));
        }

        $allowed = self::allowedGroupIds($reseller);
        if (!empty($allowed) && !in_array((int) $product->gid, $allowed, true)) {
            return self::error(403, Messages::get('product_group_not_allowed'));
        }

        $billingcycle = self::normalizeBillingCycle(
            isset($request['billingcycle']) ? (string) $request['billingcycle'] : 'monthly'
        );

        $clientId = 0;
        $samfonyUserId = isset($request['samfony_userid']) ? trim((string) $request['samfony_userid']) : '';
        if ($samfonyUserId !== '' && ctype_digit($samfonyUserId)) {
            $clientId = (int) $samfonyUserId;
            if (!Capsule::table('tblclients')->where('id', $clientId)->exists()) {
                return self::error(404, Messages::get('remote_client_missing'));
            }
        } else {
            $clientResult = self::resolveClient($request);
            if ($clientResult['result'] !== 'success') {
                return $clientResult;
            }
            $clientId = (int) $clientResult['userid'];
        }

        $customfields = self::decodeCustomFields(
            isset($request['customfields']) ? (string) $request['customfields'] : '',
            $pid
        );

        $domain = isset($request['domain']) ? (string) $request['domain'] : '';
        $serviceusername = isset($request['serviceusername']) ? (string) $request['serviceusername'] : '';
        $servicepassword = isset($request['servicepassword']) ? (string) $request['servicepassword'] : '';
        $ns1 = isset($request['ns1']) ? (string) $request['ns1'] : '';
        $ns2 = isset($request['ns2']) ? (string) $request['ns2'] : '';

        $paymentmethod = self::defaultPaymentMethod();

        $orderParams = [
            'clientid' => $clientId,
            'pid' => [$pid],
            'billingcycle' => [$billingcycle],
            'noinvoice' => true,
            'noemail' => true,
            'paymentmethod' => $paymentmethod,
        ];

        if ($domain !== '') {
            $orderParams['domain'] = [$domain];
        }
        if ($serviceusername !== '') {
            $orderParams['username'] = [$serviceusername];
        }
        if ($servicepassword !== '') {
            $orderParams['password'] = [$servicepassword];
        }
        if ($ns1 !== '') {
            $orderParams['nameserver1'] = $ns1;
        }
        if ($ns2 !== '') {
            $orderParams['nameserver2'] = $ns2;
        }
        if (!empty($customfields)) {
            $orderParams['customfields'] = [base64_encode(serialize($customfields))];
        }

        $order = localAPI('AddOrder', $orderParams);
        if (!isset($order['result']) || $order['result'] !== 'success') {
            $msg = isset($order['message']) ? $order['message'] : Messages::get('addorder_failed');
            return self::error(500, $msg);
        }

        $orderId = (int) $order['orderid'];
        $accept = localAPI('AcceptOrder', [
            'orderid' => $orderId,
            'autosetup' => true,
            'sendemail' => false,
        ]);

        if (!isset($accept['result']) || $accept['result'] !== 'success') {
            if (function_exists('localAPI')) {
                localAPI('CancelOrder', ['orderid' => $orderId]);
            }
            $msg = isset($accept['message']) ? $accept['message'] : Messages::get('acceptorder_failed');
            return self::error(500, $msg);
        }

        $service = Capsule::table('tblhosting')
            ->where('orderid', $orderId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$service) {
            return self::error(500, Messages::get('service_row_missing'));
        }

        if (is_array($reseller)) {
            ResellerAccounts::linkService((int) ($reseller['id'] ?? 0), (int) $service->id, $orderId);
        }

        return [
            'result' => 'success',
            'orderid' => (string) $orderId,
            'serviceid' => (string) $service->id,
            'dedicatedip' => (string) $service->dedicatedip,
            'assignedips' => (string) $service->assignedips,
        ];
    }

    /**
     * @param int $serviceId
     * @param string $reason
     * @param array|null $reseller
     * @return array
     */
    public static function suspendService($serviceId, $reason = '', $reseller = null)
    {
        $serviceId = (int) $serviceId;
        if ($serviceId < 1) {
            return self::error(400, Messages::get('invalid_service'));
        }

        $denied = self::assertCanManage($reseller, $serviceId);
        if ($denied !== null) {
            return $denied;
        }

        $result = localAPI('ModuleSuspend', [
            'serviceid' => $serviceId,
            'suspendreason' => $reason !== '' ? $reason : Messages::get('suspend_reason'),
        ]);

        if (!isset($result['result']) || $result['result'] !== 'success') {
            $fallback = localAPI('UpdateClientProduct', [
                'serviceid' => $serviceId,
                'status' => 'Suspended',
            ]);
            if (!isset($fallback['result']) || $fallback['result'] !== 'success') {
                $msg = isset($result['message']) ? $result['message'] : Messages::get('suspend_failed');
                return self::error(500, $msg);
            }
        }

        return ['result' => 'success'];
    }

    /**
     * @param int $serviceId
     * @param array|null $reseller
     * @return array
     */
    public static function unsuspendService($serviceId, $reseller = null)
    {
        $serviceId = (int) $serviceId;
        if ($serviceId < 1) {
            return self::error(400, Messages::get('invalid_service'));
        }

        $denied = self::assertCanManage($reseller, $serviceId);
        if ($denied !== null) {
            return $denied;
        }

        $result = localAPI('ModuleUnsuspend', [
            'serviceid' => $serviceId,
        ]);

        if (!isset($result['result']) || $result['result'] !== 'success') {
            $fallback = localAPI('UpdateClientProduct', [
                'serviceid' => $serviceId,
                'status' => 'Active',
            ]);
            if (!isset($fallback['result']) || $fallback['result'] !== 'success') {
                $msg = isset($result['message']) ? $result['message'] : Messages::get('unsuspend_failed');
                return self::error(500, $msg);
            }
        }

        return ['result' => 'success'];
    }

    /**
     * @param int $serviceId
     * @param string $reason
     * @param array|null $reseller
     * @return array
     */
    public static function terminateService($serviceId, $reason = '', $reseller = null)
    {
        $serviceId = (int) $serviceId;
        if ($serviceId < 1) {
            return self::error(400, Messages::get('invalid_service'));
        }

        $denied = self::assertCanManage($reseller, $serviceId);
        if ($denied !== null) {
            return $denied;
        }

        $result = localAPI('ModuleTerminate', [
            'serviceid' => $serviceId,
        ]);

        if (!isset($result['result']) || $result['result'] !== 'success') {
            $fallback = localAPI('UpdateClientProduct', [
                'serviceid' => $serviceId,
                'status' => 'Terminated',
            ]);
            if (!isset($fallback['result']) || $fallback['result'] !== 'success') {
                $msg = isset($result['message']) ? $result['message'] : Messages::get('terminate_failed');
                return self::error(500, $msg);
            }
        }

        return ['result' => 'success'];
    }

    /**
     * HTML overview table for a service.
     *
     * @param int $serviceId
     * @param array|null $reseller
     * @return array
     */
    public static function getOverview($serviceId, $reseller = null)
    {
        $serviceId = (int) $serviceId;
        $service = self::loadService($serviceId);
        if (!$service) {
            return self::error(404, Messages::get('service_not_found'));
        }

        $denied = self::assertCanManage($reseller, $serviceId);
        if ($denied !== null) {
            return $denied;
        }

        $productName = Capsule::table('tblproducts')->where('id', $service->packageid)->value('name');
        $rows = [
            Messages::get('label_service_id') => $service->id,
            Messages::get('label_product') => $productName ?: $service->packageid,
            Messages::get('label_domain') => $service->domain,
            Messages::get('label_status') => $service->domainstatus,
            Messages::get('label_username') => $service->username,
            Messages::get('label_billing') => $service->billingcycle,
            Messages::get('label_nextdue') => $service->nextduedate,
            Messages::get('label_dedicated_ip') => $service->dedicatedip,
            Messages::get('label_assigned_ips') => $service->assignedips,
            Messages::get('label_regdate') => $service->regdate,
        ];

        $html = '<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form"><tbody>';
        foreach ($rows as $label => $value) {
            $html .= '<tr><td class="fieldlabel">' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8')
                . '</td><td class="fieldarea">' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
                . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return ['result' => 'success', 'data' => $html];
    }

    /**
     * Control panel snippet for client area.
     *
     * @param int $serviceId
     * @param int $orderId
     * @param array|null $reseller
     * @return array
     */
    public static function getControlPanel($serviceId, $orderId = 0, $reseller = null)
    {
        $serviceId = (int) $serviceId;
        $service = self::loadService($serviceId);
        if (!$service) {
            return self::error(404, Messages::get('service_not_found'));
        }

        $denied = self::assertCanManage($reseller, $serviceId);
        if ($denied !== null) {
            return $denied;
        }

        $productName = Capsule::table('tblproducts')->where('id', $service->packageid)->value('name');
        $html = '<div class="resellery-control-panel">'
            . '<h4>' . htmlspecialchars(Messages::get('cp_title'), ENT_QUOTES, 'UTF-8') . '</h4>'
            . '<p>' . htmlspecialchars(Messages::get('cp_order'), ENT_QUOTES, 'UTF-8') . ': '
            . htmlspecialchars((string) ($orderId ?: $service->orderid), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . htmlspecialchars(Messages::get('cp_product'), ENT_QUOTES, 'UTF-8') . ': '
            . htmlspecialchars((string) $productName, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . htmlspecialchars(Messages::get('cp_status'), ENT_QUOTES, 'UTF-8') . ': '
            . htmlspecialchars((string) $service->domainstatus, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . htmlspecialchars(Messages::get('cp_domain'), ENT_QUOTES, 'UTF-8') . ': '
            . htmlspecialchars((string) $service->domain, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . htmlspecialchars(Messages::get('cp_note'), ENT_QUOTES, 'UTF-8') . '</p>'
            . '</div>';

        return ['result' => 'success', 'data' => $html];
    }

    /**
     * Lightweight ping for Test Connection.
     *
     * @param array|null $reseller
     * @return array
     */
    public static function ping($reseller = null)
    {
        $payload = [
            'result' => 'success',
            'message' => Messages::get('ping_ok'),
            'version' => '1.2.0',
            'multi_reseller' => true,
            'language' => Messages::language(),
        ];
        if (is_array($reseller)) {
            $payload['reseller'] = [
                'id' => isset($reseller['id']) ? (int) $reseller['id'] : 0,
                'name' => isset($reseller['name']) ? (string) $reseller['name'] : '',
                'is_global' => !empty($reseller['is_global']),
            ];
        }
        return $payload;
    }

    /**
     * @param array $request
     * @return array
     */
    protected static function resolveClient(array $request)
    {
        $raw = isset($request['clientData']) ? (string) $request['clientData'] : '';
        $decoded = json_decode(base64_decode($raw, true) ?: '', true);
        if (!is_array($decoded)) {
            // Try without strict base64
            $decoded = json_decode(base64_decode($raw) ?: '', true);
        }
        if (!is_array($decoded)) {
            return self::error(400, Messages::get('invalid_client_data'));
        }

        $email = isset($decoded['email']) ? trim((string) $decoded['email']) : '';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return self::error(400, Messages::get('invalid_email'));
        }

        $existing = Capsule::table('tblclients')->where('email', $email)->first();
        if ($existing) {
            return ['result' => 'success', 'userid' => (int) $existing->id];
        }

        $password = self::randomPassword();
        $add = localAPI('AddClient', [
            'firstname' => self::pick($decoded, 'firstname', 'Reseller'),
            'lastname' => self::pick($decoded, 'lastname', 'Client'),
            'companyname' => self::pick($decoded, 'companyname', ''),
            'email' => $email,
            'address1' => self::pick($decoded, 'address1', 'N/A'),
            'address2' => self::pick($decoded, 'address2', ''),
            'city' => self::pick($decoded, 'city', 'N/A'),
            'state' => self::pick($decoded, 'state', 'N/A'),
            'postcode' => self::pick($decoded, 'postcode', '00000'),
            'country' => self::pick($decoded, 'country', 'US'),
            'phonenumber' => self::pick($decoded, 'phonenumber', '0000000000'),
            'password2' => $password,
            'skipvalidation' => true,
            'noemail' => true,
        ]);

        if (!isset($add['result']) || $add['result'] !== 'success') {
            $msg = isset($add['message']) ? $add['message'] : Messages::get('addclient_failed');
            return self::error(500, $msg);
        }

        return ['result' => 'success', 'userid' => (int) $add['clientid']];
    }

    /**
     * @param string $encoded
     * @return array
     */
    protected static function decodeCustomFields($encoded, $pid = 0)
    {
        if ($encoded === '') {
            return [];
        }

        $raw = base64_decode($encoded, true);
        if ($raw === false) {
            $raw = base64_decode($encoded);
        }
        if ($raw === false || $raw === '') {
            return [];
        }

        $data = @unserialize($raw);
        if (!is_array($data)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $data = $json;
            } else {
                return [];
            }
        }

        $pid = (int) $pid;
        $mapped = [];
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $mapped[(int) $key] = $value;
                continue;
            }
            $query = Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('fieldname', (string) $key);
            if ($pid > 0) {
                $query->where('relid', $pid);
            }
            $fieldId = $query->value('id');
            if ($fieldId) {
                $mapped[(int) $fieldId] = $value;
            } else {
                $mapped[(string) $key] = $value;
            }
        }

        return $mapped;
    }

    /**
     * @param int $serviceId
     * @return object|null
     */
    protected static function loadService($serviceId)
    {
        if ($serviceId < 1) {
            return null;
        }
        return Capsule::table('tblhosting')->where('id', $serviceId)->first();
    }

    /**
     * @param string $cycle
     * @return string
     */
    protected static function normalizeBillingCycle($cycle)
    {
        $cycle = strtolower(trim($cycle));
        $map = [
            'free account' => 'free',
            'free' => 'free',
            'onetime' => 'onetime',
            'one time' => 'onetime',
            'monthly' => 'monthly',
            'quarterly' => 'quarterly',
            'semiannually' => 'semiannually',
            'semi-annually' => 'semiannually',
            'annually' => 'annually',
            'biennially' => 'biennially',
            'triennially' => 'triennially',
        ];
        return isset($map[$cycle]) ? $map[$cycle] : 'monthly';
    }

    /**
     * @return string
     */
    protected static function defaultPaymentMethod()
    {
        $gateway = Capsule::table('tblpaymentgateways')
            ->where('setting', 'name')
            ->orderBy('order', 'asc')
            ->value('gateway');
        return $gateway ? (string) $gateway : 'banktransfer';
    }

    /**
     * @param array $data
     * @param string $key
     * @param string $default
     * @return string
     */
    protected static function pick(array $data, $key, $default)
    {
        if (!isset($data[$key])) {
            return $default;
        }
        $value = trim((string) $data[$key]);
        return $value !== '' ? $value : $default;
    }

    /**
     * @return string
     */
    protected static function randomPassword($length = 16)
    {
        return substr(bin2hex(random_bytes(16)), 0, $length);
    }

    /**
     * @param int $code
     * @param string $message
     * @return array
     */
    protected static function error($code, $message)
    {
        return [
            'result' => 'error',
            'code' => (int) $code,
            'message' => (string) $message,
        ];
    }
}
