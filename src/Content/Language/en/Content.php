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

    // Structure
    'structure'         => 'structure',
    'editStructure'     => 'Edit structure',
    'saveStructure'     => 'Save structure',
    'structureHint'     => 'Drag a page to reorder it, or onto another page to nest it. The URL of a page follows its parents.',
    'structureHandle'   => 'Drag to move',
    'structureReload'   => 'Reload the structure',
    'structureEmpty'    => 'There are no entries to arrange yet.',
    'structureConflict' => 'The tree file was changed after you opened it, so your changes were not saved. Reload the structure to see the other change, then make yours again.',

    // Field types
    'selectInvalid'  => 'Choose one of the options.',
    'integerInvalid' => 'Enter a whole number.',
    'integerMin'     => 'Enter a number that is at least {0}.',
    'integerMax'     => 'Enter a number that is at most {0}.',
    'entriesInvalid' => 'Choose entries that exist, from the collections this field allows.',
    'entriesTooMany' => 'Choose no more than {0}.',
    'entriesLimit'   => 'Choose up to {0}.',
    'entriesNone'    => 'There are no entries to choose from.',

    // Terms field
    'noTerms'     => 'No terms yet.',
    'unknownTerm' => '{0} (no such term)',

    // Taxonomies
    'term'           => 'term',
    'newTerm'        => 'New term',
    'editTerm'       => 'Edit term',
    'saveTerm'       => 'Save term',
    'colSlug'        => 'Slug',
    'colEntries'     => 'Entries',
    'termsEmptyHint' => 'Create the first one. If the files are already there, run php spark content:reindex.',
    'termNoSlug'     => 'A slug could not be made from the title. Type one in the slug field, or give the term a title with letters or numbers in it.',

    // Refusals
    'noBlueprint'        => 'This collection has no blueprint for its entries to use.',
    'cannotPublish'      => 'You don\'t have permission to publish entries or to change when they go live.',
    'noSlug'             => 'A slug could not be made from the title. Type one in the slug field, or give the entry a title with letters or numbers in it.',
    'datedNeedsDate'     => 'An entry in a dated collection needs a publish date.',
    'invalidStatus'      => 'The status must be draft or published.',
    'invalidPublishedAt' => 'The publish date is not a valid date and time.',
    'conflict'           => 'This entry was changed after you opened it, so your changes were not saved. Reload the entry to see the other change, then make yours again.',
];
