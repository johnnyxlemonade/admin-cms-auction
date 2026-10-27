<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction;

use Lemonade\Admin\Authorization\AuthorizationService;
use Lemonade\Admin\Cms\Content\CmsAdminRuntime;
use Lemonade\Admin\Cms\Content\CmsContentAdminProvider;
use Lemonade\Admin\Identity\LocalActorGuard;
use Lemonade\Admin\Localization\LanguageRegistry;
use Lemonade\Admin\Module\AdminModuleRouteResolver;
use Lemonade\Cms\Auction\DataGrid\AuctionDataGrid;
use Lemonade\Cms\Auction\Editor\AuctionEditor;
use Lemonade\Cms\Auction\Editor\AuctionEditorValidationSchema;
use Lemonade\Cms\Content\CmsContentDefinition;
use Lemonade\Cms\Content\CmsContentLifecycle;
use Lemonade\Cms\Content\CmsDomainMapping;
use Lemonade\Cms\Content\Models\CmsContentModel;
use Lemonade\Cms\Routing\Cms\CmsRouteReservationService;
use Lemonade\Framework\Container\ContainerBuilderInterface;
use Lemonade\Framework\Container\ContainerInterface;
use Lemonade\Framework\Core\BootableServiceProviderInterface;
use Lemonade\Framework\Core\ServiceProviderInterface;
use Lemonade\Framework\Database\Database;
use Lemonade\Framework\Routing\UrlGenerator;
use Lemonade\Framework\Support\Slug\Slugger;

/**
 * Pripojuje modul k existujicimu sdilenemu CMS Admin transportu
 */
final readonly class CmsAuctionServiceProvider implements ServiceProviderInterface, BootableServiceProviderInterface
{
    /**
     * Registruje editorove action transporty modulu
     */
    public function register(ContainerBuilderInterface $container): void
    {
        $container->scoped('cms.auction.definition', static fn(): CmsContentDefinition => self::contentDefinition());
        $container->scoped('cms.auction.content_model', static fn(ContainerInterface $container): CmsContentModel => new CmsContentModel(
            $container->get(Database::class),
            $container->get('cms.auction.definition'),
        ));
        $container->scoped('cms.auction.lifecycle', static fn(ContainerInterface $container): CmsContentLifecycle => new CmsContentLifecycle(
            $container->get('cms.auction.definition'),
            $container->get('cms.auction.content_model'),
            new CmsDomainMapping(AuctionData::class),
            $container->get(CmsRouteReservationService::class),
            $container->get(Slugger::class),
            $container->get(CmsAdminRuntime::class),
        ));
        $container->scoped(AuctionEditor::class, static fn(ContainerInterface $container): AuctionEditor => new AuctionEditor(
            $container->get('cms.auction.read'),
            $container->get('cms.auction.lifecycle'),
            $container->get(LanguageRegistry::class),
            $container->get(LocalActorGuard::class),
            $container->get(AuctionEditorValidationSchema::class),
            $container->get(AdminModuleRouteResolver::class),
            $container->get(UrlGenerator::class),
            $container->get(AuthorizationService::class),
        ));
        self::adminProvider()->register($container);
    }

    /**
     * Vrati registrovane tabulky a atributove mapovani obsahu
     */
    public static function contentDefinition(): CmsContentDefinition
    {
        return new CmsContentDefinition(
            moduleCode: ModuleManifest::CODE,
            entityType: 'cms_auction',
            rootTable: 'cms_auction',
            translationTable: 'cms_auction_translation',
            translationForeignKey: 'article_id',
            tagTable: 'cms_auction_translation_tag',
            translationUniqueConstraint: 'uq_cms_auction_translation_locale',
            validationPrefix: 'auction',
            dataClass: AuctionData::class,
        );
    }

    /**
     * Pripoji preklady, grid, editor, stranky a akce modulu
     */
    public function boot(ContainerInterface $container): void
    {
        self::adminProvider()->boot($container);
    }

    /**
     * Nastavi stavajici presentation a action providery modulu
     */
    public static function adminProvider(): CmsContentAdminProvider
    {
        return new CmsContentAdminProvider(
            moduleCode: ModuleManifest::CODE,
            translationGroup: 'auction',
            translationPath: __DIR__ . '/Resources/lang',
            dataGridService: AuctionDataGrid::class,
            pageService: AuctionModulePageProvider::class,
            editorService: AuctionEditor::class,
            lifecycleService: 'cms.auction.lifecycle',
            contentDefinitionService: 'cms.auction.definition',
        );
    }
}
