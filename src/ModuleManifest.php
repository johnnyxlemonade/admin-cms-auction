<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction;

use Lemonade\Admin\Audit\AuditEventDefinition;
use Lemonade\Admin\Authorization\PermissionDefinition;
use Lemonade\Admin\Icon\AdminIcon;
use Lemonade\Admin\Module\AdminModuleMetadata;
use Lemonade\Admin\Module\AdminNavigationGroup;
use Lemonade\Admin\Module\Contract\AdminModuleDefinitionInterface;
use Lemonade\Admin\Module\Contract\AdminModuleFileCapabilitiesInterface;
use Lemonade\Admin\Modules\Manifest\ModuleManifestInterface;
use Lemonade\Admin\Modules\Migration\ModuleMigrationManifestInterface;
use Lemonade\Admin\Presentation\AdminFileCapabilities;
use Lemonade\Admin\Presentation\AdminFilePresentation;
use Lemonade\Framework\Database\Migration\MigrationInterface;

/**
 * Declares the Auction module, permissions, file capabilities and migrations
 */
final readonly class ModuleManifest implements
    ModuleManifestInterface,
    ModuleMigrationManifestInterface,
    AdminModuleDefinitionInterface,
    AdminModuleFileCapabilitiesInterface
{
    public const string CODE = 'cms.auction';

    /**
     * Returns the stable Auction module code
     */
    public function code(): string
    {
        return self::CODE;
    }

    /**
     * Returns the translation key for the Auction module name
     */
    public function labelKey(): string
    {
        return 'auction.module.name';
    }

    /**
     * Returns the Auction runtime provider class
     */
    public function runtimeProvider(): string
    {
        return CmsAuctionServiceProvider::class;
    }

    /**
     * Returns migrations for the standard deployment lifecycle
     *
     * @return list<class-string<MigrationInterface>>
     */
    public function migrations(): array
    {
        return [Migrations\CreateAuctionArticles::class];
    }

    /**
     * Returns Admin navigation and presentation metadata
     */
    public function adminMetadata(): AdminModuleMetadata
    {
        return new AdminModuleMetadata(
            nameKey: 'auction.module.name',
            icon: AdminIcon::Hammer,
            navigationGroup: AdminNavigationGroup::Cms,
            navigationOrder: 10,
            routeSegment: 'auction',
            navigationPermission: 'cms.auction.view_own',
            descriptionKey: 'auction.module.description',
        );
    }

    /**
     * Returns the system media capabilities of Auction articles
     */
    public function fileCapabilities(): AdminFileCapabilities
    {
        return new AdminFileCapabilities(
            thumbnail: AdminFilePresentation::Landscape,
            gallery: true,
            attachments: true,
            richTextImages: true,
            richTextFiles: true,
        );
    }

    /**
     * Returns permissions required by Auction operations
     *
     * @return list<PermissionDefinition>
     */
    public function permissionDefinitions(): array
    {
        return [
            new PermissionDefinition('cms.auction.view_own', self::CODE, 'auction.permissions.view_own'),
            new PermissionDefinition('cms.auction.view', self::CODE, 'auction.permissions.view', requires: ['cms.auction.view_own']),
            new PermissionDefinition('cms.auction.create', self::CODE, 'auction.permissions.create', requires: ['cms.auction.view_own']),
            new PermissionDefinition('cms.auction.edit_own', self::CODE, 'auction.permissions.edit_own', requires: ['cms.auction.view_own']),
            new PermissionDefinition('cms.auction.edit', self::CODE, 'auction.permissions.edit', requires: ['cms.auction.edit_own', 'cms.auction.view']),
            new PermissionDefinition('cms.auction.publish_own', self::CODE, 'auction.permissions.publish_own', requires: ['cms.auction.view_own']),
            new PermissionDefinition('cms.auction.publish', self::CODE, 'auction.permissions.publish', requires: ['cms.auction.publish_own', 'cms.auction.view']),
            new PermissionDefinition('cms.auction.delete_own', self::CODE, 'auction.permissions.delete_own', requires: ['cms.auction.view_own']),
            new PermissionDefinition('cms.auction.delete', self::CODE, 'auction.permissions.delete', requires: ['cms.auction.delete_own', 'cms.auction.view']),
            new PermissionDefinition('cms.auction.restore_own', self::CODE, 'auction.permissions.restore_own', requires: ['cms.auction.view_own']),
            new PermissionDefinition('cms.auction.restore', self::CODE, 'auction.permissions.restore', requires: ['cms.auction.restore_own', 'cms.auction.view']),
            new PermissionDefinition('cms.auction.assign_owner', self::CODE, 'auction.permissions.assign_owner', requires: ['cms.auction.view_own']),
        ];
    }

    /**
     * Returns presentation metadata for Auction audit events
     *
     * @return list<AuditEventDefinition>
     */
    public function auditEventDefinitions(): array
    {
        return [
            new AuditEventDefinition('created', 'auction.audit.events.created', AdminIcon::PlusLg),
            new AuditEventDefinition('updated', 'auction.audit.events.updated', AdminIcon::PencilSquare),
            new AuditEventDefinition('translation_created', 'auction.audit.events.translation_created', AdminIcon::PlusLg),
            new AuditEventDefinition('translation_updated', 'auction.audit.events.translation_updated', AdminIcon::PencilSquare),
            new AuditEventDefinition('published', 'auction.audit.events.published', AdminIcon::CheckCircle),
            new AuditEventDefinition('unpublished', 'auction.audit.events.unpublished', AdminIcon::PauseCircle),
            new AuditEventDefinition('deleted', 'auction.audit.events.deleted', AdminIcon::Trash3),
            new AuditEventDefinition('restored', 'auction.audit.events.restored', AdminIcon::ArrowCounterclockwise),
            new AuditEventDefinition('owner_transferred', 'auction.audit.events.owner_transferred', AdminIcon::PersonGear),
        ];
    }
}
