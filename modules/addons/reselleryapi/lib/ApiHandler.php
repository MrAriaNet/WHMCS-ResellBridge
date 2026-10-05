<?php
/**
 * Request dispatcher for WHMCS ResellBridge API.
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

namespace ReselleryApi;

class ApiHandler
{
    /**
     * @param array $request Typically $_POST
     * @return array
     */
    public static function handle(array $request)
    {
        $auth = Auth::validate($request);
        if (!$auth['ok']) {
            return [
                'result' => 'error',
                'code' => 401,
                'message' => $auth['message'],
            ];
        }

        $reseller = isset($auth['reseller']) ? $auth['reseller'] : null;
        $action = isset($request['action']) ? strtolower(trim((string) $request['action'])) : '';

        switch ($action) {
            case 'ping':
            case 'reselleryping':
                return Provisioning::ping($reseller);

            case 'getreselleryproductgroups':
                return Provisioning::getProductGroups($reseller);

            case 'getresellerypackages':
                $gid = isset($request['gid']) ? (int) $request['gid'] : 0;
                return Provisioning::getPackages($gid, $reseller);

            case 'getcustomfields':
                $pid = isset($request['reselleryPid']) ? (int) $request['reselleryPid'] : 0;
                return Provisioning::getCustomFields($pid, $reseller);

            case 'reselleryadd':
                return Provisioning::addService($request, $reseller);

            case 'resellerysuspendservice':
                $serviceId = isset($request['serviceid']) ? (int) $request['serviceid'] : 0;
                $reason = isset($request['reason']) ? (string) $request['reason'] : '';
                return Provisioning::suspendService($serviceId, $reason, $reseller);

            case 'reselleryunsuspendservice':
                $serviceId = isset($request['serviceid']) ? (int) $request['serviceid'] : 0;
                return Provisioning::unsuspendService($serviceId, $reseller);

            case 'reselleryterminateservice':
                $serviceId = isset($request['serviceid']) ? (int) $request['serviceid'] : 0;
                $reason = isset($request['reason']) ? (string) $request['reason'] : '';
                return Provisioning::terminateService($serviceId, $reason, $reseller);

            case 'resellerygetoverview':
                $serviceId = isset($request['serviceid']) ? (int) $request['serviceid'] : 0;
                return Provisioning::getOverview($serviceId, $reseller);

            case 'resellerycontrolpanel':
                $serviceId = isset($request['serviceid']) ? (int) $request['serviceid'] : 0;
                $orderId = isset($request['orderid']) ? (int) $request['orderid'] : 0;
                return Provisioning::getControlPanel($serviceId, $orderId, $reseller);

            default:
                return [
                    'result' => 'error',
                    'code' => 400,
                    'message' => Messages::get('unknown_action'),
                ];
        }
    }
}
