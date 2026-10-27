<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\DataGrid;

use Lemonade\Admin\Action\Presentation\ModuleActionPresentationFactory;
use Lemonade\Admin\Authorization\AuthorizationService;
use Lemonade\Admin\DataGrid\Action\DataGridRowActionDefinition;
use Lemonade\Admin\DataGrid\Cell\ScalarCell;
use Lemonade\Admin\DataGrid\Cell\StackedCell;
use Lemonade\Admin\DataGrid\Cell\StatusCell;
use Lemonade\Admin\DataGrid\Cell\StatusVariant;
use Lemonade\Admin\DataGrid\Cell\ThumbnailCell;
use Lemonade\Admin\DataGrid\Contract\DataGridProviderInterface;
use Lemonade\Admin\DataGrid\DataGridColumnDefinition;
use Lemonade\Admin\DataGrid\DataGridDefinition;
use Lemonade\Admin\DataGrid\DataGridFilterDefinition;
use Lemonade\Admin\DataGrid\DataGridResult;
use Lemonade\Admin\DataGrid\DataGridRowDefinition;
use Lemonade\Admin\DataGrid\Query\DataGridQuery;
use Lemonade\Admin\Icon\AdminIcon;
use Lemonade\Admin\Localization\LanguageRegistry;
use Lemonade\Admin\Module\AdminModuleRouteResolver;
use Lemonade\Admin\Presentation\AdminThumbnailComponent;
use Lemonade\Admin\Presentation\Models\AdminFileModel;
use Lemonade\Admin\Select\SelectOptionDefinition;
use Lemonade\Admin\Select\StaticSelectOptionSource;
use Lemonade\Cms\Auction\Models\AuctionModel;
use Lemonade\Cms\CmsPublicationState;
use Lemonade\Framework\Localization\TranslatorInterface;
use Lemonade\Framework\Routing\UrlGenerator;

/**
 * Promita Auction clanky do shared Admin DataGridu bez translation N+1 dotazu
 */
