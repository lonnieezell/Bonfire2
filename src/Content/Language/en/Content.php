<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

return [
    'modTitle' => 'Content',
    'entry'    => 'entry',

    // List
    'newEntry'        => 'New entry',
    'colTitle'        => 'Title',
    'colStatus'       => 'Status',
    'colDate'         => 'Date',
    'statusDraft'     => 'Draft',
    'statusPublished' => 'Published',
    'listEmpty'       => 'No entries yet.',
    'listEmptyHint'   => 'Create the first one. If the files are already there, run php spark content:reindex.',

    // Form
    'editEntry'       => 'Edit entry',
    'saveEntry'       => 'Save entry',
    'backToList'      => 'Back to the list',
    'publishing'      => 'Publishing',
    'status'          => 'Status',
    'publishedAt'     => 'Publish date',
    'publishedAtHint' => 'When the entry goes live. Leave it empty to publish it as soon as it is published.',
    'slugLocked'      => 'The slug names the entry\'s file, so it can\'t change.',
    'slugHint'        => 'Made from the title when left empty.',
    'reload'          => 'Reload the entry',
    'write'           => 'Write',
    'preview'         => 'Preview',
    'previewEmpty'    => 'Nothing to preview.',
    'on'              => 'On',
    'off'             => 'Off',

    // Refusals
    'noBlueprint'        => 'This collection has no blueprint for its entries to use.',
    'cannotPublish'      => 'You don\'t have permission to publish entries or to change when they go live.',
    'noSlug'             => 'A slug could not be made from the title. Type one in the slug field, or give the entry a title with letters or numbers in it.',
    'invalidStatus'      => 'The status must be draft or published.',
    'invalidPublishedAt' => 'The publish date is not a valid date and time.',
    'conflict'           => 'This entry was changed after you opened it, so your changes were not saved. Reload the entry to see the other change, then make yours again.',
];
