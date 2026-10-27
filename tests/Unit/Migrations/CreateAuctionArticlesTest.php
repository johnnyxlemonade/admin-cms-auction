<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Tests\Unit\Migrations;

use Lemonade\Cms\Auction\Migrations\CreateAuctionArticles;
use Lemonade\Framework\Database\Connection\DatabaseConfig;
use Lemonade\Framework\Database\DatabaseDriverInterface;
use Lemonade\Framework\Database\Driver\Mysql\MysqlIdentifierEscaper;
use Lemonade\Framework\Database\Driver\Mysql\MysqlSchemaGrammar;
use Lemonade\Framework\Database\Driver\Mysql\MysqlSqlEscaper;
use Lemonade\Framework\Database\Schema\Schema;
use Lemonade\Framework\Database\Schema\SchemaCompiler;
use PHPUnit\Framework\TestCase;

/**
 * Overuje ownership sloupce v puvodnim fresh-install schema Auction
 */
final class CreateAuctionArticlesTest extends TestCase
{
    public function testFreshInstallRootUsesNullableLocalUserForeignKeys(): void
    {
        $statements = [];
        $database = $this->createMock(DatabaseDriverInterface::class);
        $database->method('query')->willReturnCallback(
            static function (string $statement) use (&$statements): bool {
                if (preg_match('/^CREATE TABLE(?: IF NOT EXISTS)? `([^`]+)`/', $statement, $matches) === 1) {
                    $statements[$matches[1]] = $statement;
                }

                return true;
            },
        );
        $configuration = DatabaseConfig::fromArray(['driver' => 'mysql']);
        $identifierEscaper = new MysqlIdentifierEscaper('');
        $schema = new Schema(
            new SchemaCompiler(new MysqlSchemaGrammar(new MysqlSqlEscaper($identifierEscaper), $configuration)),
            $database,
        );

        (new CreateAuctionArticles())->up($schema);

        self::assertSame(
            ['cms_auction', 'cms_auction_translation', 'cms_auction_translation_tag'],
            array_keys($statements),
        );
        $root = $statements['cms_auction'];
        self::assertStringContainsString('`starting_price` DECIMAL(15,2) NULL', $root);
        self::assertStringContainsString('`auction_at` DATETIME NULL', $root);
        self::assertStringContainsString('`auction_state`', $root);
        self::assertStringContainsString('`location`', $statements['cms_auction_translation']);
        self::assertStringNotContainsString('`location`', $root);
        self::assertStringNotContainsString('`show_reading_time`', $root);
        foreach (['created_by', 'updated_by', 'deleted_by', 'owner_id'] as $column) {
            self::assertStringContainsString(sprintf('`%s` BIGINT UNSIGNED NULL', $column), $root);
        }
        foreach (['created_by', 'updated_by', 'deleted_by', 'owner'] as $suffix) {
            self::assertStringContainsString('CONSTRAINT `fk_cms_auction_' . $suffix . '`', $root);
        }
        self::assertSame(4, substr_count($root, 'ON UPDATE RESTRICT ON DELETE SET NULL'));
        self::assertStringContainsString('KEY `idx_cms_auction_owner_deleted` (`owner_id`, `deleted_at`)', $root);
    }
}
