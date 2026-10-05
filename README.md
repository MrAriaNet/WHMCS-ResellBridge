# WHMCS ResellBridge

**WHMCS ResellBridge** connects one or more reseller WHMCS installations to a single upstream WHMCS. Resellers sell locally; create, suspend, unsuspend, and terminate run on the main system through a dedicated API, with per-reseller credentials, product-group access, and English/Farsi UI.

This repository contains both sides of the integration:

- A **server module** (`resellery`) for each reseller WHMCS
- An **addon + API endpoint** (`reselleryapi`) on the main WHMCS that supports **multiple reseller accounts**

---

## Table of contents

- [Features](#features)
- [Languages](#languages)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Repository structure](#repository-structure)
- [Installation — Main WHMCS](#installation--main-whmcs)
- [Installation — Reseller WHMCS](#installation--reseller-whmcs)
- [Product configuration](#product-configuration)
- [How remote clients are created](#how-remote-clients-are-created)
- [Smoke test checklist](#smoke-test-checklist)
- [API reference](#api-reference)
- [Database](#database)
- [Security recommendations](#security-recommendations)
- [Troubleshooting](#troubleshooting)
- [Limitations](#limitations)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

---

## Features

### Reseller module (`resellery` — display name: WHMCS ResellBridge)

- Provision services on the main WHMCS when a local service is created
- Suspend / unsuspend / terminate mirrored to the remote service
- Admin service tab with remote overview
- Client area overview and control-panel HTML from the main system
- **Test Connection** against the main API
- Load allowed remote product groups and products into module settings (not hardcoded)
- Sync remote product custom fields onto the local product
- Optional fixed remote client ID for all orders
- English and Farsi (Persian) UI

### Main API (`reselleryapi` + `api_resellery.php` — display name: WHMCS ResellBridge)

- Credential-based authentication (username, password, access hash)
- Optional IP allowlist
- Selectable allowed product groups for resellers
- **Multiple reseller WHMCS accounts** (separate credentials, groups, IPs, and service ownership)
- List products (filtered by allowed groups) and custom fields
- Create client (or reuse existing), place order, accept order / autosetup
- Suspend, unsuspend, terminate via WHMCS `localAPI`
- Service overview and control-panel HTML for the reseller UI
- JSON responses only (no fatal `die()` on business errors)
- English and Farsi (Persian) API / admin messages

---

## Languages

Both sides support **English** and **Farsi (Persian)**.

| Component | Language files | How language is chosen |
|-----------|----------------|------------------------|
| Main addon | `modules/addons/reselleryapi/lang/english.php`, `farsi.php` | WHMCS admin language (`adminlang` / `farsi`) |
| Main API messages | `modules/addons/reselleryapi/lib/Messages.php` | `language` POST field from the reseller (`english` or `farsi`) |
| Reseller module | `modules/servers/resellery/lang/english.php`, `farsi.php` | Admin/client WHMCS language |

Set the WHMCS admin language to **Farsi** to see the Persian UI. The reseller module also sends its language to the main API so error messages and overview labels match.

---

## Architecture

```text
┌─────────────────────────┐
│  Reseller WHMCS A       │──┐
│  (own API credentials)  │  │
└─────────────────────────┘  │     HTTPS POST
                             ├──► /includes/api_resellery.php ──► Main WHMCS
┌─────────────────────────┐  │         JSON result              Products / Services
│  Reseller WHMCS B       │──┘                                  Per-reseller ownership
│  (own API credentials)  │
└─────────────────────────┘
```

Each reseller WHMCS uses the same server module, but authenticates with its **own** username, password, and access hash created on the main addon. Product-group visibility and service ownership are enforced per reseller.

---

## Requirements

| Component | Requirement |
|-----------|-------------|
| WHMCS | 7.x or 8.x recommended (Capsule + `localAPI`) |
| PHP | Version supported by your WHMCS (cURL enabled) |
| Network | Reseller must reach main WHMCS over HTTP(S) |
| SSL | HTTPS strongly recommended in production |
| Permissions | Main WHMCS must allow the API file to bootstrap and call `localAPI` |

---

## Repository structure

```text
whmcs-resellery/
├── README.md
├── includes/
│   └── api_resellery.php
├── modules/
│   ├── addons/
│   │   └── reselleryapi/
│   │       ├── reselleryapi.php
│   │       ├── lang/
│   │       │   ├── english.php
│   │       │   └── farsi.php
│   │       └── lib/
│   │           ├── Messages.php
│   │           ├── Auth.php
│   │           ├── ApiHandler.php
│   │           ├── Provisioning.php
│   │           └── ResellerAccounts.php
│   └── servers/
│       └── resellery/
│           ├── resellery.php
│           ├── resellery_ajax.php
│           ├── resellery.js
│           ├── lang.php
│           ├── clientarea.tpl
│           └── lang/
│               ├── english.php
│               └── farsi.php
```

---

## Installation — Main WHMCS

Perform these steps on the **upstream / provider** WHMCS (where real products and services live).

### 1. Copy files

```text
modules/addons/reselleryapi/   →  {main-whmcs}/modules/addons/reselleryapi/
includes/api_resellery.php     →  {main-whmcs}/includes/api_resellery.php
```

### 2. Activate the addon

1. Log in to WHMCS admin.
2. Go to **System Settings → Addon Modules** (or **Setup → Addon Modules** on older versions).
3. Find **WHMCS ResellBridge** and click **Activate**.

### 3. Configure defaults and reseller accounts

#### Optional global / default settings (Addon Modules → Configure)

| Setting | Description |
|---------|-------------|
| **Global API Username / Password / Access Hash** | Optional shared credential (not required if you use per-reseller accounts) |
| **Default Allowed IPs** | Fallback IP allowlist when a reseller account has none |
| **Default Allowed Product Groups** | Fallback groups when a reseller account has none |

#### Per-reseller accounts (Addons → WHMCS ResellBridge)

1. Open **Addons → WHMCS ResellBridge**.
2. Under **Add Reseller Account**, create one account for each reseller WHMCS:
   - Display name
   - Unique API username
   - API password
   - Access hash (auto-generated; you can replace it)
   - Optional IP allowlist
   - Optional product groups for that reseller only
3. Save. Repeat for every reseller WHMCS that should connect.
4. Optionally set **Default Product Groups** used when an account has no groups selected.

Each reseller can only list/provision products from its allowed groups (or the default). Each reseller can only manage services it created (ownership is stored on the main WHMCS).

### 4. Prepare products

- Create the products you want resellers to sell.
- Configure pricing and billing cycles.
- Add product custom fields if the reseller needs to collect them at order time.

### 5. Verify the API

Use credentials from **one reseller account** (or the optional global credential):

```bash
# Linux / macOS / Git Bash
curl -X POST "https://main.example.com/includes/api_resellery.php" \
  -d "action=ping" \
  -d "username=YOUR_USER" \
  -d "password=$(printf '%s' 'YOUR_PASS' | md5sum | awk '{print $1}')" \
  -d "accesshash=YOUR_HASH"
```

PowerShell example:

```powershell
$pass = "YOUR_PASS"
$md5 = [BitConverter]::ToString(
  [System.Security.Cryptography.MD5]::Create().ComputeHash(
    [Text.Encoding]::UTF8.GetBytes($pass)
  )
).Replace("-", "").ToLower()

Invoke-RestMethod -Method Post -Uri "https://main.example.com/includes/api_resellery.php" -Body @{
  action     = "ping"
  username   = "YOUR_USER"
  password   = $md5
  accesshash = "YOUR_HASH"
}
```

Successful response example:

```json
{
  "result": "success",
  "message": "WHMCS ResellBridge API is reachable.",
  "version": "1.0.0"
}
```

---

## Installation — Reseller WHMCS

Perform these steps on the **reseller** WHMCS (where customers place orders).

### 1. Copy the server module

```text
modules/servers/resellery/  →  {reseller-whmcs}/modules/servers/resellery/
```

### 2. Create a server record (per reseller WHMCS)

1. Go to **System Settings → Products/Services → Servers**.
2. Click **Add New Server**.
3. Fill in:

| Field | Value |
|-------|--------|
| **Name** | Any label (e.g. `Main WHMCS`) |
| **Hostname** | Main host, e.g. `main.example.com`, or full base URL `https://main.example.com` |
| **IP Address** | Optional fallback if hostname is empty |
| **Username** | The **reseller account** API username from the main addon |
| **Password** | That reseller account’s API password |
| **Access Hash** | That reseller account’s access hash |

Do **not** reuse another reseller’s credentials. Each reseller WHMCS should use its own account so product visibility and service ownership stay isolated.

Also:

- Do **not** put `/includes/api_resellery.php` in the hostname.
- The module always appends `/includes/api_resellery.php`.
- Prefer hostname over IP when TLS certificates are involved.
- Save the server, then use **Test Connection** where available.

### 3. Create a server group

1. Create a **Server Group**.
2. Add the server from the previous step to that group.
3. You will assign this group on each WHMCS ResellBridge product.

---

## Product configuration

1. Create or edit a product on the reseller WHMCS.
2. Open **Module Settings**.
3. Select module: **WHMCS ResellBridge** (folder name: `resellery`).
4. Select the **Server Group** that points to the main WHMCS.
5. **Save** the product once so it has a local product ID (required before syncing custom fields).
6. Configure module options:

| Option | Purpose |
|--------|---------|
| **Remote Product Group** | Loaded automatically from allowed groups on the main WHMCS. Choose one group or **All allowed groups** |
| **Service Type** | `Service` or `Volume` — local UI only |
| **Remote Product** | Upstream product ID. Loaded automatically (and via **Load Products**) as `Group — Product` |
| **Sync Custom Fields** | Use **Fetch Fields** to copy upstream product custom fields onto this local product |
| **Start Department** | Local UI only (optional ticket-related customization) |
| **Start Department Status** | Local UI only |
| **Remote Client ID** | If set, all services are created under this client on the main WHMCS. If empty, clients are matched/created by email |

Product groups and products are **not** hardcoded. They come from the main WHMCS based on the groups you allow in the WHMCS ResellBridge addon.

---

## How remote clients are created

When **Remote Client ID** is empty:

1. The reseller sends the local client row as base64-encoded JSON (`clientData`).
2. The main API looks up a client by **email**.
3. If found, that client is reused.
4. If not found, a new client is created via `AddClient` using the reseller client details.

When **Remote Client ID** is set:

- Every provisioned service is attached to that existing client ID on the main WHMCS.
- The ID must already exist; otherwise create fails with an error.

---

## Smoke test checklist

Use a non-production product first.

- [ ] Main addon is active and credentials are set
- [ ] `ping` returns `"result":"success"`
- [ ] Reseller server **Test Connection** succeeds
- [ ] At least one reseller account created on the main addon
- [ ] Allowed product groups selected for that account (or defaults)
- [ ] Reseller module auto-loads remote groups/products; **Load Products** lists only allowed groups
- [ ] Place a paid test order on the reseller (or run **Create** on the service)
- [ ] A matching service appears on the main WHMCS
- [ ] Reseller DB table `tblsamfonyresellery` contains local/remote order and service IDs
- [ ] **Suspend** on reseller → remote service Suspended
- [ ] **Unsuspend** → remote Active
- [ ] **Terminate** → remote Terminated
- [ ] Client area product page shows overview / control panel HTML without errors

---

## API reference

**Endpoint (main WHMCS):**

```text
POST https://{main-host}/includes/api_resellery.php
```

**Content type:** `application/x-www-form-urlencoded`

### Authentication (required on every request)

| Field | Description |
|-------|-------------|
| `username` | API username from the addon |
| `password` | MD5 hash of the API password |
| `accesshash` | Access hash from the addon |
| `responsetype` | Optional; use `json` |

### Actions

| Action | Description | Important parameters | Success payload (typical) |
|--------|-------------|----------------------|---------------------------|
| `ping` / `reselleryping` | Health check | — | `result`, `message`, `version` |
| `getreselleryproductgroups` | List allowed product groups | — | `data`: `[{id,name}, ...]` |
| `getresellerypackages` | List products in allowed groups | `gid` (optional) | `data`: `[{id,name,gid,groupname,label}, ...]` |
| `getcustomfields` | Product custom fields | `reselleryPid` | `data`: field definitions |
| `reselleryadd` | Create order/service | `pid`, `clientData`, `billingcycle`, `customfields`, `samfony_userid`, domain/user/pass/ns | `orderid`, `serviceid`, `dedicatedip`, `assignedips` |
| `resellerysuspendservice` | Suspend | `serviceid`, `reason` | `result=success` |
| `reselleryunsuspendservice` | Unsuspend | `serviceid` | `result=success` |
| `reselleryterminateservice` | Terminate | `serviceid`, `reason` | `result=success` |
| `resellerygetoverview` | HTML overview | `serviceid` | `data` (HTML) |
| `resellerycontrolpanel` | Client panel HTML | `serviceid`, `orderid` | `data` (HTML) |

### Error format

```json
{
  "result": "error",
  "code": 401,
  "message": "Invalid username."
}
```

Common codes: `400` bad request, `401` auth failure, `404` not found, `500` / `502` server or connectivity issues.

### Example: list packages

```bash
curl -X POST "https://main.example.com/includes/api_resellery.php" \
  -d "action=getresellerypackages" \
  -d "username=YOUR_USER" \
  -d "password=MD5_OF_PASSWORD" \
  -d "accesshash=YOUR_HASH" \
  -d "responsetype=json"
```

---

## Database

### Reseller WHMCS

Table **`tblsamfonyresellery`** (created automatically when module config is opened):

| Column | Description |
|--------|-------------|
| `id` | Primary key |
| `local_orderid` | Order ID on the reseller |
| `local_serviceid` | Service ID on the reseller |
| `remote_orderid` | Order ID on the main WHMCS |
| `remote_serviceid` | Service ID on the main WHMCS |
| `date` | Mapping creation timestamp |

### Main WHMCS

| Table | Purpose |
|-------|---------|
| `mod_reselleryapi_resellers` | Per-reseller API accounts (credentials, IPs, product groups) |
| `mod_reselleryapi_services` | Maps each provisioned service to the reseller that created it |
| `tbladdonmodules` | Addon defaults (global credential, default groups/IPs) |

---

## Security recommendations

1. Use **HTTPS** between reseller and main WHMCS.
2. Set a strong unique **Access Hash** and API password for **each** reseller account (not your WHMCS admin password).
3. Prefer per-reseller **Allowed IPs** (or the default allowlist) limited to each reseller’s public address(es).
4. Keep `includes/api_resellery.php` only on the main WHMCS; do not expose it elsewhere.
5. Disable or delete a reseller account immediately if that reseller’s server is compromised.
6. Prefer least-privilege operational practices on both WHMCS installations (backups, staging tests first).

---

## Troubleshooting

| Symptom | Likely cause | What to check |
|---------|--------------|---------------|
| Test Connection fails | Wrong host, SSL, or credentials | Hostname without `/includes/...`; matching username/password/hash; firewall; valid TLS cert |
| `Invalid username/password/access hash` | Credential mismatch | Addon settings vs server record; password must be the plaintext configured value (module sends MD5) |
| `Client IP is not allowed` | IP allowlist | Add reseller egress IP to **Allowed IPs**, or clear the field |
| Load Products empty / error | API or server group | Server in selected group; products exist on main; `ping` works |
| Create fails after order | Upstream product/billing/client issue | Main WHMCS activity log; product pricing for the billing cycle; remote client ID validity |
| Custom field sync fails | Product not saved | Save local product first so `id` exists in the URL |
| Overview blank in client area | No mapping row | Confirm `tblsamfonyresellery` has the local service ID |

Enable WHMCS module debug / check **Utilities → Logs → Module Log** and **Activity Log** on both sides while testing.

---

## Limitations

- No Change Password, Change Package, or Renew module hooks in this release.
- **Service Type** and department options are local UI fields only and are not sent to the main API.
- Control panel output is a safe HTML summary from the main API (not an interactive SSO iframe into the main client area).
- This package does not use the stock WHMCS `/includes/api.php` Identifier/Secret API; it uses the dedicated WHMCS ResellBridge endpoint (`/includes/api_resellery.php`).

---

## Contributing

Issues and pull requests are welcome via [github.com/MrAriaNet/WHMCS-ResellBridge](https://github.com/MrAriaNet/WHMCS-ResellBridge).

1. Fork the repository.
2. Create a feature branch.
3. Test against a staging reseller + main WHMCS pair.
4. Open a pull request with a clear description of the change and test steps.

Please do not commit production credentials, access hashes, or customer data.

---

## License

This project is released under [The Unlicense](https://unlicense.org/).

Anyone is free to copy, modify, publish, use, compile, sell, or distribute this software, either in source code form or as a compiled binary, for any purpose, commercial or non-commercial, and by any means.

See the [LICENSE](LICENSE) file for the full dedication to the public domain.

---

## Support

- Open a GitHub issue on [github.com/MrAriaNet/WHMCS-ResellBridge](https://github.com/MrAriaNet/WHMCS-ResellBridge) for bugs and feature requests.
- Include WHMCS versions (reseller + main), PHP version, and relevant Module/Activity log excerpts (redact secrets).
