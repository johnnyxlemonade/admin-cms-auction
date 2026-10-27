<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Migrations;

use Lemonade\Cms\Content\CmsContentSchema;
use Lemonade\Framework\Database\Migration\MigrationInterface;
use Lemonade\Framework\Database\Schema\Blueprint\TableBlueprint;
use Lemonade\Framework\Database\Schema\Schema;

/**
 * Vytvari oddelene tabulky drazeb a jejich lokalizovanych mutaci
 */
final readonly class CreateAuctionArticles implements MigrationInterface
{
    /**
     * Vrati stabilni identifikator migrace drazeb
     */
    public static function identifier(): string
    {
        return '20261005130000_create_cms_auctions';
    }

    /**
     * Vytvori spolecne CMS sloupce a domenove udaje drazeb
     */
    public function up(Schema $schema): void
    {
        $schema->create('cms_auction', static function (TableBlueprint $table): void {
            CmsContentSchema::root($table, 'cms_auction');
            $table->decimal('starting_price', 15, 2)->nullable();
            $table->string('currency', 3)->default('CZK');
            $table->datetime('auction_at')->nullable();
            $table->string('auction_state', 20)->default('preparing');
            $table->string('external_url', 2048)->nullable();
        }, ifNotExists: true);
        $schema->create('cms_auction_translation', static function (TableBlueprint $table): void {
            CmsContentSchema::translation($table, 'cms_auction', 'article_id');
            $table->string('location', 255)->nullable();
        }, ifNotExists: true);
        $schema->create('cms_auction_translation_tag', static function (TableBlueprint $table): void {
            CmsContentSchema::tags($table, 'cms_auction', 'cms_auction_translation');
        }, ifNotExists: true);
    }
}
