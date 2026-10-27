<?php

declare(strict_types=1);

return [
    'module' => ['name' => 'Dražby', 'description' => 'Správa dražeb a jejich publikace.'],
    'actions' => ['create' => 'Přidat dražebu', 'confirm_create' => 'Vytvořit', 'edit' => 'Upravit', 'delete' => 'Smazat', 'restore' => 'Obnovit', 'assign_owner' => 'Předat vlastnictví', 'assign_owner_submit' => 'Předat', 'owner_assigned' => 'Vlastnictví dražby bylo předáno', 'deleted' => 'Dražeba byla smazána', 'restored' => 'Dražeba byla obnovena jako koncept'],
    'states' => ['preparing' => 'Připravovaná', 'active' => 'Aktivní', 'ended' => 'Ukončená', 'cancelled' => 'Zrušená'],
    'fields' => ['location' => 'Lokalita', 'starting_price' => 'Vyvolávací cena (CZK)', 'auction_at' => 'Termín dražby', 'auction_state' => 'Stav dražby', 'external_url' => 'Externí odkaz', 'title' => 'Název dražby', 'author_name' => 'Autor', 'tags' => 'Štítky', 'tags_help' => 'Vyberte existující štítek nebo zadejte nový.', 'page_title' => 'Název na stránce', 'page_title_help' => 'Doporučená délka je 50–60 znaků. Tento název se zobrazí ve výsledcích vyhledávání.', 'summary' => 'Perex', 'content' => 'Obsah', 'meta_description' => 'Meta description', 'meta_description_help' => 'Doporučená délka je 120–160 znaků. Tento popis se zobrazí ve výsledcích vyhledávání.', 'thumbnail' => 'Hlavní obrázek', 'publication_state' => 'Stav publikace', 'published_at' => 'Publikováno od', 'created_by' => 'Vytvořil', 'created_at' => 'Vytvořeno', 'updated_by' => 'Naposledy upravil', 'updated_at' => 'Aktualizováno', 'deleted_by' => 'Smazal', 'deleted_at' => 'Smazáno', 'owner' => 'Vlastník'],
    'list' => ['title' => 'Dražby', 'description' => 'Správa publikovaných a rozpracovaných dražeb', 'search' => 'Hledat v dražbách…', 'loading' => 'Načítání dražeb…', 'empty' => 'Zatím zde nejsou žádné dražby', 'active' => 'Aktivní', 'deleted' => 'Smazané', 'untitled_locale' => 'Bez názvu ({locale})'],
    'filters' => ['state' => 'Stav', 'all_states' => 'Všechny stavy', 'locale' => 'Jazyk', 'all_locales' => 'Všechny jazyky'],
    'editor' => [
        'title' => 'Upravit dražbu',
        'create_title' => 'Nová dražba',
        'content' => 'Obsah',
        'publication' => 'Publikace',
        'ownership' => 'Autorství a vlastnictví',
        'locale' => ['label' => 'Jazykové verze', 'existing_translation' => 'Existující překlad', 'new_translation' => 'Nový překlad', 'disabled' => 'Neaktivní jazyk'],
        'tabs' => ['content' => 'Obsah', 'seo' => 'SEO', 'settings' => 'Nastavení'],
        'sections' => ['localized_content' => 'Obsah', 'localized_content_help' => 'Základní informace o dražbě', 'content' => 'Obsah dražby', 'content_help' => 'Text a hlavní obsah dražby', 'seo' => 'SEO nastavení', 'seo_help' => 'Nastavení názvu, popisu a adresy pro vyhledávače', 'gallery_help' => 'Obrázky přiřazené k záznamu', 'attachments_help' => 'Soubory ke stažení a další dokumenty', 'publication_help' => 'Stav zveřejnění a datum publikace', 'ownership_help' => 'Historie záznamu a správa vlastníka', 'thumbnail_help' => 'Hlavní náhled dražby v přehledech a odkazech'],
        'not_implemented' => 'Zatím neimplementováno',
        'thumbnail' => ['title' => 'Náhledový obrázek', 'help' => 'Obrázek se použije jako hlavní náhled dražby.'],
        'created' => 'Dražeba byla vytvořena',
        'updated' => 'Dražeba byla uložena',
    ],
    'validation' => ['price_invalid' => 'Zadejte platnou cenu s nejvýše dvěma desetinnými místy', 'state_invalid' => 'Stav dražby není platný', 'url_invalid' => 'Zadejte platný odkaz HTTP nebo HTTPS', 'date_invalid' => 'Termín dražby není platný', 'location_invalid' => 'Lokalita může mít nejvýše 255 znaků','translation_required' => 'Vyplňte nadpis alespoň v jednom jazyce', 'locale_required' => 'Vyberte jazyk', 'title_required' => 'Nadpis je povinný', 'title_max_length' => 'Nadpis může mít nejvýše 180 znaků', 'author_max_length' => 'Autor může mít nejvýše 255 znaků', 'page_title_max_length' => 'Název na stránce může mít nejvýše 180 znaků', 'meta_description_max_length' => 'Meta description může mít nejvýše 255 znaků', 'tag_max_length' => 'Štítek může mít nejvýše 255 znaků', 'locale_invalid' => 'Zvolený jazyk není aktivní', 'publication_invalid' => 'Stav publikace není platný', 'owner_invalid' => 'Vybraný vlastník neexistuje nebo byl odstraněn', 'not_found' => 'Dražeba neexistuje', 'save_failed' => 'Dražebu se nepodařilo uložit'],
    'confirm' => ['delete' => 'Opravdu chcete tuto dražebu smazat?', 'restore' => 'Opravdu chcete tuto dražebu obnovit jako koncept?'],
    'audit' => ['events' => ['created' => 'Vytvořena dražeba', 'updated' => 'Upravena dražeba', 'translation_created' => 'Přidána jazyková mutace dražby', 'translation_updated' => 'Upravena jazyková mutace dražby', 'published' => 'Publikována dražeba', 'unpublished' => 'Zrušeno publikování dražby', 'deleted' => 'Smazána dražeba', 'restored' => 'Obnovena dražeba jako koncept', 'owner_transferred' => 'Předáno vlastnictví dražby']],
    'permissions' => [
        'view' => 'Zobrazit dražby',
        'view_own' => 'Zobrazit vlastní dražby',
        'create' => 'Vytvářet dražby',
        'edit' => 'Upravovat dražby',
        'edit_own' => 'Upravovat vlastní dražby',
        'publish' => 'Publikovat dražby',
        'publish_own' => 'Publikovat vlastní dražby',
        'delete' => 'Mazat dražby',
        'delete_own' => 'Mazat vlastní dražby',
        'restore' => 'Obnovovat dražby',
        'restore_own' => 'Obnovovat vlastní dražby',
        'assign_owner' => 'Předávat vlastnictví dražeb',
    ],
];
