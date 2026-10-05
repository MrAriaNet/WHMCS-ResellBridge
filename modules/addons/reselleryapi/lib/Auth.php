<?php
/**
 * API authentication for WHMCS ResellBridge (multi-reseller aware).
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

namespace ReselleryApi;

class Auth
{
    /** @var array|null */
    protected static $currentReseller = null;

    /**
     * @return array{ok:bool,message?:string,reseller?:array,settings?:array}
     */
    public static function validate(array $request)
    {
        self::$currentReseller = null;

        if (isset($request['language'])) {
            Messages::setLanguage($request['language']);
        }

        $settings = self::loadSettings();
        if ($settings === null) {
            return ['ok' => false, 'message' => Messages::get('addon_not_configured')];
        }

        $username = isset($request['username']) ? (string) $request['username'] : '';
        $password = isset($request['password']) ? (string) $request['password'] : '';
        $accessHash = isset($request['accesshash']) ? (string) $request['accesshash'] : '';

        if ($username === '' || $password === '' || $accessHash === '') {
            return ['ok' => false, 'message' => Messages::get('missing_credentials')];
        }

        ResellerAccounts::ensureSchema();

        $account = ResellerAccounts::findByUsername($username);
        if ($account) {
            if (!(int) $account->enabled) {
                return ['ok' => false, 'message' => Messages::get('account_disabled')];
            }

            if (!self::passwordMatches((string) $account->password, $password)) {
                return ['ok' => false, 'message' => Messages::get('invalid_password')];
            }

            if (!hash_equals((string) $account->access_hash, $accessHash)) {
                return ['ok' => false, 'message' => Messages::get('invalid_access_hash')];
            }

            $ips = trim((string) $account->allowed_ips);
            if ($ips === '') {
                $ips = (string) ($settings['allowed_ips'] ?? '');
            }
            if (!self::ipAllowed($ips)) {
                return ['ok' => false, 'message' => Messages::get('ip_not_allowed')];
            }

            $reseller = [
                'id' => (int) $account->id,
                'name' => (string) $account->name,
                'username' => (string) $account->username,
                'allowed_product_groups' => (string) $account->allowed_product_groups,
                'allowed_ips' => (string) $account->allowed_ips,
                'is_global' => false,
            ];
            self::$currentReseller = $reseller;

            return ['ok' => true, 'reseller' => $reseller, 'settings' => $settings];
        }

        $cfgUser = (string) ($settings['api_username'] ?? '');
        $cfgPass = (string) ($settings['api_password'] ?? '');
        $cfgHash = (string) ($settings['access_hash'] ?? '');

        if ($cfgUser === '' || $cfgPass === '' || $cfgHash === '') {
            return ['ok' => false, 'message' => Messages::get('invalid_username')];
        }

        if (!hash_equals($cfgUser, $username)) {
            return ['ok' => false, 'message' => Messages::get('invalid_username')];
        }

        if (!self::passwordMatches($cfgPass, $password)) {
            return ['ok' => false, 'message' => Messages::get('invalid_password')];
        }

        if (!hash_equals($cfgHash, $accessHash)) {
            return ['ok' => false, 'message' => Messages::get('invalid_access_hash')];
        }

        if (!self::ipAllowed($settings['allowed_ips'] ?? '')) {
            return ['ok' => false, 'message' => Messages::get('ip_not_allowed')];
        }

        $reseller = [
            'id' => 0,
            'name' => 'Global',
            'username' => $cfgUser,
            'allowed_product_groups' => (string) ($settings['allowed_product_groups'] ?? ''),
            'allowed_ips' => (string) ($settings['allowed_ips'] ?? ''),
            'is_global' => true,
        ];
        self::$currentReseller = $reseller;

        return ['ok' => true, 'reseller' => $reseller, 'settings' => $settings];
    }

    /**
     * @return array|null
     */
    public static function currentReseller()
    {
        return self::$currentReseller;
    }

    /**
     * @param string $storedPassword
     * @param string $providedPassword
     * @return bool
     */
    public static function passwordMatches($storedPassword, $providedPassword)
    {
        $storedPassword = (string) $storedPassword;
        $providedPassword = (string) $providedPassword;
        $md5Stored = md5($storedPassword);

        if (hash_equals($md5Stored, $providedPassword)) {
            return true;
        }
        if (strlen($providedPassword) === 32 && ctype_xdigit($providedPassword)
            && hash_equals($md5Stored, strtolower($providedPassword))
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return array|null
     */
    public static function loadSettings()
    {
        try {
            $rows = \WHMCS\Database\Capsule::table('tbladdonmodules')
                ->where('module', 'reselleryapi')
                ->get(['setting', 'value']);
        } catch (\Exception $e) {
            return null;
        }

        if ($rows->isEmpty()) {
            return null;
        }

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->setting] = $row->value;
        }

        return $settings;
    }

    /**
     * @param string $allowedIpsCsv
     * @return bool
     */
    public static function ipAllowed($allowedIpsCsv)
    {
        $allowedIpsCsv = trim((string) $allowedIpsCsv);
        if ($allowedIpsCsv === '') {
            return true;
        }

        $clientIp = self::clientIp();
        $parts = preg_split('/[\s,;]+/', $allowedIpsCsv, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return true;
        }

        foreach ($parts as $ip) {
            if (hash_equals(trim($ip), $clientIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Use REMOTE_ADDR only (do not trust X-Forwarded-For by default).
     *
     * @return string
     */
    public static function clientIp()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    }
}
