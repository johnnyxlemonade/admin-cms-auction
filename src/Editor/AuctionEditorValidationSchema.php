<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Editor;

use Lemonade\Admin\Cms\Content\CmsDisplaySettings;
use Lemonade\Cms\CmsPublicationState;
use Lemonade\Framework\Localization\TranslatorInterface;
use Lemonade\Framework\Validation\ValidationSchema;

/**
 * Sklada frameworkovou validaci create a update vstupu editoru Dražeb
 */
final readonly class AuctionEditorValidationSchema
{
    /**
     * Nastavuje prekladovy zdroj chyb editorove validace
     */
    public function __construct(private TranslatorInterface $translator) {}

    /**
     * Ponechava create bez formulovych hodnot pro okamzite zalozeni draftu
     */
    public function forCreate(): ValidationSchema
    {
        return ValidationSchema::create();
    }

    /**
     * Vymezuje flat payload jedne lokalizovane mutation a globalnich metadat
     */
    public function forUpdate(): ValidationSchema
    {
        return CmsDisplaySettings::validation(ValidationSchema::create()
            ->field('locale', $this->translator->get('auction.editor.content'))
                ->required($this->translator->get('auction.validation.locale_required'))
            ->field('title', $this->translator->get('auction.fields.title'))
                ->required($this->translator->get('auction.validation.title_required'))
                ->maxLength(180, $this->translator->get('auction.validation.title_max_length'))
            ->field('author_name', $this->translator->get('auction.fields.author_name'))
                ->maxLength(255, $this->translator->get('auction.validation.author_max_length'))
            ->field('page_title', $this->translator->get('admin.seo.page_title'))
                ->maxLength(180, $this->translator->get('auction.validation.page_title_max_length'))
            ->field('meta_description', $this->translator->get('admin.seo.meta_description'))
                ->maxLength(255, $this->translator->get('auction.validation.meta_description_max_length'))
            ->field('publication_state', $this->translator->get('auction.fields.publication_state'))
                ->inList(array_keys(CmsPublicationState::editorOptionLabelKeys()), $this->translator->get('auction.validation.publication_invalid'))
            ->field('published_at', $this->translator->get('auction.fields.published_at'))
            ->field('location', $this->translator->get('auction.fields.location'))
            ->field('starting_price', $this->translator->get('auction.fields.starting_price'))
            ->field('auction_at', $this->translator->get('auction.fields.auction_at'))
            ->field('auction_state', $this->translator->get('auction.fields.auction_state'))
            ->field('external_url', $this->translator->get('auction.fields.external_url'))
            ->field('summary', $this->translator->get('auction.fields.summary'))
            ->field('content', $this->translator->get('auction.fields.content'))
            ->field('tags', $this->translator->get('auction.fields.tags'))
            ->end(), $this->translator);
    }
}
