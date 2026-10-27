<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Tests\Unit;

use Lemonade\Admin\Action\ModuleActionRegistry;
use Lemonade\Admin\DataGrid\DataGridRegistry;
use Lemonade\Admin\Editor\EditorRegistry;
use Lemonade\Admin\Localization\ClientTranslationGroupRegistry;
use Lemonade\Admin\Page\ModulePageRegistry;
use Lemonade\Cms\Auction\CmsAuctionServiceProvider;
use Lemonade\Framework\Container\Container;
use Lemonade\Framework\Container\ContainerInterface;
use Lemonade\Framework\Localization\TranslationResourceRegistry;
use Lemonade\Framework\Routing\RouteRegistrarInterface;
use PHPUnit\Framework\TestCase;

/**
 * Overuje oddeleni administracniho Auction modulu od verejneho routingu hosta
 */
final class CmsAuctionServiceProviderTest extends TestCase
{
    /**
     * Ověří napojení stávajících registrů i oddělená oprávnění akcí
     */
    public function testBootRegistersExistingAdminCapabilities(): void
    {
        $grid = new DataGridRegistry();
        $editors = new EditorRegistry();
        $pages = new ModulePageRegistry();
        $actions = new ModuleActionRegistry();
        $groups = new ClientTranslationGroupRegistry();
        $resources = new TranslationResourceRegistry();
        $services = [
            DataGridRegistry::class => $grid,
            EditorRegistry::class => $editors,
            ModulePageRegistry::class => $pages,
            ModuleActionRegistry::class => $actions,
            ClientTranslationGroupRegistry::class => $groups,
            TranslationResourceRegistry::class => $resources,
        ];
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(static fn(string $id): object => $services[$id]);

        (new CmsAuctionServiceProvider())->boot($container);

        self::assertSame(\Lemonade\Cms\Auction\DataGrid\AuctionDataGrid::class, $grid->providerServiceId('cms.auction'));
        self::assertSame(\Lemonade\Cms\Auction\Editor\AuctionEditor::class, $editors->providerServiceId('cms.auction'));
        self::assertSame(\Lemonade\Cms\Auction\AuctionModulePageProvider::class, $pages->indexProviderServiceId('cms.auction'));
        self::assertSame(\Lemonade\Cms\Auction\AuctionModulePageProvider::class, $pages->editorProviderServiceId('cms.auction'));
        self::assertTrue($groups->has('auction'));
        foreach (['create', 'save', 'change-url', 'delete', 'restore', 'assign-owner'] as $action) {
            self::assertTrue($actions->has('cms.auction', $action));
        }
        self::assertSame('cms.auction.delete_own', $actions->action('cms.auction', 'delete')['definition']->permission());
    }

    /**
     * Registruje pouze administracni sluzby bez verejnych rout
     */
    public function testItDoesNotActivatePublicAuctionRouting(): void
    {
        $container = new Container();

        (new CmsAuctionServiceProvider())->register($container);

        self::assertTrue($container->isBound('admin.cms.auction.action.create'));
        self::assertSame([], iterator_to_array($container->tagged(RouteRegistrarInterface::class)));
    }
}
