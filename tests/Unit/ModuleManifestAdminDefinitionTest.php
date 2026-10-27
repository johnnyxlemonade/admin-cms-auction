<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Tests\Unit;

use Lemonade\Admin\Audit\AuditEventPresentationRegistry;
use Lemonade\Admin\Icon\AdminIcon;
use Lemonade\Admin\Module\AdminModuleRegistry;
use Lemonade\Admin\Module\AdminModuleRouteResolver;
use Lemonade\Admin\Module\AdminNavigationGroup;
use Lemonade\Admin\Navigation\AdminNavigationGroupRegistry;
use Lemonade\Admin\Navigation\AdminNavigationItem;
use Lemonade\Cms\Auction\ModuleManifest;
use PHPUnit\Framework\TestCase;

/**
 * Overuje stabilni Admin identitu a oddelene publish opravneni modulu
 */
final class ModuleManifestAdminDefinitionTest extends TestCase
{
    /**
     * Publish pravo nezavisi na edit opravneni a modul ma canonical segment
     */
    public function testDefinesIndependentPublishPermissionAndCanonicalAdminSegment(): void
    {
        $definition = new ModuleManifest();

        self::assertSame('cms.auction', $definition->code());
        self::assertSame(AdminNavigationGroup::Content, $definition->adminMetadata()->navigationGroup);
        self::assertSame('auction', $definition->adminMetadata()->routeSegment());
        self::assertSame('auction.module.description', $definition->adminMetadata()->descriptionKey());
        self::assertSame('cms.auction.view_own', $definition->adminMetadata()->navigationPermission());
        self::assertSame(
            ['cms.auction.publish_own', 'cms.auction.view'],
            $definition->permissionDefinitions()[6]->requires(),
        );
    }

    public function testDeclaresSharedMediaCapabilities(): void
    {
        $files = (new ModuleManifest())->fileCapabilities();

        self::assertNotNull($files->thumbnail);
        self::assertTrue($files->gallery);
        self::assertTrue($files->attachments);
        self::assertTrue($files->richTextImages);
        self::assertTrue($files->richTextFiles);
    }

    /**
     * Popis modulu je dostupny pro oba podporovane jazyky
     */
    public function testDefinesLocalizedModuleDescription(): void
    {
        foreach (['cs', 'en'] as $locale) {
            $catalog = require dirname(__DIR__, 2) . '/src/Resources/lang/' . $locale . '/auction.php';

            self::assertIsString($catalog['module']['description'] ?? null);
            self::assertNotSame('', $catalog['module']['description']);
        }
    }

    /**
     * Auditni udalosti Auction zustavaji registrovane pod canonical module kodem
     */
    public function testItDeclaresAndRegistersPresentationsForAllEmittedAuctionEvents(): void
    {
        $definition = new ModuleManifest();

        self::assertSame([
            'created',
            'updated',
            'translation_created',
            'translation_updated',
            'published',
            'unpublished',
            'deleted',
            'restored',
            'owner_transferred',
        ], array_map(static fn(\Lemonade\Admin\Audit\AuditEventDefinition $event): string => $event->suffix, $definition->auditEventDefinitions()));

        $registry = new AuditEventPresentationRegistry();
        $registry->registerModuleDefinition($definition);

        $module = $registry->modulePresentation('cms.auction');
        $event = $registry->presentation('cms.auction.published');

        self::assertNotNull($module);
        self::assertSame('auction.module.name', $module->nameKey());
        self::assertSame(AdminIcon::JournalText, $module->icon());
        self::assertNotNull($event);
        self::assertSame('auction.audit.events.published', $event->translationKey());
        self::assertSame(AdminIcon::CheckCircle, $event->icon());
    }

    /**
     * Standardni Auction lifecycle zachovava aktivni polozku z resolved group a segmentu
     */
    public function testItKeepsAuctionNavigationActiveAcrossTheStandardLifecycle(): void
    {
        $definition = new ModuleManifest();
        $registry = new AdminModuleRegistry(new AdminNavigationGroupRegistry());
        $registry->register($definition);
        $metadata = (new AdminModuleRouteResolver($registry))
            ->metadata('auction');
        $item = new AdminNavigationItem(
            key: 'module:' . $definition->code(),
            order: $metadata->navigationOrder(),
            label: $metadata->nameKey(),
            labelKey: $metadata->nameKey(),
            route: 'admin.content.module.index',
            routeParameters: ['module' => $metadata->routeSegment()],
            routePrefix: false,
            icon: $metadata->icon(),
            moduleCode: $definition->code(),
            moduleNavigationGroup: $metadata->navigationGroup,
            moduleRouteSegment: $metadata->routeSegment(),
        );

        foreach (['index', 'create', 'edit'] as $lifecycleAction) {
            self::assertTrue(
                $item->isActiveFor($metadata),
                sprintf('%s must keep cms.auction active.', $lifecycleAction),
            );
        }
    }
}
