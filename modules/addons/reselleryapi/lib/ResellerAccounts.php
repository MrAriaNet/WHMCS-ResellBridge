<?php
/**
 * Multi-reseller account storage for WHMCS ResellBridge.
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

namespace ReselleryApi;

use WHMCS\Database\Capsule;

class ResellerAccounts
{
    const TABLE_RESELLERS = 'mod_reselleryapi_resellers';
    const TABLE_SERVICES = 'mod_reselleryapi_services';

    /**
     * Ensure database tables exist.
     */
    public static function ensureSchema()
    {
        if (!Capsule::schema()->hasTable(self::TABLE_RESELLERS)) {
            Capsule::schema()->create(self::TABLE_RESELLERS, function ($table) {
                $table->increments('id');
                $table->string('name', 128);
                $table->string('username', 64);
                $table->string('password', 255);
                $table->string('access_hash', 128);
                $table->text('allowed_ips')->nullable();
                $table->text('allowed_product_groups')->nullable();
                $table->boolean('enabled')->default(1);
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->unique('username');
            });
        }

        if (!Capsule::schema()->hasTable(self::TABLE_SERVICES)) {
            Capsule::schema()->create(self::TABLE_SERVICES, function ($table) {
                $table->increments('id');
                $table->unsignedInteger('reseller_id');
                $table->unsignedInteger('service_id');
                $table->unsignedInteger('order_id')->default(0);
                $table->timestamp('created_at')->useCurrent();
                $table->unique('service_id');
                $table->index('reseller_id');
            });
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function all()
    {
        self::ensureSchema();
        return Capsule::table(self::TABLE_RESELLERS)->orderBy('name', 'asc')->get();
    }

    /**
     * @param int $id
     * @return object|null
     */
    public static function find($id)
    {
        self::ensureSchema();
        return Capsule::table(self::TABLE_RESELLERS)->where('id', (int) $id)->first();
    }

    /**
     * @param string $username
     * @return object|null
     */
    public static function findByUsername($username)
    {
        self::ensureSchema();
        return Capsule::table(self::TABLE_RESELLERS)
            ->where('username', (string) $username)
            ->first();
    }

    /**
     * @param array $data
     * @return int
     */
    public static function create(array $data)
    {
        self::ensureSchema();
        return (int) Capsule::table(self::TABLE_RESELLERS)->insertGetId([
            'name' => (string) $data['name'],
            'username' => (string) $data['username'],
            'password' => (string) $data['password'],
            'access_hash' => (string) $data['access_hash'],
            'allowed_ips' => isset($data['allowed_ips']) ? (string) $data['allowed_ips'] : '',
            'allowed_product_groups' => isset($data['allowed_product_groups']) ? (string) $data['allowed_product_groups'] : '',
            'enabled' => !empty($data['enabled']) ? 1 : 0,
            'notes' => isset($data['notes']) ? (string) $data['notes'] : '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param int $id
     * @param array $data
     * @return void
     */
    public static function update($id, array $data)
    {
        self::ensureSchema();
        $payload = [
            'name' => (string) $data['name'],
            'username' => (string) $data['username'],
            'access_hash' => (string) $data['access_hash'],
            'allowed_ips' => isset($data['allowed_ips']) ? (string) $data['allowed_ips'] : '',
            'allowed_product_groups' => isset($data['allowed_product_groups']) ? (string) $data['allowed_product_groups'] : '',
            'enabled' => !empty($data['enabled']) ? 1 : 0,
            'notes' => isset($data['notes']) ? (string) $data['notes'] : '',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (isset($data['password']) && $data['password'] !== '') {
            $payload['password'] = (string) $data['password'];
        }
        Capsule::table(self::TABLE_RESELLERS)->where('id', (int) $id)->update($payload);
    }

    /**
     * @param int $id
     * @return void
     */
    public static function delete($id)
    {
        self::ensureSchema();
        $id = (int) $id;
        // Keep ownership rows for audit; soft-disable by deleting account record only after unlink optional.
        // Preserve service ownership pointing at deleted reseller id so global admin can still see history.
        Capsule::table(self::TABLE_RESELLERS)->where('id', $id)->delete();
    }

    /**
     * @param int $resellerId
     * @param int $serviceId
     * @param int $orderId
     * @return void
     */
    public static function linkService($resellerId, $serviceId, $orderId = 0)
    {
        self::ensureSchema();
        $resellerId = (int) $resellerId;
        $serviceId = (int) $serviceId;
        $orderId = (int) $orderId;

        if ($resellerId < 1 || $serviceId < 1) {
            return;
        }

        $existing = Capsule::table(self::TABLE_SERVICES)->where('service_id', $serviceId)->first();
        if ($existing) {
            Capsule::table(self::TABLE_SERVICES)->where('service_id', $serviceId)->update([
                'reseller_id' => $resellerId,
                'order_id' => $orderId ?: $existing->order_id,
            ]);
            return;
        }

        Capsule::table(self::TABLE_SERVICES)->insert([
            'reseller_id' => $resellerId,
            'service_id' => $serviceId,
            'order_id' => $orderId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Whether a reseller may manage a given service.
     *
     * @param array $reseller Auth reseller context
     * @param int $serviceId
     * @return bool
     */
    public static function canManageService(array $reseller, $serviceId)
    {
        self::ensureSchema();
        $serviceId = (int) $serviceId;
        if ($serviceId < 1) {
            return false;
        }

        $row = Capsule::table(self::TABLE_SERVICES)->where('service_id', $serviceId)->first();

        // Unmapped services: only the shared/global credential may manage them.
        if (!$row) {
            return !empty($reseller['is_global']);
        }

        // Global credential can manage any mapped service (admin/ops).
        if (!empty($reseller['is_global'])) {
            return true;
        }

        return (int) $row->reseller_id === (int) $reseller['id'];
    }

    /**
     * Parse CSV / newline list of group IDs.
     *
     * @param string $raw
     * @return int[]
     */
    public static function parseGroupIds($raw)
    {
        $ids = [];
        foreach (preg_split('/[\s,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $id = (int) $part;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }
}
