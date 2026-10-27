<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Editor;

use Lemonade\Admin\Action\Presentation\AdminActionFormDefinition;
use Lemonade\Admin\Cms\Content\CmsDisplaySettings;
use Lemonade\Admin\Editor\AdminEditor\Action\AdminEditorActionDefinition;
use Lemonade\Admin\Editor\AdminEditor\Action\AdminEditorSaveBarDefinition;
use Lemonade\Admin\Editor\AdminEditor\AdminEditorBuilder;
use Lemonade\Admin\Editor\AdminEditor\AdminEditorDefinition;
use Lemonade\Admin\Editor\AdminEditor\Field\Field;
use Lemonade\Admin\Editor\AdminEditor\Header\AdminEditorHeaderContextDefinition;
use Lemonade\Admin\Editor\AdminEditor\Header\AdminEditorHeaderDefinition;
use Lemonade\Admin\Editor\AdminEditor\Layout\AdminEditorSidebarDefinition;
use Lemonade\Admin\Editor\AdminEditor\Layout\AdminEditorTab;
use Lemonade\Admin\Editor\AdminEditor\Layout\CustomViewBlock;
use Lemonade\Admin\Editor\AdminEditor\Layout\FieldColumn;
use Lemonade\Admin\Editor\AdminEditor\Layout\FieldGroupBlock;
use Lemonade\Admin\Editor\AdminEditor\Layout\SectionBlock;
use Lemonade\Admin\Module\AdminModuleRouteResolver;
use Lemonade\Admin\Presentation\AdminFileUploadCollection;
use Lemonade\Cms\CmsPublicationState;
use Lemonade\Framework\Routing\UrlGenerator;

/**
 * Sklada full-page editor Dražeb se shared taby, fieldy a save barem
 */
