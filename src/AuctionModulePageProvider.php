<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction;

use Lemonade\Admin\Action\Presentation\AdminActionFormDefinition;
use Lemonade\Admin\Authorization\AuthorizationService;
use Lemonade\Admin\DataGrid\DataGridIndexViewModel;
use Lemonade\Admin\DataGrid\DataGridPrimaryAction;
use Lemonade\Admin\Editor\AdminEditor\Header\AdminEditorHeaderContextDefinition;
use Lemonade\Admin\Editor\AdminEditor\Header\AdminEditorHeaderContextItem;
use Lemonade\Admin\Editor\AdminEditor\Rendering\AdminEditorRenderContext;
use Lemonade\Admin\Editor\EditorLoaded;
use Lemonade\Admin\Editor\Exception\EditorEntityNotFoundException;
use Lemonade\Admin\Icon\AdminIcon;
use Lemonade\Admin\Identity\LocalUserDirectory;
use Lemonade\Admin\Module\AdminModuleRouteResolver;
use Lemonade\Admin\Page\Contract\ModuleEditorPageProviderInterface;
use Lemonade\Admin\Page\Contract\ModuleIndexPageProviderInterface;
use Lemonade\Admin\Page\ModulePage;
use Lemonade\Admin\Presentation\AdminFileUploadComponent;
use Lemonade\Cms\Auction\DataGrid\AuctionDataGrid;
use Lemonade\Cms\Auction\Editor\AuctionAdminEditorDefinitionFactory;
use Lemonade\Cms\CmsPublicationState;
use Lemonade\Cms\Content\CmsContentReadModel;
use Lemonade\Framework\Localization\TranslatorInterface;
use Lemonade\Framework\Routing\UrlGenerator;

/**
 * Sklada indexovou stranku Auction managementu nad shared DataGrid transportem
 */