final readonly class AuctionDataGrid implements DataGridProviderInterface
{
    /**
     * Nastavuje Auction persistence projekci pro DataGrid
     */
    public function __construct(
        private AuctionModel $auction,
        private AuthorizationService $authorization,
        private TranslatorInterface $translator,
        private ModuleActionPresentationFactory $actionPresentation,
        private AdminModuleRouteResolver $routes,
        private UrlGenerator $urls,
        private AdminFileModel $files,
        private AdminThumbnailComponent $thumbnails,
        private LanguageRegistry $languages,
    ) {}

    /**
     * Vrati pravo potrebne pro nacteni Auction prehledu
     */
    public function permission(): string
    {
        return 'cms.auction.view_own';
    }

    /**
     * Deklaruje sloupce, search, filtry a deterministic razeni prehledu
     */
    public function dataGridDefinition(): DataGridDefinition
    {
        return new DataGridDefinition(
            id: 'cms-auction',
            columns: [
                new DataGridColumnDefinition('title', 'auction.fields.title', 'title'),
                new DataGridColumnDefinition('location', 'auction.fields.location', 'location'),
                new DataGridColumnDefinition('startingPrice', 'auction.fields.starting_price', 'startingPrice'),
                new DataGridColumnDefinition('auctionAt', 'auction.fields.auction_at', 'auctionAt'),
                new DataGridColumnDefinition('auctionState', 'auction.fields.auction_state', 'auctionState'),
                new DataGridColumnDefinition('state', 'auction.fields.publication_state', 'state'),
                new DataGridColumnDefinition('publishedAt', 'auction.fields.published_at', 'publishedAt', 'd-none d-lg-table-cell', configurable: true),
                new DataGridColumnDefinition('owner', 'auction.fields.owner', class: 'd-none d-xl-table-cell', configurable: true),
                new DataGridColumnDefinition('createdAt', 'auction.fields.created_at', 'createdAt', configurable: true),
                new DataGridColumnDefinition('updatedAt', 'auction.fields.updated_at', 'updatedAt', 'd-none d-xl-table-cell', configurable: true),
                new DataGridColumnDefinition('deletedAt', 'auction.fields.deleted_at', configurable: true, visibleByDefault: false),
                new DataGridColumnDefinition('actions', 'admin.common.actions'),
            ],
            searchEnabled: true,
            filters: [
                new DataGridFilterDefinition(
                    key: 'status',
                    optionSource: new StaticSelectOptionSource(
                        options: [
                            new SelectOptionDefinition('active', $this->translator->get('auction.list.active')),
                            new SelectOptionDefinition('deleted', $this->translator->get('auction.list.deleted')),
                        ],
                    ),
                ),
                new DataGridFilterDefinition('state', new StaticSelectOptionSource([
                    new SelectOptionDefinition('new', $this->translator->get('admin.status.new')),
                    new SelectOptionDefinition('draft', $this->translator->get('admin.status.draft')),
                    new SelectOptionDefinition('published', $this->translator->get('admin.status.published')),
                ])),
                new DataGridFilterDefinition('auctionState', new StaticSelectOptionSource(array_map(
                    fn(string $state): SelectOptionDefinition => new SelectOptionDefinition($state, $this->translator->get('auction.states.' . $state)),
                    ['preparing', 'active', 'ended', 'cancelled'],
                ))),
                new DataGridFilterDefinition('locale', new StaticSelectOptionSource(array_map(
                    fn(array $language): SelectOptionDefinition => new SelectOptionDefinition($language['code'], $language['name']),
                    $this->auction->dataGridLocales(),
                ))),
            ],
            defaultSortKey: 'publishedAt',
            defaultSortDirection: 'desc',
            defaultPageSize: 25,
            maximumPageSize: 100,
            defaultView: 'active',
            showAllView: false,
        );
    }

    /**
     * Vrati typed radky z jedine batch projekce Auction modelu
     */
    public function execute(DataGridQuery $query): DataGridResult
    {
        $canViewAll = $this->authorization->hasPermission('cms.auction.view');
        $ownerId = $canViewAll ? null : $this->authorization->currentUserId();
        if (!$canViewAll && $ownerId === null) {
            return new DataGridResult([], $query->page(), $query->pageSize(), 0);
        }
        $page = $this->auction->listForDataGrid(
            $query,
            $this->languages->defaultLocale(),
            $ownerId,
        );
        $featuredImages = $this->files->findForEntities(
            'cms.auction',
            array_map(static fn(array $article): int => (int) $article['id'], $page->items()),
            'thumbnail',
        );
        $rows = array_map(
            fn(array $article): DataGridRowDefinition => $this->row(
                $article,
                $featuredImages[(int) $article['id']] ?? null,
            ),
            $page->items(),
        );

        return new DataGridResult($rows, $page->page(), $page->perPage(), $page->total());
    }

    /**
     * Promita jeden clanek na odkazovatelny aktivni nebo read-only smazany radek
     *
     * @param array<string,mixed> $article
     * @param array{
     *     id:int,
     *     module_code:string,
     *     entity_id:int,
     *     usage:string,
     *     kind:string,
     *     original_filename:string,
     *     display_name:string|null,
     *     caption:string|null,
     *     extension:string,
     *     mime_type:string,
     *     file_size:int,
     *     width:int|null,
     *     height:int|null,
     * }|null $featuredImage
     */
    private function row(array $article, ?array $featuredImage): DataGridRowDefinition
    {
        $articleId = (int) $article['id'];
        $locale = (string) $article['locale'];
        $title = trim((string) ($article['title'] ?? ''));
        $displayTitle = $title === ''
            ? $this->translator->get('admin.datagrid.untitled')
            : $title;
        $deleted = $article['deleted_at'] !== null;
        $ownerId = $article['owner_id'] === null ? null : (int) $article['owner_id'];
        $editable = !$deleted && $this->authorization->canForOwner(
            'cms.auction.edit',
            'cms.auction.edit_own',
            $ownerId,
        );

        return new DataGridRowDefinition(
            id: $articleId,
            cells: [
                'title' => new ThumbnailCell(
                    value: $displayTitle,
                    thumbnail: $featuredImage === null
                        ? $this->thumbnails->fallback(
                            fallback: null,
                            size: 'compact',
                            shape: 'rounded',
                        )
                        : $this->thumbnails->image(
                            module: 'cms.auction',
                            imageId: (string) $featuredImage['id'],
                            alt: $displayTitle,
                            fallback: null,
                            size: 'compact',
                            shape: 'rounded',
                            presentation: 'datagrid',
                        ),
                    secondary: (string) ($article['canonical_path'] ?? ''),
                    url: $editable ? $this->editUrl($articleId, $locale) : null,
                ),
                'location' => new ScalarCell((string) ($article['location'] ?? '—')),
                'startingPrice' => new ScalarCell($article['starting_price'] === null ? '—' : (string) $article['starting_price'] . ' CZK'),
                'auctionAt' => new ScalarCell((string) ($article['auction_at'] ?? '—')),
                'auctionState' => new ScalarCell($this->translator->get('auction.states.' . $article['auction_state'])),
                'state' => new StatusCell(
                    $this->publicationStateLabel((int) $article['publication_state']),
                    (int) $article['publication_state'] === CmsPublicationState::Published->value ? StatusVariant::Success : StatusVariant::Muted,
                ),
                'publishedAt' => new ScalarCell((string) ($article['published_at'] ?? '—')),
                'owner' => new ScalarCell((string) ($article['owner_name'] ?? '—')),
                'createdAt' => $this->auditMetadataCell($article['created_at'] ?? null, $article['created_by_name'] ?? null),
                'updatedAt' => $this->auditMetadataCell($article['updated_at'] ?? null, $article['updated_by_name'] ?? null),
                'deletedAt' => $this->auditMetadataCell($article['deleted_at'] ?? null, $article['deleted_by_name'] ?? null),
            ],
            actions: $this->rowActions($articleId, $deleted, $locale, $ownerId),
        );
    }

    /**
     * Vrati lokalizovany label publication stavu z databazove hodnoty
     */
    private function publicationStateLabel(int $value): string
    {
        $state = CmsPublicationState::tryFrom($value) ?? CmsPublicationState::New;

        return $this->translator->get('admin.status.' . match ($state) {
            CmsPublicationState::New => 'new',
            CmsPublicationState::Draft => 'draft',
            CmsPublicationState::Published => 'published',
        });
    }

    /**
     * Nabizi editaci pouze jako neautoritativni presentation akci
     *
     * @return list<DataGridRowActionDefinition>
     */
    private function rowActions(
        int $articleId,
        bool $deleted,
        string $contentLocale,
        ?int $ownerId,
    ): array {
        $actions = [];
        if (!$deleted && $this->authorization->canForOwner('cms.auction.edit', 'cms.auction.edit_own', $ownerId)) {
            $actions[] = (new DataGridRowActionDefinition(
                key: 'edit',
                label: $this->translator->get('admin.common.edit'),
                url: $this->editUrl($articleId, $contentLocale),
                method: 'GET',
            ))->withIcon(AdminIcon::PencilSquare);
        }
        $canChangeLifecycle = $deleted
            ? $this->authorization->canForOwner('cms.auction.restore', 'cms.auction.restore_own', $ownerId)
            : $this->authorization->canForOwner('cms.auction.delete', 'cms.auction.delete_own', $ownerId);
        if ($canChangeLifecycle) {
            $action = $this->actionPresentation->rowAction(
                moduleCode: 'cms.auction',
                action: $deleted ? 'restore' : 'delete',
                entityId: $articleId,
            );
            if ($action !== null) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * Zobrazi auditni datum nad identitou uzivatele
     */
    private function auditMetadataCell(mixed $date, mixed $actor): StackedCell
    {
        $date = trim((string) $date);
        $actor = trim((string) $actor);

        return new StackedCell(
            value: $date === '' ? '—' : $date,
            secondary: $date === '' || $actor === '' ? '—' : $actor,
        );
    }

    /**
     * Sklada canonical URL editoru s explicitni content locale mutace
     */
    private function editUrl(int $articleId, ?string $contentLocale = null): string
    {
        $url = $this->urls->route(
            $this->routes->managementRouteName('auction', 'edit'),
            ['module' => 'auction', 'id' => $articleId],
        );

        return $contentLocale === null ? $url : $url . '?locale=' . rawurlencode($contentLocale);
    }
}
