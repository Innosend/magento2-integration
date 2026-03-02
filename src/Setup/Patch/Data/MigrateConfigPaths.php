<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.1.0
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Migrates configuration values from the 1.0.x path structure to 1.1.0.
 *
 * Changes applied:
 * - innosend_api/configuration/pickup_points/api_token
 *   → innosend_api/configuration/api_token
 *
 * Removed paths (values discarded – no replacement):
 * - innosend_api/configuration/api_key
 * - innosend_api/configuration/api_secret
 * - innosend_api/configuration/allow_mutations
 * - innosend_api/configuration/enable_pickup_points
 */
class MigrateConfigPaths implements DataPatchInterface
{
    private const OLD_API_TOKEN_PATH = 'innosend_api/configuration/pickup_points/api_token';
    private const NEW_API_TOKEN_PATH = 'innosend_api/configuration/api_token';

    private const REMOVED_PATHS = [
        'innosend_api/configuration/api_key',
        'innosend_api/configuration/api_secret',
        'innosend_api/configuration/allow_mutations',
        'innosend_api/configuration/enable_pickup_points',
    ];

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Apply data patch
     *
     * @return $this
     */
    public function apply(): static
    {
        $setup = $this->moduleDataSetup;
        $setup->startSetup();

        $connection = $setup->getConnection();
        $table = $setup->getTable('core_config_data');

        // Migrate api_token from old nested path to new flat path
        $oldRows = $connection->fetchAll(
            $connection->select()
                ->from($table)
                ->where('path = ?', self::OLD_API_TOKEN_PATH)
        );

        foreach ($oldRows as $row) {
            // Check if a value already exists at the new path for this scope
            $existingCount = (int) $connection->fetchOne(
                $connection->select()
                    ->from($table, ['COUNT(*)'])
                    ->where('path = ?', self::NEW_API_TOKEN_PATH)
                    ->where('scope = ?', $row['scope'])
                    ->where('scope_id = ?', $row['scope_id'])
            );

            if ($existingCount === 0) {
                $connection->insert($table, [
                    'scope'    => $row['scope'],
                    'scope_id' => $row['scope_id'],
                    'path'     => self::NEW_API_TOKEN_PATH,
                    'value'    => $row['value'],
                ]);
            }

            $connection->delete($table, [
                'path = ?'     => self::OLD_API_TOKEN_PATH,
                'scope = ?'    => $row['scope'],
                'scope_id = ?' => $row['scope_id'],
            ]);
        }

        // Delete removed config paths
        foreach (self::REMOVED_PATHS as $path) {
            $connection->delete($table, ['path = ?' => $path]);
        }

        $setup->endSetup();
        return $this;
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