final readonly class AuctionModulePageProvider implements ModuleIndexPageProviderInterface, ModuleEditorPageProviderInterface
{
    /**
     * Nastavuje grid, authorization, preklady a URL pro Auction index
     */
    public function __construct(
        private AuctionDataGrid $grid,
        private AuthorizationService $authorization,
        private TranslatorInterface $translator,
        private UrlGenerator $urls,
        private AdminModuleRouteResolver $routes,
        private AuctionAdminEditorDefinitionFactory $adminEditorDefinitions,
        private AdminFileUploadComponent $files,
        private CmsContentReadModel $auction,
        private LocalUserDirectory $users,
    ) {}

    /**
     * Vrati pravo potrebne pro otevreni Auction indexu
     */
    public function indexPermission(): string
    {
        return 'cms.auction.view_own';
    }

    /**
     * Vytvori standardni Auction index s autorizovanou create akci
     */
    public function index(string $locale): ModulePage
    {
        $primaryAction = $this->authorization->hasPermission('cms.auction.create')
            ? DataGridPrimaryAction::action(
                translationKey: 'auction.actions.create',
                icon: AdminIcon::PlusLg,
                form: new AdminActionFormDefinition(
                    id: 'auction-create-draft-form',
                    action: $this->urls->route(
                        name: $this->routes->managementRouteName('auction', 'store'),
                        params: ['module' => 'auction'],
                    ),
                    actionUrl: $this->urls->route(
                        name: $this->routes->managementRouteName('auction', 'ajax.create'),
                        params: ['module' => 'auction'],
                    ),
                    actionKey: 'create',
                    navigateToEditAfterCreate: true,
                ),
            )
            : null;
        $viewModel = new DataGridIndexViewModel(
            moduleCode: 'auction',
            title: $this->translator->get('auction.list.title'),
            description: $this->translator->get('auction.list.description'),
            endpoint: $this->urls->route(
                name: 'admin.api.datagrid.index',
                params: ['module' => 'auction'],
            ),
            definition: $this->grid->dataGridDefinition(),
            primaryAction: $primaryAction,
            loadingText: $this->translator->get('auction.list.loading'),
            emptyText: $this->translator->get('auction.list.empty'),
            errorText: $this->translator->get('admin.datagrid.errorDescription'),
        );

        return new ModulePage(
            view: 'admin::datagrid.index',
            title: $viewModel->title(),
            data: ['dataGrid' => $viewModel, 'locale' => $locale],
        );
    }

    /**
     * Auction nema full-page create presentation; draft se zaklada primary action
     *
     * @param array<string,string> $errors
     * @param array<string,mixed> $input
     * @param array<string,mixed> $query
     */
    public function create(EditorLoaded $editor, array $errors, array $input, string $locale, array $query = []): ModulePage
    {
        throw new EditorEntityNotFoundException('Auction does not provide a full-page create editor.');
    }

    /**
     * Vytvori full-page editor existujici Auction entity
     *
     * @param array<string,string> $errors
     * @param array<string,mixed> $input
     * @param array<string,mixed> $query
     */
    public function editor(EditorLoaded $editor, array $errors, array $input, string $locale, array $query = []): ModulePage
    {
        return $this->editorPage(
            editor: $editor,
            errors: $errors,
            input: $input,
            locale: $locale,
            query: $query,
        );
    }

    /**
     * Pripravi package-owned editor view pro jeden content locale context
     *
     * @param array<string,string> $errors
     * @param array<string,mixed> $input
     * @param array<string,mixed> $query
     */
    private function editorPage(EditorLoaded $editor, array $errors, array $input, string $locale, array $query = []): ModulePage
    {
        $locales = $editor->data()['locales'];
        $selected = $this->resolveContentLocale($locales, $query);
        $article = $editor->data()['article'];
        $article['translations'] = array_values(array_filter(
            $article['translations'],
            static fn(array $translation): bool => $translation['locale'] === $selected['code'],
        ));
        $translation = $article['translations'][0] ?? [];
        $canonicalPath = trim((string) ($translation['canonical_path'] ?? ''), '/');
        $context = $this->localeContext(
            locales: $locales,
            selectedLocale: $selected['code'],
            articleId: $article['id'],
        );
        $ownerId = $article['owner_id'] === null ? null : (int) $article['owner_id'];
        $canAssignOwner = $this->authorization->hasPermission('cms.auction.assign_owner')
            && $this->authorization->canForOwner('cms.auction.view', 'cms.auction.view_own', $ownerId);

        return new ModulePage(
            view: 'admin::editor',
            title: $this->translator->get('auction.editor.title'),
            data: [
                'adminEditor' => $this->adminEditorDefinitions->create(
                    article: $article,
                    contentLocale: $selected['code'],
                    canPublish: $this->authorization->canForOwner(
                        'cms.auction.publish',
                        'cms.auction.publish_own',
                        $ownerId,
                    ),
                    canAssignOwner: $canAssignOwner,
                    ownerOptions: $canAssignOwner ? $this->ownerOptions() : [],
                    context: $context,
                    featuredImageUpload: $this->files->collection(
                        module: 'cms.auction',
                        entityId: (int) $article['id'],
                        usage: 'thumbnail',
                        labelKey: 'auction.fields.thumbnail',
                        helpKey: 'auction.editor.thumbnail.help',
                        alt: (string) ($article['translations'][0]['title'] ?? ''),
                    ),
                    galleryUpload: $this->files->collection('cms.auction', (int) $article['id'], 'gallery', 'admin.file_upload.gallery', 'admin.file_upload.gallery_help'),
                    attachmentUpload: $this->files->collection('cms.auction', (int) $article['id'], 'attachment', 'admin.file_upload.attachments', 'admin.file_upload.attachments_help'),
                    tagOptions: $this->auction->tagNamesForLocale($selected['code']),
                ),
                'adminEditorContext' => new AdminEditorRenderContext(
                    values: [
                        'title' => (string) ($translation['title'] ?? ''),
                        'location' => (string) ($translation['location'] ?? ''),
                        'starting_price' => (string) ($article['starting_price'] ?? ''),
                        'auction_at' => str_replace(' ', 'T', substr((string) ($article['auction_at'] ?? ''), 0, 16)),
                        'auction_state' => (string) ($article['auction_state'] ?? 'preparing'),
                        'external_url' => (string) ($article['external_url'] ?? ''),
                        'author_name' => (string) ($article['author_name'] ?? ''),
                        'tags' => $translation['tags'] ?? [],
                        'summary' => (string) ($translation['summary'] ?? ''),
                        'content' => (string) ($translation['content'] ?? ''),
                        'page_title' => (string) ($translation['page_title'] ?? ''),
                        'meta_description' => (string) ($translation['meta_description'] ?? ''),
                        'url_segment' => $canonicalPath === '' ? '' : basename($canonicalPath),
                        ...\Lemonade\Admin\Cms\Content\CmsDisplaySettings::values($article),
                        'publication_state' => CmsPublicationState::from((int) ($translation['publication_state'] ?? CmsPublicationState::New->value))->editorValue(),
                        'published_at' => (string) ($translation['published_at'] ?? ''),
                        'created_by_name' => (string) ($article['created_by_name'] ?? '—'),
                        'created_at' => (string) ($article['created_at'] ?? ''),
                        'updated_by_name' => (string) ($article['updated_by_name'] ?? '—'),
                        'updated_at' => (string) ($article['updated_at'] ?? ''),
                        'owner_name' => (string) ($article['owner_name'] ?? '—'),
                        'owner_id' => $ownerId === null ? '' : (string) $ownerId,
                        'deleted_by_name' => (string) ($article['deleted_by_name'] ?? '—'),
                        'deleted_at' => (string) ($article['deleted_at'] ?? ''),
                    ],
                    oldInput: $input,
                    errors: $errors,
                    mode: 'edit',
                    moduleCode: 'cms.auction',
                    entityId: (int) $article['id'],
                ),
                'locale' => $locale,
            ],
        );
    }

    /**
     * Sestavi options nesmazanych lokalnich uzivatelu pro ownership transfer
     *
     * @return array<int,string>
     */
    private function ownerOptions(): array
    {
        $options = [];
        foreach ($this->users->assignableUsers() as $user) {
            $options[$user['id']] = $user['label'];
        }

        return $options;
    }

    /**
     * Vybere povoleny content locale podle explicitniho query contextu
     *
     * @param list<array<string,mixed>> $locales
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    private function resolveContentLocale(array $locales, array $query): array
    {
        $requested = strtolower(trim((string) ($query['locale'] ?? '')));
        if ($requested !== '') {
            foreach ($locales as $locale) {
                if ($locale['code'] === $requested) {
                    return $locale;
                }
            }

            throw new EditorEntityNotFoundException('Auction content locale is not available.');
        }

        foreach ($locales as $locale) {
            if ((int) ($locale['is_default'] ?? 0) === 1) {
                return $locale;
            }
        }

        return $locales[0] ?? throw new EditorEntityNotFoundException('Auction content locale is not available.');
    }

    /**
     * Vytvori linkove prepinani dostupnych content locales editoru
     *
     * @param list<array<string,mixed>> $locales
     */
    private function localeContext(array $locales, string $selectedLocale, ?int $articleId): AdminEditorHeaderContextDefinition
    {
        $items = [];
        foreach ($locales as $locale) {
            $code = (string) $locale['code'];
            $enabled = (int) ($locale['enabled'] ?? 1) === 1;
            $hasTranslation = (int) ($locale['has_translation'] ?? 0) === 1;
            $items[] = new AdminEditorHeaderContextItem(
                label: (string) $locale['name'],
                href: $this->urls->route(
                    name: $this->routes->managementRouteName('auction', 'edit'),
                    params: ['module' => 'auction', 'id' => $articleId, 'locale' => $code],
                ),
                active: $code === $selectedLocale,
                secondary: !$enabled
                    ? $this->translator->get('auction.editor.locale.disabled')
                    : ($hasTranslation
                        ? $this->translator->get('auction.editor.locale.existing_translation')
                        : $this->translator->get('auction.editor.locale.new_translation')),
                code: $code,
                translationExists: $hasTranslation,
            );
        }

        return new AdminEditorHeaderContextDefinition(
            label: $this->translator->get('auction.editor.locale.label'),
            items: $items,
        );
    }
}