final readonly class AuctionAdminEditorDefinitionFactory
{
    /**
     * Nastavuje generator canonical Admin URL
     */
    public function __construct(
        private UrlGenerator $urls,
        private AdminModuleRouteResolver $routes,
    ) {}

    /**
     * Vytvori editor pro jednu content mutation a autorizovany publication panel
     *
     * @param array{id:int|null,translations:list<array<string,mixed>>} $article
     * @param list<string> $tagOptions
     * @param array<int,string> $ownerOptions
     */
    public function create(
        array $article,
        string $contentLocale,
        bool $canPublish,
        bool $canAssignOwner = false,
        array $ownerOptions = [],
        ?AdminEditorHeaderContextDefinition $context = null,
        ?AdminFileUploadCollection $featuredImageUpload = null,
        ?AdminFileUploadCollection $galleryUpload = null,
        ?AdminFileUploadCollection $attachmentUpload = null,
        array $tagOptions = [],
    ): AdminEditorDefinition {
        $articleId = $article['id'];
        $translation = $article['translations'][0] ?? [];
        $builder = AdminEditorBuilder::create('cms.auction')
            ->form($this->editorForm((int) $articleId, $contentLocale))
            ->header($this->header($context))
            ->saveBar(new AdminEditorSaveBarDefinition(
                primaryAction: new AdminEditorActionDefinition(
                    label: '',
                    labelKey: 'admin.common.save_changes',
                    submit: true,
                    primary: true,
                ),
                secondaryAction: new AdminEditorActionDefinition(
                    label: '',
                    labelKey: 'admin.common.discard',
                ),
                titleKey: 'admin.editor.unsaved_changes',
                descriptionKey: 'admin.editor.unsaved_changes_help',
            ));
        $builder
            ->tab($this->contentTab($translation, $contentLocale, $tagOptions))
            ->tab($this->seoTab($translation))
            ->tab($this->settingsTab())
            ->tab($this->mediaTab('gallery', 'admin.file_upload.gallery', 'admin.file_upload.gallery', 'auction.editor.sections.gallery_help', $galleryUpload))
            ->tab($this->mediaTab('attachments', 'admin.file_upload.attachments', 'admin.file_upload.attachments', 'auction.editor.sections.attachments_help', $attachmentUpload));

        $sidebarSections = $this->sidebarSections(
            article: $article,
            canPublish: $canPublish,
            canAssignOwner: $canAssignOwner,
            ownerOptions: $ownerOptions,
            featuredImageUpload: $featuredImageUpload,
        );
        if ($sidebarSections !== []) {
            $builder->sidebar(new AdminEditorSidebarDefinition($sidebarSections));
        }

        return $builder->build();
    }

    /**
     * Sestavi transportni formular pro ulozeni existujiciho Auction draftu
     */
    private function editorForm(int $articleId, string $contentLocale): AdminActionFormDefinition
    {
        $routeParams = ['module' => 'auction', 'id' => $articleId, 'locale' => $contentLocale];

        return new AdminActionFormDefinition(
            id: 'auction-editor-form',
            action: $this->urls->route(name: $this->routes->managementRouteName('auction', 'update'), params: $routeParams),
            actionUrl: $this->urls->route(name: $this->routes->managementRouteName('auction', 'ajax.entity'), params: $routeParams),
            actionKey: 'save',
            novalidate: true,
        );
    }

    /**
     * Sestavi shared hlavicku s breadcrumbem, akcemi a content locale contextem
     */
    private function header(?AdminEditorHeaderContextDefinition $context): AdminEditorHeaderDefinition
    {
        $indexUrl = $this->urls->route(
            name: $this->routes->managementRouteName('auction', 'index'),
            params: ['module' => 'auction'],
        );
        $titleKey = 'auction.editor.title';

        return new AdminEditorHeaderDefinition(
            title: '',
            titleKey: $titleKey,
            breadcrumbs: [
                [
                    'label' => '',
                    'labelKey' => 'auction.module.name',
                    'href' => $indexUrl,
                ],
                [
                    'label' => '',
                    'labelKey' => $titleKey,
                    'current' => true,
                ],
            ],
            actions: [
                new AdminEditorActionDefinition(
                    label: '',
                    labelKey: 'admin.common.back',
                    href: $indexUrl,
                ),
                new AdminEditorActionDefinition(
                    label: '',
                    labelKey: 'admin.common.save',
                    submit: true,
                    primary: true,
                ),
            ],
            context: $context,
        );
    }

    /**
     * Sestavi lokalizovany obsah a rich-text body editoru
     *
     * @param array<string,mixed> $translation
     * @param list<string> $tagOptions
     */
    private function contentTab(array $translation, string $contentLocale, array $tagOptions): AdminEditorTab
    {
        return new AdminEditorTab(
            id: 'content',
            label: '',
            blocks: [
                new SectionBlock(
                    id: 'content-basic',
                    blocks: [
                        Field::hidden('locale')->defaultValue($contentLocale),
                        new FieldGroupBlock([
                            new FieldColumn(
                                Field::text('title', labelKey: 'auction.fields.title')
                                    ->required()
                                    ->attributes(['maxlength' => '180']),
                                md: 12,
                            ),
                            new FieldColumn(
                                Field::text('location', labelKey: 'auction.fields.location')->attributes(['maxlength' => '255']),
                                md: 12,
                            ),
                            new FieldColumn(
                                Field::number('starting_price', labelKey: 'auction.fields.starting_price')->attributes(['min' => '0', 'step' => '0.01']),
                                md: 6,
                            ),
                            new FieldColumn(
                                Field::datetimeLocal('auction_at', labelKey: 'auction.fields.auction_at'),
                                md: 6,
                            ),
                            new FieldColumn(
                                Field::select('auction_state', labelKey: 'auction.fields.auction_state')
                                    ->optionLabelKeys([
                                        'preparing' => 'auction.states.preparing',
                                        'active' => 'auction.states.active',
                                        'ended' => 'auction.states.ended',
                                        'cancelled' => 'auction.states.cancelled',
                                    ])
                                    ->options(['preparing' => '', 'active' => '', 'ended' => '', 'cancelled' => ''])
                                    ->defaultValue('preparing'),
                                md: 6,
                            ),
                            new FieldColumn(
                                Field::text('external_url', labelKey: 'auction.fields.external_url')->attributes(['maxlength' => '2048']),
                                md: 12,
                            ),
                            new FieldColumn(
                                Field::select('tags', labelKey: 'auction.fields.tags')
                                    ->help('', 'auction.fields.tags_help')
                                    ->multiple()
                                    ->options($this->tagOptions($tagOptions, $translation['tags'] ?? []))
                                    ->attributes([
                                        'data-lemonade-searchable' => true,
                                        'data-lemonade-allow-create' => true,
                                    ]),
                                md: 12,
                            ),
                            new FieldColumn(
                                Field::textarea('summary', labelKey: 'auction.fields.summary'),
                                md: 12,
                            ),
                        ]),
                    ],
                    title: '',
                    titleKey: 'auction.editor.sections.localized_content',
                    descriptionKey: 'auction.editor.sections.localized_content_help',
                ),
                new SectionBlock(
                    id: 'content-body',
                    blocks: [
                        new FieldGroupBlock([
                            new FieldColumn(
                                Field::textarea('content', labelKey: 'auction.fields.content')
                                    ->richText(imageUpload: true, fileUpload: true),
                                md: 12,
                            ),
                        ]),
                    ],
                    title: '',
                    titleKey: 'auction.editor.sections.content',
                    descriptionKey: 'auction.editor.sections.content_help',
                ),
            ],
            labelKey: 'auction.editor.tabs.content',
            default: true,
        );
    }

    /**
     * Sestavi lokalizovana SEO metadata editoru
     *
     * @param array<string,mixed> $translation
     */
    private function seoTab(array $translation): AdminEditorTab
    {
        return new AdminEditorTab(
            id: 'seo',
            label: '',
            blocks: [
                new SectionBlock(
                    id: 'seo',
                    blocks: [
                        new FieldGroupBlock([
                            new FieldColumn(
                                Field::text('page_title', labelKey: 'auction.fields.page_title')
                                    ->attributes(['maxlength' => '180'])
                                    ->help('', 'auction.fields.page_title_help'),
                                md: 12,
                            ),
                            new FieldColumn(
                                Field::textarea('meta_description', labelKey: 'auction.fields.meta_description')
                                    ->attributes(['maxlength' => '255'])
                                    ->help('', 'auction.fields.meta_description_help'),
                                md: 12,
                            ),
                            new FieldColumn(
                                Field::urlSegment('url_segment')
                                    ->attributes(['maxlength' => '180'])
                                    ->prefix('/')
                                    ->source(
                                        field: 'title',
                                        autoGenerate: trim((string) ($translation['canonical_path'] ?? '')) === '',
                                    )
                                    ->action('change-url'),
                                md: 12,
                            ),
                        ]),
                    ],
                    title: '',
                    titleKey: 'auction.editor.sections.seo',
                    description: '',
                    descriptionKey: 'auction.editor.sections.seo_help',
                ),
            ],
            labelKey: 'auction.editor.tabs.seo',
        );
    }

    /**
     * Sestavi root nastaveni publikacniho zobrazeni
     *
     */
    private function settingsTab(): AdminEditorTab
    {
        return new AdminEditorTab(
            id: 'settings',
            label: '',
            blocks: [
                ...CmsDisplaySettings::tab()->blocks(),
                new SectionBlock(
                    id: 'authorship',
                    blocks: [new FieldGroupBlock([
                        new FieldColumn(
                            Field::text('author_name', labelKey: 'auction.fields.author_name')->attributes(['maxlength' => '255']),
                            md: 12,
                        ),
                    ])],
                    title: '',
                    titleKey: 'auction.editor.ownership',
                ),
            ],
            labelKey: 'auction.editor.tabs.settings',
        );
    }

    /**
     * Sestavi zatim neimplementovanou content capability jako informativni tab
     */
    private function placeholderTab(string $id, string $labelKey, string $titleKey, string $descriptionKey): AdminEditorTab
    {
        return new AdminEditorTab(
            id: $id,
            label: '',
            blocks: [
                new SectionBlock(
                    id: $id,
                    blocks: [],
                    title: '',
                    titleKey: $titleKey,
                    descriptionKey: $descriptionKey,
                ),
            ],
            labelKey: $labelKey,
        );
    }

    /**
     * Sestavi article-global media tab nad shared Admin upload collection contractem
     */
    private function mediaTab(
        string $id,
        string $labelKey,
        string $titleKey,
        string $descriptionKey,
        ?AdminFileUploadCollection $upload,
    ): AdminEditorTab {
        if ($upload === null) {
            return $this->placeholderTab($id, $labelKey, $titleKey, $descriptionKey);
        }

        return new AdminEditorTab(
            id: $id,
            label: '',
            blocks: [
                new SectionBlock(
                    id: $id,
                    blocks: [new CustomViewBlock('admin::components.file-upload-collection', ['uploadCollection' => $upload])],
                    title: '',
                    titleKey: $titleKey,
                    descriptionKey: $descriptionKey,
                ),
            ],
            labelKey: $labelKey,
        );
    }

    /**
     * Sestavi publication panel a pripraveny shared upload hlavniho obrazku
     *
     * @param array<string,mixed> $article
     * @param array<int,string> $ownerOptions
     * @return list<SectionBlock>
     */
    private function sidebarSections(
        array $article,
        bool $canPublish,
        bool $canAssignOwner,
        array $ownerOptions,
        ?AdminFileUploadCollection $featuredImageUpload,
    ): array {
        $sections = [];
        if ($canPublish) {
            $sections[] = new SectionBlock(
                id: 'publication',
                blocks: [
                    new FieldGroupBlock([
                        new FieldColumn(
                            Field::select('publication_state', labelKey: 'auction.fields.publication_state')
                                ->options(array_fill_keys(array_keys(CmsPublicationState::editorOptionLabelKeys()), ''))
                                ->optionLabelKeys(CmsPublicationState::editorOptionLabelKeys()),
                            md: 12,
                        ),
                        new FieldColumn(
                            Field::datetimeLocal('published_at', labelKey: 'auction.fields.published_at'),
                            md: 12,
                        ),
                    ]),
                ],
                title: '',
                titleKey: 'auction.editor.publication',
                descriptionKey: 'auction.editor.sections.publication_help',
            );
        }
        $metadata = [
            [
                'labelKey' => 'auction.fields.created_by',
                'value' => trim((string) ($article['created_by_name'] ?? '')),
                'detail' => trim((string) ($article['created_at'] ?? '')),
            ],
            [
                'labelKey' => 'auction.fields.updated_by',
                'value' => trim((string) ($article['updated_by_name'] ?? '')),
                'detail' => trim((string) ($article['updated_at'] ?? '')),
            ],
            [
                'labelKey' => 'auction.fields.owner',
                'value' => trim((string) ($article['owner_name'] ?? '')),
                'detail' => null,
                'wide' => true,
                'current' => true,
            ],
        ];
        if (($article['deleted_at'] ?? null) !== null) {
            $metadata[] = [
                'labelKey' => 'auction.fields.deleted_by',
                'value' => trim((string) ($article['deleted_by_name'] ?? '')),
                'detail' => trim((string) ($article['deleted_at'] ?? '')),
                'wide' => true,
            ];
        }
        $ownershipBlocks = [new CustomViewBlock('admin::components.editor-audit-metadata', ['metadata' => $metadata])];
        if ($canAssignOwner) {
            $ownershipBlocks[] = new FieldGroupBlock([new FieldColumn(
                Field::actionSelect('owner_id', 'assign-owner', labelKey: 'auction.actions.assign_owner', actionLabelKey: 'auction.actions.assign_owner_submit')
                    ->options($ownerOptions)
                    ->attributes(['data-lemonade-searchable' => true]),
                md: 12,
            )]);
        }
        $sections[] = new SectionBlock(
            id: 'ownership',
            blocks: $ownershipBlocks,
            title: '',
            titleKey: 'auction.editor.ownership',
            descriptionKey: 'auction.editor.sections.ownership_help',
        );
        if ($featuredImageUpload !== null) {
            $sections[] = new SectionBlock(
                id: 'thumbnail',
                blocks: [
                    new CustomViewBlock(
                        view: 'admin::components.file-upload-collection',
                        context: ['uploadCollection' => $featuredImageUpload],
                    ),
                ],
                title: '',
                titleKey: 'auction.editor.thumbnail.title',
                descriptionKey: 'auction.editor.sections.thumbnail_help',
            );
        }

        return $sections;
    }

    /**
     * Prevede dostupne nazvy stitku na native select options
     *
     * @param list<string> $tagNames
     * @return array<string,string>
     */
    private function tagOptions(array $tagNames, mixed $selectedTags): array
    {
        $options = [];
        if (is_array($selectedTags)) {
            foreach ($selectedTags as $tagName) {
                if (is_string($tagName)) {
                    $options[$tagName] = $tagName;
                }
            }
        }
        foreach ($tagNames as $tagName) {
            $options[$tagName] = $tagName;
        }

        return $options;
    }
}
