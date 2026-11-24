<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction;

use Lemonade\Cms\Content\CmsContentValidation;
use Lemonade\Cms\Content\CmsLocalized;
use Lemonade\Cms\Content\CmsRoot;

/**
 * Represents root and localized domain data of one auction
 */
final readonly class AuctionData
{
    /**
     * Creates the auction data stored by the shared CMS lifecycle
     */
    public function __construct(
        #[CmsRoot('author_name')]
        private ?string $authorName,
        #[CmsRoot('starting_price')]
        private ?string $startingPrice,
        #[CmsRoot('currency')]
        private string $currency,
        #[CmsRoot('auction_at')]
        private ?string $auctionAt,
        #[CmsRoot('auction_state')]
        private string $auctionState,
        #[CmsRoot('external_url')]
        private ?string $externalUrl,
        #[CmsLocalized('location')]
        private ?string $location,
    ) {}

    /**
     * Returns the optional author name
     */
    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    /**
     * Returns the stored starting price text
     */
    public function getStartingPrice(): ?string
    {
        return $this->startingPrice;
    }

    /**
     * Returns the stored currency code
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Returns the stored auction date and time
     */
    public function getAuctionAt(): ?string
    {
        return $this->auctionAt;
    }

    /**
     * Returns the auction business state
     */
    public function getAuctionState(): string
    {
        return $this->auctionState;
    }

    /**
     * Returns the optional external auction URL
     */
    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    /**
     * Returns the localized auction location
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Validates and normalizes domain values submitted by the editor
     *
     * @param array<string,mixed> $data
     */
    public static function fromEditor(array $data): self
    {
        $data = CmsContentValidation::texts($data, ['author_name', 'starting_price', 'auction_at', 'auction_state', 'external_url', 'location']);
        $errors = [];
        $author = $data['author_name'];
        if ($author !== null && mb_strlen($author) > 255) {
            $errors['author_name'] = 'auction.validation.author_max_length';
        }
        $price = $data['starting_price'];
        if ($price !== null && (mb_strlen($price) > 150
            || preg_match('/^[−-]\s*[0-9]|[\x00-\x1F\x7F]/u', $price) === 1)) {
            $errors['starting_price'] = 'auction.validation.price_invalid';
        }
        $state = (string) ($data['auction_state'] ?? 'preparing');
        if (!in_array($state, ['preparing', 'active', 'ended', 'cancelled'], true)) {
            $errors['auction_state'] = 'auction.validation.state_invalid';
        }
        $url = $data['external_url'];
        if ($url !== null && (mb_strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true))) {
            $errors['external_url'] = 'auction.validation.url_invalid';
        }
        $auctionAt = $data['auction_at'];
        if ($auctionAt !== null) {
            if (!CmsContentValidation::validDate($auctionAt)) {
                $errors['auction_at'] = 'auction.validation.date_invalid';
            } else {
                $auctionAt = str_replace('T', ' ', $auctionAt) . ':00';
            }
        }
        $location = $data['location'];
        if ($location !== null && mb_strlen($location) > 255) {
            $errors['location'] = 'auction.validation.location_invalid';
        }

        CmsContentValidation::assert($errors);

        return new self(
            authorName: $author,
            startingPrice: $price,
            currency: 'CZK',
            auctionAt: $auctionAt,
            auctionState: $state,
            externalUrl: $url,
            location: $location,
        );
    }
}
