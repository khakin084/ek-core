<?php

namespace App\Enums;

use InvalidArgumentException;

/**
 * Ordinal permission levels — one integer per (subject, module), not a bag of flags.
 *
 * Capability is strictly increasing on a single record type, so every check is a
 * comparison: `$level >= PermissionLevel::ReadWrite->value`.
 *
 *   None         0   nothing (hidden)
 *   Read         1   view, list
 *   ReadWrite    2   + create, edit
 *   FullControl  3   + delete, EXPORT
 *
 * Export sits at FullControl deliberately — bulk download is an exfiltration surface, so
 * a Read user can see records in-app but cannot pull them out. Route export endpoints
 * through a full_control check, not the same gate as the on-screen list.
 *
 * Approve is NOT on this scale. Approval authority lives in user_approval_levels and is
 * resolved by the Approval service — never inside FullControl.
 *
 * CONTAINERS hold 0 or 1 only. A container's level is menu visibility and grants nothing
 * to its children.
 */
enum PermissionLevel: int
{
    case None        = 0;
    case Read        = 1;
    case ReadWrite   = 2;
    case FullControl = 3;

    /**
     * Parses the string form used in middleware signatures: `perm:accounts.tax_codes,read_write`.
     *
     * @throws InvalidArgumentException on an unknown key — a typo in a route definition
     *         would otherwise surface as a confusing 403 rather than a boot-time failure.
     */
    public static function fromKey(string $key): self
    {
        return match (strtolower(trim($key))) {
            'none'                       => self::None,
            'read'                       => self::Read,
            'read_write', 'readwrite'    => self::ReadWrite,
            'full_control', 'fullcontrol' => self::FullControl,
            default => throw new InvalidArgumentException(
                "Unknown permission level [{$key}]. Expected: none, read, read_write, full_control."
            ),
        };
    }

    /**
     * Clamps a raw integer from the database. Guards against a stored grant that exceeds
     * a module's current max_level — which happens between a leaf->container flip and the
     * sync's clamping pass.
     */
    public static function fromLevel(int $level, int $maxLevel = 3): self
    {
        return self::from(max(0, min($level, $maxLevel)));
    }

    public function key(): string
    {
        return match ($this) {
            self::None        => 'none',
            self::Read        => 'read',
            self::ReadWrite   => 'read_write',
            self::FullControl => 'full_control',
        };
    }

    /**
     * Column headers in the Access Controls matrix.
     */
    public function label(): string
    {
        return match ($this) {
            self::None        => 'None',
            self::Read        => 'Read',
            self::ReadWrite   => 'Read/Write',
            self::FullControl => 'Full Control',
        };
    }

    public function atLeast(self|int $minimum): bool
    {
        return $this->value >= ($minimum instanceof self ? $minimum->value : $minimum);
    }

    public function allows(string $action): bool
    {
        return match (strtolower($action)) {
            'view', 'list'            => $this->atLeast(self::Read),
            'create', 'edit', 'update' => $this->atLeast(self::ReadWrite),
            'delete', 'export'        => $this->atLeast(self::FullControl),
            default => throw new InvalidArgumentException("Unknown action [{$action}]."),
        };
    }

    /**
     * Which toggle columns a matrix row should render. Containers stop at Read, which is
     * why parent rows in the UI show two toggles and leaf rows show four.
     *
     * @return array<self>
     */
    public static function columnsFor(int $maxLevel): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $level) => $level->value <= $maxLevel,
        ));
    }

    /**
     * @return array<int, string>  value => label, for select inputs and API metadata
     */
    public static function options(int $maxLevel = 3): array
    {
        $options = [];

        foreach (self::columnsFor($maxLevel) as $level) {
            $options[$level->value] = $level->label();
        }

        return $options;
    }
}
