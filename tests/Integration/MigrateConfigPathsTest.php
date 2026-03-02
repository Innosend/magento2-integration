<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Tests\Integration;

use Innosend\Integration\Setup\Patch\Data\MigrateConfigPaths;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Integration-style test for the MigrateConfigPaths data patch.
 * Uses a mock DB connection to verify queries executed.
 */
class MigrateConfigPathsTest extends TestCase
{
    /**
     * Verify that the patch moves the old pickup_points/api_token value
     * to the new api_token path and deletes the old row.
     */
    public function testApplyMigratesTokenPath(): void
    {
        $oldRow = [
            'config_id' => 1,
            'scope'     => 'default',
            'scope_id'  => 0,
            'path'      => 'innosend_api/configuration/pickup_points/api_token',
            'value'     => 'encrypted-token-value',
        ];

        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $select     = $this->createMock(\Magento\Framework\DB\Select::class);

        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        // fetchAll for the old token path returns one row
        $connection->method('select')->willReturn($select);
        $connection->method('fetchAll')
            ->with($select)
            ->willReturn([$oldRow]);

        // fetchOne for existence check at new path returns 0 (no existing value)
        $connection->method('fetchOne')->willReturn('0');

        $insertedData = null;
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                $this->anything(),
                $this->callback(function (array $data) use (&$insertedData) {
                    $insertedData = $data;
                    return true;
                })
            );

        $deletedPaths = [];
        $connection->expects($this->atLeastOnce())
            ->method('delete')
            ->with(
                $this->anything(),
                $this->callback(function ($where) use (&$deletedPaths) {
                    if (is_array($where) && isset($where['path = ?'])) {
                        $deletedPaths[] = $where['path = ?'];
                    }
                    return true;
                })
            );

        $setup = $this->createMock(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturn('core_config_data');

        $patch = new MigrateConfigPaths($setup);
        $patch->apply();

        // Verify the new row was inserted with the correct path
        $this->assertNotNull($insertedData);
        $this->assertSame('innosend_api/configuration/api_token', $insertedData['path']);
        $this->assertSame('encrypted-token-value', $insertedData['value']);
        $this->assertSame('default', $insertedData['scope']);
    }

    /**
     * Verify that removed paths are deleted even when no token migration occurs.
     */
    public function testApplyDeletesRemovedPaths(): void
    {
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $select     = $this->createMock(\Magento\Framework\DB\Select::class);

        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        // No old token rows
        $connection->method('select')->willReturn($select);
        $connection->method('fetchAll')->willReturn([]);

        $deletedPaths = [];
        $connection->method('delete')
            ->with(
                $this->anything(),
                $this->callback(function ($where) use (&$deletedPaths) {
                    if (is_array($where) && isset($where['path = ?'])) {
                        $deletedPaths[] = $where['path = ?'];
                    }
                    return true;
                })
            );

        $setup = $this->createMock(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturn('core_config_data');

        $patch = new MigrateConfigPaths($setup);
        $patch->apply();

        $expected = [
            'innosend_api/configuration/api_key',
            'innosend_api/configuration/api_secret',
            'innosend_api/configuration/allow_mutations',
            'innosend_api/configuration/enable_pickup_points',
        ];

        foreach ($expected as $path) {
            $this->assertContains($path, $deletedPaths, "Expected path $path to be deleted.");
        }
    }

    public function testGetDependenciesReturnsEmptyArray(): void
    {
        $this->assertSame([], MigrateConfigPaths::getDependencies());
    }

    public function testGetAliasesReturnsEmptyArray(): void
    {
        $setup  = $this->createMock(ModuleDataSetupInterface::class);
        $patch  = new MigrateConfigPaths($setup);
        $this->assertSame([], $patch->getAliases());
    }
}
