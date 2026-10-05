<?php
/**
 * Persian (Farsi) language strings — WHMCS ResellBridge addon (main WHMCS).
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

$_ADDONLANG['addon_name'] = 'WHMCS ResellBridge';
$_ADDONLANG['addon_description'] = 'چند نصب WHMCS نمایندگی را به این WHMCS اصلی وصل می‌کند. نمایندگان به‌صورت محلی می‌فروشند؛ ایجاد، تعلیق، رفع تعلیق و حذف از طریق API اختصاصی روی سیستم اصلی اجرا می‌شود، با اعتبارنامه جدا، دسترسی به گروه محصول و رابط انگلیسی/فارسی.';

$_ADDONLANG['cfg_global_username'] = 'نام کاربری سراسری API (اختیاری)';
$_ADDONLANG['cfg_global_username_desc'] = 'اعتبارنامه مشترک اختیاری. بهتر است برای هر نماینده حساب جداگانه در صفحه افزونه بسازید.';
$_ADDONLANG['cfg_global_password'] = 'رمز عبور سراسری API (اختیاری)';
$_ADDONLANG['cfg_global_password_desc'] = 'رمز عبور مشترک اختیاری برای اعتبارنامه سراسری.';
$_ADDONLANG['cfg_global_hash'] = 'هش دسترسی سراسری (اختیاری)';
$_ADDONLANG['cfg_global_hash_desc'] = 'هش دسترسی مشترک اختیاری برای اعتبارنامه سراسری.';
$_ADDONLANG['cfg_default_ips'] = 'IPهای مجاز پیش‌فرض';
$_ADDONLANG['cfg_default_ips_desc'] = 'لیست IP پیش‌فرض وقتی حساب نماینده IP جدا ندارد. خالی = همه IPها مجاز.';
$_ADDONLANG['cfg_default_groups'] = 'گروه‌های محصول مجاز پیش‌فرض';
$_ADDONLANG['cfg_default_groups_desc'] = 'شناسه گروه‌های پیش‌فرض وقتی حساب نماینده گروه جدا ندارد. خالی = همه گروه‌ها. مدیریت از صفحه افزونه.';

$_ADDONLANG['activate_ok'] = 'WHMCS ResellBridge فعال شد. حساب‌های نمایندگی را در صفحه افزونه بسازید و سپس includes/api_resellery.php را نصب کنید.';
$_ADDONLANG['activate_fail'] = 'فعال‌سازی هنگام ساخت جداول ناموفق بود: ';
$_ADDONLANG['deactivate_ok'] = 'WHMCS ResellBridge غیرفعال شد. جداول حساب نمایندگی حفظ شدند.';

$_ADDONLANG['intro'] = 'با WHMCS ResellBridge چند نصب WHMCS نمایندگی را به این WHMCS اصلی وصل کنید. هر نماینده نام کاربری، رمز، هش دسترسی، IP مجاز و گروه‌های محصول خودش را دارد.';
$_ADDONLANG['endpoint'] = 'آدرس ورود HTTP:';
$_ADDONLANG['schema_error'] = 'امکان آماده‌سازی جداول نمایندگی نیست: ';

$_ADDONLANG['defaults_title'] = 'گروه‌های محصول پیش‌فرض';
$_ADDONLANG['defaults_help'] = 'وقتی حساب نماینده گروه جدا ندارد استفاده می‌شود. اگر اینجا هم چیزی انتخاب نشود، همه گروه‌ها در دسترس‌اند.';
$_ADDONLANG['save_defaults'] = 'ذخیره گروه‌های پیش‌فرض';
$_ADDONLANG['defaults_saved'] = 'گروه‌های محصول مجاز پیش‌فرض ذخیره شد.';

$_ADDONLANG['accounts_title'] = 'حساب‌های نمایندگی';
$_ADDONLANG['col_id'] = 'شناسه';
$_ADDONLANG['col_name'] = 'نام';
$_ADDONLANG['col_username'] = 'نام کاربری';
$_ADDONLANG['col_enabled'] = 'فعال';
$_ADDONLANG['col_groups'] = 'گروه‌های محصول';
$_ADDONLANG['col_ips'] = 'IPهای مجاز';
$_ADDONLANG['col_actions'] = 'عملیات';
$_ADDONLANG['no_accounts'] = 'هنوز حساب نمایندگی‌ای نیست. در پایین یکی بسازید.';
$_ADDONLANG['inherit_default'] = 'پیش‌فرض / همه';
$_ADDONLANG['inherit_ips'] = 'پیش‌فرض / همه';
$_ADDONLANG['yes'] = 'بله';
$_ADDONLANG['no'] = 'خیر';
$_ADDONLANG['edit'] = 'ویرایش';
$_ADDONLANG['delete'] = 'حذف';
$_ADDONLANG['delete_confirm'] = 'این حساب نمایندگی حذف شود؟ لینک مالکیت سرویس‌ها برای پیگیری حفظ می‌شود.';
$_ADDONLANG['deleted'] = 'حساب نمایندگی حذف شد.';
$_ADDONLANG['hidden_store'] = '(مخفی در فروشگاه)';
$_ADDONLANG['group'] = 'گروه';
$_ADDONLANG['allow'] = 'مجاز';
$_ADDONLANG['no_groups'] = 'گروه محصولی یافت نشد.';

$_ADDONLANG['add_title'] = 'افزودن حساب نمایندگی';
$_ADDONLANG['add_help'] = 'برای هر WHMCS نمایندگی یک حساب بسازید و همان نام کاربری، رمز و هش را در بخش Servers آن نمایندگی وارد کنید.';
$_ADDONLANG['edit_title'] = 'ویرایش نماینده #';
$_ADDONLANG['cancel_edit'] = 'انصراف از ویرایش / ساخت جدید';

$_ADDONLANG['field_name'] = 'نام نمایشی';
$_ADDONLANG['field_username'] = 'نام کاربری API';
$_ADDONLANG['field_password'] = 'رمز عبور API';
$_ADDONLANG['field_password_keep'] = 'برای حفظ رمز فعلی خالی بگذارید';
$_ADDONLANG['field_ips'] = 'IPهای مجاز';
$_ADDONLANG['field_ips_help'] = 'اختیاری. با کاما یا خط جدید جدا کنید. خالی = پیش‌فرض / همه.';
$_ADDONLANG['field_notes'] = 'یادداشت';
$_ADDONLANG['field_enabled'] = 'وضعیت';
$_ADDONLANG['field_active'] = 'فعال';
$_ADDONLANG['groups_for_reseller'] = 'گروه‌های محصول این نماینده';
$_ADDONLANG['groups_for_reseller_help'] = 'اگر هیچ‌کدام انتخاب نشود، گروه‌های پیش‌فرض (یا همه گروه‌ها) اعمال می‌شود.';
$_ADDONLANG['btn_create'] = 'ایجاد نماینده';
$_ADDONLANG['btn_update'] = 'به‌روزرسانی نماینده';

$_ADDONLANG['err_required'] = 'نام، نام کاربری، رمز عبور و هش دسترسی الزامی است.';
$_ADDONLANG['err_required_update'] = 'نام، نام کاربری و هش دسترسی الزامی است.';
$_ADDONLANG['err_username_taken'] = 'این نام کاربری قبلاً استفاده شده است.';
$_ADDONLANG['err_not_found'] = 'حساب نمایندگی یافت نشد.';
$_ADDONLANG['err_create'] = 'ایجاد نماینده ممکن نشد: ';
$_ADDONLANG['err_update'] = 'به‌روزرسانی نماینده ممکن نشد: ';
$_ADDONLANG['ok_created'] = 'حساب نمایندگی ساخته شد. اعتبارنامه سرور را در WHMCS نمایندگی تنظیم کنید.';
$_ADDONLANG['ok_updated'] = 'حساب نمایندگی به‌روزرسانی شد.';
$_ADDONLANG['success'] = 'موفق!';
