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
    'modTitle' => 'Turinys',
    'entry'    => 'įrašas',

    // List
    'newEntry'        => 'Naujas įrašas',
    'colTitle'        => 'Pavadinimas',
    'colStatus'       => 'Būsena',
    'colCollection'   => 'Rinkinys',
    'colDate'         => 'Data',
    'statusDraft'     => 'Juodraštis',
    'statusPublished' => 'Paskelbtas',
    'listEmpty'       => 'Įrašų dar nėra.',
    'listEmptyHint'   => 'Sukurkite pirmąjį. Jei failai jau yra, paleiskite php spark content:reindex.',

    // Form
    'editEntry'       => 'Redaguoti įrašą',
    'saveEntry'       => 'Išsaugoti įrašą',
    'publishing'      => 'Publikavimas',
    'status'          => 'Būsena',
    'publishedAt'     => 'Publikavimo data',
    'publishedAtHint' => 'Kada įrašas bus paskelbtas. Palikite lauką tuščią, jei norite jį paskelbti nedelsiant.',
    'slugLocked'      => 'Nuorodos fragmentas sudaro įrašo failo pavadinimą, todėl jo pakeisti negalima.',
    'slugHint'        => 'Sugeneruojamas iš pavadinimo, jei paliktas tuščias.',
    'reload'          => 'Įkelti įrašą iš naujo',
    'write'           => 'Rašyti',
    'preview'         => 'Peržiūra',
    'previewEmpty'    => 'Nėra ką peržiūrėti.',

    // Trash
    'recycler' => [
        'label'   => 'Turinio įrašai',
        'columns' => [
            'title'      => 'Pavadinimas',
            'collection' => 'Rinkinys',
            'slug'       => 'Nuorodos fragmentas',
        ],
    ],
    'deleteConfirm'      => 'Perkelti šį įrašą į šiukšliadėžę?',
    'deleteConfirmBody'  => 'Galėsite jį atkurti iš šiukšliadėžės.',
    'deleteConfirmBelow' => '{0, plural, one {Kartu bus ištrintas ir # po juo esantis puslapis.} few {Kartu bus ištrinti ir # po juo esantys puslapiai.} many {Kartu bus ištrinta ir # po juo esančių puslapių.} other {Kartu bus ištrinta ir # po juo esančių puslapių.}} Galėsite atkurti iš šiukšliadėžės.',
    'trashed'            => 'Įrašas perkeltas į šiukšliadėžę. Galėsite jį atkurti iš šiukšliadėžės.',
    'inTrash'            => 'Šiukšliadėžėje',
    'showTrashed'        => 'Rodyti ištrintus ({0})',

    // Structure
    'structure'         => 'puslapių medis',
    'editStructure'     => 'Redaguoti struktūrą',
    'saveStructure'     => 'Išsaugoti struktūrą',
    'structureHint'     => 'Vilkite puslapį, kad pakeistumėte eilę, arba numeskite jį ant kito puslapio, kad įdėtumėte į jį. Puslapio URL nustatomas pagal jo tėvus.',
    'structureHandle'   => 'Vilkite, kad perkeltumėte',
    'structureReload'   => 'Įkelti struktūrą iš naujo',
    'structureEmpty'    => 'Kol kas nėra įrašų, kuriuos būtų galima išdėstyti.',
    'structureConflict' => 'Medžio failas buvo pakeistas po to, kai jį atidarėte, todėl jūsų pakeitimai nebuvo išsaugoti. Įkelkite struktūrą iš naujo, kad pamatytumėte kitą pakeitimą, tada pakartokite savo pakeitimus.',

    // Navigation
    'navigation'              => 'meniu',
    'saveNavigation'          => 'Išsaugoti meniu',
    'navigationHint'          => 'Pridėkite įrašų ir nuorodų, tada vilkite elementą, kad pakeistumėte eilę, arba numeskite jį ant kito elemento, kad įdėtumėte į jį.',
    'navigationAddEntry'      => 'Pridėti įrašą',
    'navigationAddLink'       => 'Pridėti nuorodą',
    'navigationLinkTitle'     => 'Pavadinimas',
    'navigationLinkUrl'       => 'URL',
    'navigationTitleOverride' => 'Pavadinimas meniu',
    'navigationRemove'        => 'Pašalinti iš meniu',
    'navigationLinkUrlHint'   => 'Pradėkite nuo /, #, ?, http:, https:, mailto: arba tel:.',
    'navigationReload'        => 'Atidaryti paskutinį išsaugotą meniu',
    'navigationMissing'       => 'Įrašas nerastas',
    'navigationScheduled'     => 'Suplanuotas',
    'navigationNoUri'         => 'Nėra URL',
    'navigationTrashed'       => 'Šiukšliadėžėje',

    // Field types
    'entriesInvalid' => 'Pasirinkite egzistuojančius įrašus iš rinkinių, kuriuos leidžia šis laukas.',
    'entriesTooMany' => 'Pasirinkite ne daugiau kaip {0}.',
    'entriesLimit'   => 'Pasirinkite iki {0}.',
    'entriesNone'    => 'Nėra įrašų, iš kurių būtų galima rinktis.',

    // Blocks field
    'blocksTooMany'    => 'Pridėkite ne daugiau kaip {0} blokų.',
    'blocksTooFew'     => 'Pridėkite bent {0} blokų.',
    'blockTypeInvalid' => 'Bloko „{0}“ šis laukas neleidžia.',
    'blockError'       => 'Blokas {0} ({1}): {2}',
    'blocksNone'       => 'Blokų dar nėra.',
    'blocksHint'       => 'Blokai rašomi įrašo faile. Išsaugojus čia rodomos reikšmės išlieka.',

    // Terms field
    'noTerms'     => 'Terminų dar nėra.',
    'unknownTerm' => '{0} (tokio termino nėra)',

    // Taxonomies
    'term'           => 'terminas',
    'newTerm'        => 'Naujas terminas',
    'editTerm'       => 'Redaguoti terminą',
    'saveTerm'       => 'Išsaugoti terminą',
    'colSlug'        => 'Nuorodos fragmentas',
    'colEntries'     => 'Įrašai',
    'termsEmptyHint' => 'Sukurkite pirmąjį. Jei failai jau yra, paleiskite php spark content:reindex.',
    'termNoSlug'     => 'Iš pavadinimo nepavyko sudaryti nuorodos fragmento. Įrašykite jį į nuorodos fragmento lauką arba suteikite terminui pavadinimą su raidėmis arba skaičiais.',

    // Refusals
    'noBlueprint'        => 'Šis rinkinys neturi šablono (blueprint), kurį galėtų naudoti jo įrašai.',
    'cannotPublish'      => 'Neturite teisės publikuoti įrašų arba keisti jų publikavimo laiko.',
    'noSlug'             => 'Iš pavadinimo nepavyko sudaryti nuorodos fragmento. Įrašykite jį į nuorodos fragmento lauką arba suteikite įrašui pavadinimą su raidėmis arba skaičiais.',
    'datedNeedsDate'     => 'Datuojamo rinkinio įrašui reikia publikavimo datos.',
    'invalidStatus'      => 'Būsena turi būti juodraštis arba paskelbta.',
    'invalidPublishedAt' => 'Publikavimo data nėra tinkama data ir laikas.',
    'conflict'           => 'Šis įrašas buvo pakeistas po to, kai jį atidarėte, todėl jūsų pakeitimai nebuvo išsaugoti. Įkelkite įrašą iš naujo, kad pamatytumėte kitą pakeitimą, tada pakartokite savo pakeitimus.',
];
