<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction;

use DateTimeImmutable;
use DomainException;
use Lemonade\Cms\Content\CmsLocalized;
use Lemonade\Cms\Content\CmsRoot;

/**
 * Predstavuje globalni a lokalizovane domenove udaje drazby
 */
final readonly class AuctionData
{
    /**
     * Nastavuje autora, cenu, termin, stav, odkaz a lokalitu
     */
    public function __construct(
        #[CmsRoot('author_name')]
        public ?string $authorName,
        #[CmsRoot('starting_price')]
        public ?string $startingPrice,
        #[CmsRoot('currency')]
        public string $currency,
        #[CmsRoot('auction_at')]
        public ?string $auctionAt,
        #[CmsRoot('auction_state')]
        public string $auctionState,
        #[CmsRoot('external_url')]
        public ?string $externalUrl,
        #[CmsLocalized('location')]
        public ?string $location,
    ) {}

    /**
     * Validuje a normalizuje domenove hodnoty editoru
     *
     * @param array<string,mixed> $data
     */
    public static function fromEditor(array $data): self
    {
        $author = self::nullableText($data['author_name'] ?? null);
        if ($author !== null && mb_strlen($author) > 255) {
            throw new DomainException('auction.validation.author_max_length');
        }
        $price = self::nullableText($data['starting_price'] ?? null);
        if ($price !== null && (mb_strlen($price) > 150
            || preg_match('/^[−-]\s*[0-9]|[\x00-\x1F\x7F]/u', $price) === 1)) {
            throw new DomainException('auction.validation.price_invalid');
        }
        $state = (string) ($data['auction_state'] ?? 'preparing');
        if (!in_array($state, ['preparing', 'active', 'ended', 'cancelled'], true)) {
            throw new DomainException('auction.validation.state_invalid');
        }
        $url = self::nullableText($data['external_url'] ?? null);
        if ($url !== null && (mb_strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true))) {
            throw new DomainException('auction.validation.url_invalid');
        }
        $auctionAt = self::nullableText($data['auction_at'] ?? null);
        if ($auctionAt !== null) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $auctionAt);
            if ($date === false || $date->format('Y-m-d\\TH:i') !== $auctionAt) {
                throw new DomainException('auction.validation.date_invalid');
            }
            $auctionAt = $date->format('Y-m-d H:i:s');
        }
        $location = self::nullableText($data['location'] ?? null);
        if ($location !== null && mb_strlen($location) > 255) {
            throw new DomainException('auction.validation.location_invalid');
        }

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

    /**
     * Prevede prazdny volitelny text na null
     */
    private static function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
