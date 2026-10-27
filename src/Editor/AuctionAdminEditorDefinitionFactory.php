<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Editor;

use Lemonade\Admin\Action\Presentation\AdminActionFormDefinition;
use Lemonade\Admin\Cms\Content\CmsDisplaySettings;
use Lemonade\Admin\Editor\AdminEditor\AdminEditorBuilder;
use Lemonade\Admin\Editor\AdminEditor\AdminEditorDefinition;
use Lemonade\Admin\Editor\AdminEditor\Field\Field;
use Lemonade\Admin\Editor\AdminEditor\Header\AdminEditorHeaderContextDefinition;
use Lemonade\Admin\Editor\AdminEditor\Layout\AdminEditorSidebarDefinition;
use Lemonade\Admin\Editor\AdminEditor\Layout\AdminEditorTab;
use Lemonade\Admin\Editor\AdminEditor\Layout\CmsEditorHeader;
use Lemonade\Admin\Editor\AdminEditor\Layout\CmsEditorSaveBar;
use Lemonade\Admin\Editor\AdminEditor\Layout\CmsMediaSection;
use Lemonade\Admin\Editor\AdminEditor\Layout\CmsOwnershipSection;
use Lemonade\Admin\Editor\AdminEditor\Layout\CmsPublicationSection;
use Lemonade\Admin\Editor\AdminEditor\Layout\CmsTagsField;
use Lemonade\Admin\Editor\AdminEditor\Layout\FieldColumn;
use Lemonade\Admin\Editor\AdminEditor\Layout\FieldGroupBlock;
use Lemonade\Admin\Editor\AdminEditor\Layout\SectionBlock;
use Lemonade\Admin\Editor\AdminEditor\Layout\SeoSection;
use Lemonade\Admin\Module\AdminModuleRouteResolver;
use Lemonade\Admin\Presentation\AdminFileUploadCollection;
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
        $indexUrl = $this->urls->route($this->routes->managementRouteName('auction', 'index'), ['module' => 'auction']);
        $builder = AdminEditorBuilder::create('cms.auction')
            ->form($this->editorForm((int) $articleId, $contentLocale))
            ->header(CmsEditorHeader::create('auction', $indexUrl, $context))
            ->saveBar(CmsEditorSaveBar::create());
        $builder
            ->tab($this->contentTab($translation, $contentLocale, $tagOptions))
            ->tab(SeoSection::tab(pathPrefix: '/', autoGenerateUrl: trim((string) ($translation['canonical_path'] ?? '')) === ''))
            ->tab($this->settingsTab())
            ->tab(CmsMediaSection::gallery('auction', $galleryUpload))
            ->tab(CmsMediaSection::attachments('auction', $attachmentUpload));

        $sidebarSections = [];
        $publication = CmsPublicationSection::create('auction', $canPublish);
        if ($publication !== null) {
            $sidebarSections[] = $publication;
        }
        $sidebarSections[] = CmsOwnershipSection::create('auction', $article, $canAssignOwner, $ownerOptions);
        $thumbnail = CmsMediaSection::thumbnail('auction', $featuredImageUpload);
        if ($thumbnail !== null) {
            $sidebarSections[] = $thumbnail;
        }
        $builder->sidebar(new AdminEditorSidebarDefinition($sidebarSections));

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
                            CmsTagsField::column('auction', $tagOptions, $translation['tags'] ?? []),
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
}
