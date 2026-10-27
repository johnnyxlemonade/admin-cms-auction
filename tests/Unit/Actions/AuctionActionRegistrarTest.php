<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Tests\Unit\Actions;

use Lemonade\Admin\Action\ModuleActionRegistry;
use Lemonade\Cms\Auction\CmsAuctionServiceProvider;
use PHPUnit\Framework\TestCase;

/**
 * Overuje explicitni registraci Auction actions a jejich permission hranice
 */
final class AuctionActionRegistrarTest extends TestCase
{
    /**
     * Registruje URL akci oddelene od standardniho editoroveho save
     */
    public function testRegistersIndependentUrlChangeAction(): void
    {
        $actions = new ModuleActionRegistry();
        CmsAuctionServiceProvider::adminProvider()->registerActions($actions);

        self::assertTrue($actions->has('cms.auction', 'change-url'));
        self::assertSame(
            'cms.auction.edit_own',
            $actions->action('cms.auction', 'change-url')['definition']->permission(),
        );
        self::assertTrue($actions->has('cms.auction', 'assign-owner'));
    }
}
