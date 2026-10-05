<?php
/**
 * English language strings — WHMCS ResellBridge addon (main WHMCS).
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

$_ADDONLANG['addon_name'] = 'WHMCS ResellBridge';
$_ADDONLANG['addon_description'] = 'Connects multiple reseller WHMCS installations to this upstream WHMCS. Resellers sell locally; create, suspend, unsuspend, and terminate run here through a dedicated API, with per-reseller credentials, product-group access, and English/Farsi UI.';

$_ADDONLANG['cfg_global_username'] = 'Global API Username (optional)';
$_ADDONLANG['cfg_global_username_desc'] = 'Optional shared credential. Prefer creating per-reseller accounts on the addon page.';
$_ADDONLANG['cfg_global_password'] = 'Global API Password (optional)';
$_ADDONLANG['cfg_global_password_desc'] = 'Optional shared password for the global credential.';
$_ADDONLANG['cfg_global_hash'] = 'Global Access Hash (optional)';
$_ADDONLANG['cfg_global_hash_desc'] = 'Optional shared access hash for the global credential.';
$_ADDONLANG['cfg_default_ips'] = 'Default Allowed IPs';
$_ADDONLANG['cfg_default_ips_desc'] = 'Default IP allowlist used when a reseller account has no IPs of its own. Empty = allow any IP.';
$_ADDONLANG['cfg_default_groups'] = 'Default Allowed Product Groups';
$_ADDONLANG['cfg_default_groups_desc'] = 'Default group IDs when a reseller account has no groups set. Empty = all groups. Manage defaults on the addon page.';

$_ADDONLANG['activate_ok'] = 'WHMCS ResellBridge activated. Create reseller accounts on the addon page, then install includes/api_resellery.php.';
$_ADDONLANG['activate_fail'] = 'Activation failed while creating tables: ';
$_ADDONLANG['deactivate_ok'] = 'WHMCS ResellBridge deactivated. Reseller account tables were kept.';

$_ADDONLANG['intro'] = 'Connect multiple reseller WHMCS installations to this main WHMCS with WHMCS ResellBridge. Each reseller gets its own API username, password, access hash, optional IP allowlist, and product groups.';
$_ADDONLANG['endpoint'] = 'HTTP entrypoint:';
$_ADDONLANG['schema_error'] = 'Unable to initialize reseller tables: ';

$_ADDONLANG['defaults_title'] = 'Default Product Groups';
$_ADDONLANG['defaults_help'] = 'Used when a reseller account has no product groups of its own. If nothing is selected here either, all groups are available.';
$_ADDONLANG['save_defaults'] = 'Save Default Groups';
$_ADDONLANG['defaults_saved'] = 'Default allowed product groups saved.';

$_ADDONLANG['accounts_title'] = 'Reseller Accounts';
$_ADDONLANG['col_id'] = 'ID';
$_ADDONLANG['col_name'] = 'Name';
$_ADDONLANG['col_username'] = 'Username';
$_ADDONLANG['col_enabled'] = 'Enabled';
$_ADDONLANG['col_groups'] = 'Product Groups';
$_ADDONLANG['col_ips'] = 'Allowed IPs';
$_ADDONLANG['col_actions'] = 'Actions';
$_ADDONLANG['no_accounts'] = 'No reseller accounts yet. Create one below.';
$_ADDONLANG['inherit_default'] = 'Use default / all';
$_ADDONLANG['inherit_ips'] = 'Use default / any';
$_ADDONLANG['yes'] = 'Yes';
$_ADDONLANG['no'] = 'No';
$_ADDONLANG['edit'] = 'Edit';
$_ADDONLANG['delete'] = 'Delete';
$_ADDONLANG['delete_confirm'] = 'Disable and remove this reseller account? Ownership links will be kept for audit.';
$_ADDONLANG['deleted'] = 'Reseller account deleted.';
$_ADDONLANG['hidden_store'] = '(hidden in store)';
$_ADDONLANG['group'] = 'Group';
$_ADDONLANG['allow'] = 'Allow';
$_ADDONLANG['no_groups'] = 'No product groups found.';

$_ADDONLANG['add_title'] = 'Add Reseller Account';
$_ADDONLANG['add_help'] = 'Create one account per reseller WHMCS. Put the same username, password, and access hash into that reseller\'s Servers record.';
$_ADDONLANG['edit_title'] = 'Edit Reseller #';
$_ADDONLANG['cancel_edit'] = 'Cancel edit / create new';

$_ADDONLANG['field_name'] = 'Display Name';
$_ADDONLANG['field_username'] = 'API Username';
$_ADDONLANG['field_password'] = 'API Password';
$_ADDONLANG['field_password_keep'] = 'Leave blank to keep current password';
$_ADDONLANG['field_hash'] = 'Access Hash';
$_ADDONLANG['field_ips'] = 'Allowed IPs';
$_ADDONLANG['field_ips_help'] = 'Optional. Comma/newline separated. Empty = use default / any.';
$_ADDONLANG['field_notes'] = 'Notes';
$_ADDONLANG['field_enabled'] = 'Enabled';
$_ADDONLANG['field_active'] = 'Active';
$_ADDONLANG['groups_for_reseller'] = 'Product Groups for this Reseller';
$_ADDONLANG['groups_for_reseller_help'] = 'Leave all unchecked to inherit the default groups (or all groups if defaults are empty).';
$_ADDONLANG['btn_create'] = 'Create Reseller';
$_ADDONLANG['btn_update'] = 'Update Reseller';

$_ADDONLANG['err_required'] = 'Name, username, password, and access hash are required.';
$_ADDONLANG['err_required_update'] = 'Name, username, and access hash are required.';
$_ADDONLANG['err_username_taken'] = 'That username is already in use.';
$_ADDONLANG['err_not_found'] = 'Reseller account not found.';
$_ADDONLANG['err_create'] = 'Could not create reseller: ';
$_ADDONLANG['err_update'] = 'Could not update reseller: ';
$_ADDONLANG['ok_created'] = 'Reseller account created. Configure the matching Server credentials on that reseller WHMCS.';
$_ADDONLANG['ok_updated'] = 'Reseller account updated.';
$_ADDONLANG['success'] = 'Success!';
