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
use Lemonade\Cms\Auction\Public\PublishedAuctionReadModel;
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
use Lemonade\Framework\Localization\TranslatorInterface;
use Lemonade\Framework\Routing\UrlGenerator;
use Lemonade\Framework\Support\Slug\Slugger;

/**
 * Connects Auction to the shared CMS Admin transport and public reader
 */
final readonly class CmsAuctionServiceProvider implements ServiceProviderInterface, BootableServiceProviderInterface
{
    /**
     * Registers Auction services before the container is frozen
     */
    public function register(ContainerBuilderInterface $container): void
    {
        $container->scoped(PublishedAuctionReadModel::class, PublishedAuctionReadModel::class);
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
            $container->get(TranslatorInterface::class),
        ));
        self::adminProvider()->register($container);
    }

    /**
     * Returns the Auction table and domain mapping definition
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
     * Connects Auction resources and Admin contributions after registration
     */
    public function boot(ContainerInterface $container): void
    {
        self::adminProvider()->boot($container);
    }

    /**
     * Creates the shared CMS Admin provider for Auction
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
