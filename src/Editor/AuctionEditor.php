<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Editor;

use DomainException;
use Lemonade\Admin\Authorization\AuthorizationService;
use Lemonade\Admin\Cms\Content\CmsDisplaySettings;
use Lemonade\Admin\Cms\Content\CmsPublicationEditorValues;
use Lemonade\Admin\Editor\Contract\EditorAccessPolicyInterface;
use Lemonade\Admin\Editor\Contract\EditorFieldAccessPolicyInterface;
use Lemonade\Admin\Editor\Contract\EditorProviderInterface;
use Lemonade\Admin\Editor\EditorDefinition;
use Lemonade\Admin\Editor\EditorFieldDefinition;
use Lemonade\Admin\Editor\EditorSaveResult;
use Lemonade\Admin\Editor\Exception\EditorEntityNotFoundException;
use Lemonade\Admin\Editor\Exception\EditorValidationException;
use Lemonade\Admin\Identity\LocalActorGuard;
use Lemonade\Admin\Localization\LanguageRegistry;
use Lemonade\Admin\Module\AdminModuleRouteResolver;
use Lemonade\Cms\Auction\AuctionData;
use Lemonade\Cms\Content\CmsContentInput;
use Lemonade\Cms\Content\CmsContentLifecycle;
use Lemonade\Cms\Content\CmsContentReadModel;
use Lemonade\Cms\Content\CmsContentValidationException;
use Lemonade\Framework\Localization\TranslatorInterface;
use Lemonade\Framework\Routing\UrlGenerator;
use Lemonade\Framework\Validation\ValidationSchema;

/**
 * Connects the Auction editor to the shared content lifecycle
 */
final readonly class AuctionEditor implements
    EditorProviderInterface,
    EditorAccessPolicyInterface,
    EditorFieldAccessPolicyInterface
{
    /**
     * Receives Auction persistence, lifecycle, routing and authorization services
     *
     * @param CmsContentLifecycle<AuctionData> $service
     */
    public function __construct(
        private CmsContentReadModel $auction,
        private CmsContentLifecycle $service,
        private LanguageRegistry $languages,
        private LocalActorGuard $actors,
        private AuctionEditorValidationSchema $validation,
        private AdminModuleRouteResolver $routes,
        private UrlGenerator $urls,
        private AuthorizationService $authorization,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Defines the transport fields accepted by the Auction editor
     */
    public function editorDefinition(): EditorDefinition
    {
        return new EditorDefinition(
            loadPermission: 'cms.auction.view_own',
            savePermission: 'cms.auction.edit_own',
            createPermission: 'cms.auction.create',
            fields: [
                new EditorFieldDefinition(name: 'publication_state', type: 'text', readOnly: false, permission: 'cms.auction.publish_own'),
                new EditorFieldDefinition(name: 'published_at', type: 'text', readOnly: false, permission: 'cms.auction.publish_own'),
                new EditorFieldDefinition(name: 'author_name', type: 'text', readOnly: false, permission: null),
                ...CmsDisplaySettings::fields(),
                new EditorFieldDefinition(name: 'location', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'starting_price', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'auction_at', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'auction_state', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'external_url', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'locale', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'title', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'summary', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'content', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'page_title', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'meta_description', type: 'text', readOnly: false, permission: null),
                new EditorFieldDefinition(name: 'tags', type: 'text', readOnly: false, permission: null),
            ],
        );
    }

    /**
     * Checks global or owner-scoped access to an article
     */
    public function loadAccessDecision(int $id): bool
    {
        return $this->authorization->canForOwner(
            'cms.auction.view',
            'cms.auction.view_own',
            $this->auction->ownerId($id),
        );
    }

    /**
     * Checks global or owner-scoped access to edit an article
     */
    public function saveAccessDecision(int $id): bool
    {
        return $this->authorization->canForOwner(
            'cms.auction.edit',
            'cms.auction.edit_own',
            $this->auction->ownerId($id),
        );
    }

    /**
     * Checks publication fields against the current article owner
     */
    public function fieldAccessDecision(int $id, string $field): ?bool
    {
        if (!in_array($field, ['publication_state', 'published_at'], true)) {
            return null;
        }

        return $this->authorization->canForOwner(
            'cms.auction.publish',
            'cms.auction.publish_own',
            $this->auction->ownerId($id),
        );
    }

    /**
     * Loads an existing Auction article for editing
     */
    public function updateData(int $id): array
    {
        $article = $this->auction->findForEditor($id);
        if ($article === null) {
            throw new EditorEntityNotFoundException('Auction article not found.');
        }

        return ['article' => $article, 'locales' => $this->auction->editorLocales($id)];
    }

    /**
     * Returns the empty editor state required by the shared create contract
     */
    public function createData(): array
    {
        $locales = $this->languages->enabledLocales();

        return [
            'article' => [
                'id' => null,
                'author_name' => null,
                ...\Lemonade\Cms\Content\CmsContentInput::DISPLAY_DEFAULTS,
                'translations' => [],
            ],
            'locales' => $locales,
        ];
    }

    /**
     * Returns validation for creating a draft without form input
     */
    public function createValidationSchema(): ValidationSchema
    {
        return $this->validation->forCreate();
    }

    /**
     * Returns validation for a localized update and root metadata
     */
    public function updateValidationSchema(int $id): ValidationSchema
    {
        return $this->validation->forUpdate();
    }

    /**
     * Saves a localized article mutation through the shared lifecycle
     */
    public function update(int $id, array $data): EditorSaveResult
    {
        try {
            $actorId = $this->actors->requireLocalUser()->id();
            $this->service->save($actorId, $id, CmsContentInput::fromEditor($data), AuctionData::fromEditor($data));
        } catch (CmsContentValidationException $exception) {
            throw new EditorValidationException(
                $exception->getMessage(),
                previous: $exception,
                errors: array_map($this->translator->get(...), $exception->getErrors()),
            );
        } catch (DomainException $exception) {
            throw new EditorValidationException($exception->getMessage(), previous: $exception);
        }

        return new EditorSaveResult(
            data: [
                'id' => $id,
                'editUrl' => $this->editUrl($id, (string) $data['locale']),
                'editorFields' => isset($data['publication_state'])
                    ? CmsPublicationEditorValues::forLocale($this->updateData($id)['article']['translations'], (string) $data['locale'])
                    : [],
            ],
            messageKey: 'auction.editor.updated',
        );
    }

    /**
     * Creates a minimal draft in the configured default content locale
     */
    public function create(array $data): EditorSaveResult
    {
        try {
            $actorId = $this->actors->requireLocalUser()->id();
            $locale = $this->languages->defaultLocale();
            $articleId = $this->service->createDraft($actorId, $locale);
        } catch (CmsContentValidationException $exception) {
            throw new EditorValidationException(
                $exception->getMessage(),
                previous: $exception,
                errors: array_map($this->translator->get(...), $exception->getErrors()),
            );
        } catch (DomainException $exception) {
            throw new EditorValidationException($exception->getMessage(), previous: $exception);
        }

        return new EditorSaveResult(
            data: [
                'id' => $articleId,
                'editUrl' => $this->editUrl($articleId, $locale),
            ],
            messageKey: 'auction.editor.created',
        );
    }

    /**
     * Builds the canonical Admin edit URL for the content locale
     */
    private function editUrl(int $articleId, string $locale): string
    {
        return $this->urls->route(
            $this->routes->managementRouteName('auction', 'edit'),
            ['module' => 'auction', 'id' => $articleId, 'locale' => $locale],
        );
    }
}
