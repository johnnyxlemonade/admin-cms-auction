<?php

declare(strict_types=1);

return [
    'module' => ['name' => 'Auctions', 'description' => 'Manage auction and publishing.'],
    'actions' => ['create' => 'Add auction', 'confirm_create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete', 'restore' => 'Restore', 'assign_owner' => 'Transfer ownership', 'assign_owner_submit' => 'Transfer', 'owner_assigned' => 'Auction ownership transferred', 'deleted' => 'Auction item deleted', 'restored' => 'Auction item restored as draft'],
    'states' => ['preparing' => 'Preparing', 'active' => 'Active', 'ended' => 'Ended', 'cancelled' => 'Cancelled'],
    'fields' => ['location' => 'Location', 'starting_price' => 'Starting price (CZK)', 'auction_at' => 'Auction date', 'auction_state' => 'Auction state', 'external_url' => 'External URL', 'title' => 'Auction name', 'author_name' => 'Author', 'tags' => 'Tags', 'tags_help' => 'Select an existing tag or enter a new one.', 'page_title' => 'Page title', 'page_title_help' => 'Recommended length is 50–60 characters. This title appears in search results.', 'summary' => 'Summary', 'content' => 'Content', 'meta_description' => 'Meta description', 'meta_description_help' => 'Recommended length is 120–160 characters. This description appears in search results.', 'thumbnail' => 'Thumbnail', 'publication_state' => 'Publication state', 'published_at' => 'Published from', 'created_by' => 'Created by', 'created_at' => 'Created', 'updated_by' => 'Last updated by', 'updated_at' => 'Updated', 'deleted_by' => 'Deleted by', 'deleted_at' => 'Deleted', 'owner' => 'Owner'],
    'list' => ['title' => 'Auction', 'description' => 'Manage published and draft auction', 'search' => 'Search auction…', 'loading' => 'Loading auction…', 'empty' => 'There is no auction yet', 'active' => 'Active', 'deleted' => 'Deleted', 'untitled_locale' => 'Untitled ({locale})'],
    'filters' => ['state' => 'State', 'all_states' => 'All states', 'locale' => 'Language', 'all_locales' => 'All languages'],
    'editor' => [
        'title' => 'Edit auction',
        'create_title' => 'New auction',
        'content' => 'Content',
        'publication' => 'Publication',
        'ownership' => 'Authorship and ownership',
        'locale' => ['label' => 'Language versions', 'existing_translation' => 'Existing translation', 'new_translation' => 'New translation', 'disabled' => 'Disabled language'],
        'tabs' => ['content' => 'Content', 'seo' => 'SEO', 'settings' => 'Settings'],
        'sections' => ['localized_content' => 'Content', 'localized_content_help' => 'Basic information about the auction item', 'content' => 'Auction content', 'content_help' => 'Auction text and main content', 'seo' => 'SEO settings', 'seo_help' => 'Search engine title, description, and URL settings', 'gallery_help' => 'Images assigned to the record', 'attachments_help' => 'Downloadable files and other documents', 'publication_help' => 'Publication status and publishing date', 'ownership_help' => 'Record history and owner management', 'thumbnail_help' => 'Primary auction preview in listings and links'],
        'not_implemented' => 'Not implemented yet',
        'thumbnail' => ['title' => 'Preview image', 'help' => 'The image is used as the primary preview for this auction item.'],
        'created' => 'Auction item created',
        'updated' => 'Auction item saved',
    ],
    'validation' => ['price_invalid' => 'Enter a valid price with at most two decimal places', 'state_invalid' => 'Invalid auction state', 'url_invalid' => 'Enter a valid HTTP or HTTPS URL', 'date_invalid' => 'Invalid auction date', 'location_invalid' => 'Location may not exceed 255 characters','translation_required' => 'Enter a title in at least one language', 'locale_required' => 'Select a language', 'title_required' => 'Title is required', 'title_max_length' => 'Title may not exceed 180 characters', 'author_max_length' => 'Author may not exceed 255 characters', 'page_title_max_length' => 'Page title may not exceed 180 characters', 'meta_description_max_length' => 'Meta description may not exceed 255 characters', 'tag_max_length' => 'A tag may not exceed 255 characters', 'locale_invalid' => 'The selected language is not enabled', 'publication_invalid' => 'The publication state is invalid', 'owner_invalid' => 'The selected owner does not exist or has been removed', 'not_found' => 'The auction item does not exist', 'save_failed' => 'The auction item could not be saved'],
    'confirm' => ['delete' => 'Do you really want to delete this auction item?', 'restore' => 'Do you really want to restore this auction item as a draft?'],
    'audit' => ['events' => ['created' => 'Auction item created', 'updated' => 'Auction item updated', 'translation_created' => 'Auction language version added', 'translation_updated' => 'Auction language version updated', 'published' => 'Auction item published', 'unpublished' => 'Auction item unpublished', 'deleted' => 'Auction item deleted', 'restored' => 'Auction item restored as draft', 'owner_transferred' => 'Auction ownership transferred']],
    'permissions' => [
        'view' => 'View auction',
        'view_own' => 'View own auction',
        'create' => 'Create auction',
        'edit' => 'Edit auction',
        'edit_own' => 'Edit own auction',
        'publish' => 'Publish auction',
        'publish_own' => 'Publish own auction',
        'delete' => 'Delete auction',
        'delete_own' => 'Delete own auction',
        'restore' => 'Restore auction',
        'restore_own' => 'Restore own auction',
        'assign_owner' => 'Transfer auction ownership',
    ],
];
