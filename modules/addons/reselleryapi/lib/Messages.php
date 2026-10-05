<?php
/**
 * Shared API message catalog for WHMCS ResellBridge (main WHMCS) — English + Farsi.
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

namespace ReselleryApi;

class Messages
{
    /** @var string */
    protected static $lang = 'english';

    /**
     * @param string $lang english|farsi|en|fa|persian
     * @return void
     */
    public static function setLanguage($lang)
    {
        $lang = strtolower(trim((string) $lang));
        if (in_array($lang, ['fa', 'farsi', 'persian', 'فارسی'], true)) {
            self::$lang = 'farsi';
            return;
        }
        self::$lang = 'english';
    }

    /**
     * @return string
     */
    public static function language()
    {
        return self::$lang;
    }

    /**
     * @param string $key
     * @param array $replace
     * @return string
     */
    public static function get($key, array $replace = [])
    {
        $catalog = self::catalog();
        $text = isset($catalog[self::$lang][$key])
            ? $catalog[self::$lang][$key]
            : (isset($catalog['english'][$key]) ? $catalog['english'][$key] : $key);

        foreach ($replace as $search => $value) {
            $text = str_replace(':' . $search, (string) $value, $text);
        }

        return $text;
    }

    /**
     * @return array
     */
    protected static function catalog()
    {
        return [
            'english' => [
                'addon_not_configured' => 'WHMCS ResellBridge addon is not configured or not activated.',
                'missing_credentials' => 'Missing authentication credentials.',
                'account_disabled' => 'Reseller account is disabled.',
                'invalid_password' => 'Invalid password.',
                'invalid_access_hash' => 'Invalid access hash.',
                'ip_not_allowed' => 'Client IP is not allowed.',
                'invalid_username' => 'Invalid username or reseller account not found.',
                'unknown_action' => 'Unknown or missing action.',
                'invalid_pid' => 'Missing or invalid product ID (pid).',
                'product_not_found' => 'Product not found.',
                'group_not_allowed' => 'This product group is not allowed for reselling.',
                'product_group_not_allowed' => 'This product belongs to a group that is not allowed for reselling.',
                'remote_client_missing' => 'Configured remote client ID was not found.',
                'addorder_failed' => 'AddOrder failed.',
                'acceptorder_failed' => 'AcceptOrder failed.',
                'service_row_missing' => 'Order created but service row was not found.',
                'invalid_service' => 'Invalid service ID.',
                'unauthenticated' => 'Unauthenticated.',
                'not_owned' => 'This service is not owned by the authenticated reseller.',
                'service_not_found' => 'Service not found.',
                'suspend_failed' => 'Suspend failed.',
                'unsuspend_failed' => 'Unsuspend failed.',
                'terminate_failed' => 'Terminate failed.',
                'suspend_reason' => 'Suspended by reseller',
                'invalid_client_data' => 'Invalid clientData payload.',
                'invalid_email' => 'Client email is missing or invalid.',
                'addclient_failed' => 'AddClient failed.',
                'ping_ok' => 'WHMCS ResellBridge API is reachable.',
                'label_service_id' => 'Service ID',
                'label_product' => 'Product',
                'label_domain' => 'Domain',
                'label_status' => 'Status',
                'label_username' => 'Username',
                'label_billing' => 'Billing Cycle',
                'label_nextdue' => 'Next Due Date',
                'label_dedicated_ip' => 'Dedicated IP',
                'label_assigned_ips' => 'Assigned IPs',
                'label_regdate' => 'Reg Date',
                'cp_title' => 'Service Control Panel',
                'cp_order' => 'Remote Order ID',
                'cp_product' => 'Product',
                'cp_status' => 'Status',
                'cp_domain' => 'Domain',
                'cp_note' => 'Manage this service from your account dashboard on the provider system.',
                'ungrouped' => 'Ungrouped',
            ],
            'farsi' => [
                'addon_not_configured' => 'افزونه WHMCS ResellBridge پیکربندی یا فعال نشده است.',
                'missing_credentials' => 'اطلاعات احراز هویت ناقص است.',
                'account_disabled' => 'حساب نمایندگی غیرفعال است.',
                'invalid_password' => 'رمز عبور نامعتبر است.',
                'invalid_access_hash' => 'هش دسترسی نامعتبر است.',
                'ip_not_allowed' => 'IP کلاینت مجاز نیست.',
                'invalid_username' => 'نام کاربری نامعتبر است یا حساب نمایندگی یافت نشد.',
                'unknown_action' => 'عملیات ناشناخته یا نامشخص است.',
                'invalid_pid' => 'شناسه محصول (pid) نامعتبر یا موجود نیست.',
                'product_not_found' => 'محصول یافت نشد.',
                'group_not_allowed' => 'این گروه محصول برای نمایندگی مجاز نیست.',
                'product_group_not_allowed' => 'این محصول در گروهی است که برای نمایندگی مجاز نیست.',
                'remote_client_missing' => 'شناسه مشتری راه دور تنظیم‌شده یافت نشد.',
                'addorder_failed' => 'ایجاد سفارش ناموفق بود.',
                'acceptorder_failed' => 'پذیرش سفارش ناموفق بود.',
                'service_row_missing' => 'سفارش ساخته شد اما ردیف سرویس یافت نشد.',
                'invalid_service' => 'شناسه سرویس نامعتبر است.',
                'unauthenticated' => 'احراز هویت نشده است.',
                'not_owned' => 'این سرویس متعلق به نماینده احراز هویت‌شده نیست.',
                'service_not_found' => 'سرویس یافت نشد.',
                'suspend_failed' => 'تعلیق ناموفق بود.',
                'unsuspend_failed' => 'رفع تعلیق ناموفق بود.',
                'terminate_failed' => 'حذف سرویس ناموفق بود.',
                'suspend_reason' => 'تعلیق توسط نماینده',
                'invalid_client_data' => 'داده clientData نامعتبر است.',
                'invalid_email' => 'ایمیل مشتری موجود نیست یا نامعتبر است.',
                'addclient_failed' => 'ایجاد مشتری ناموفق بود.',
                'ping_ok' => 'API مربوط به WHMCS ResellBridge در دسترس است.',
                'label_service_id' => 'شناسه سرویس',
                'label_product' => 'محصول',
                'label_domain' => 'دامنه',
                'label_status' => 'وضعیت',
                'label_username' => 'نام کاربری',
                'label_billing' => 'دوره صورتحساب',
                'label_nextdue' => 'سررسید بعدی',
                'label_dedicated_ip' => 'IP اختصاصی',
                'label_assigned_ips' => 'IPهای اختصاص‌یافته',
                'label_regdate' => 'تاریخ ثبت',
                'cp_title' => 'پنل کنترل سرویس',
                'cp_order' => 'شناسه سفارش راه دور',
                'cp_product' => 'محصول',
                'cp_status' => 'وضعیت',
                'cp_domain' => 'دامنه',
                'cp_note' => 'این سرویس را از داشبورد حساب در سامانه ارائه‌دهنده مدیریت کنید.',
                'ungrouped' => 'بدون گروه',
            ],
        ];
    }
}
