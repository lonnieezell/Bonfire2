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
    'systemInfoModTitle'    => 'Informazioni di sistema',
    'logsModTitle'          => 'Registri',
    'log'                   => 'Registro',
    'empty'                 => 'Nessun registro trovato.',
    'level'                 => 'Livello',
    'date'                  => 'Data',
    'file'                  => 'Nome del file',
    'deleteFile'            => 'Elimina il file di registro',
    'deleteSelected'        => 'Elimina la selezione',
    'deleteAll'             => 'Elimina tutti i registri',
    'deleteSelectedConfirm' => 'Sei sicuro di voler eliminare tutti i file selezionati?',
    'deleteSuccess'         => 'File eliminati con successo.',
    'deleteAllSuccess'      => 'Tutti i file sono stati eliminati con successo.',
    'deleteError'           => 'Impossibile eseguire l\'eliminazione.',
    'noLogsSelected'        => 'Nessun registro è stato selezionato per l\'eliminazione.',
    // system info
    'viewPhpInfo'        => 'Visualizza PHP Info',
    'copyAsText'         => 'Copia come testo',
    'copied'             => 'Copiato',
    'server'             => 'Server',
    'filesystem'         => 'Filesystem',
    'notWritableWarning' => 'Alcuni file o cartelle in /writable non sono scrivibili. Bonfire deve scrivervi log, cache e file caricati.',
    'present'            => 'Presente',
    'missing'            => 'Mancante',
    'writable'           => 'Scrivibile',
    'notWritable'        => 'Non scrivibile',
    // logs
    'navigateFiles' => 'Navigazione tra i file',
    'stackTrace'    => 'Stack trace',
    'levelErrors'   => 'Errori',
    'levelWarnings' => 'Avvisi',
    'levelInfo'     => 'Info',
    'levelDebug'    => 'Debug',
    // system info server table
    'phpVersion'    => 'Versione PHP',
    'ciVersion'     => 'Versione CodeIgniter',
    'sqlEngine'     => 'Motore SQL',
    'serverOs'      => 'Sistema operativo',
    'serverLoad'    => 'Carico del server',
    'maxUpload'     => 'Upload massimo',
    'maxPost'       => 'POST massimo',
    'memoryLimit'   => 'Limite di memoria',
    'unknown'       => 'Sconosciuto',
    'pruneNeverRun' => 'I dati personali non sono mai stati eliminati. Assicurati che <code>php spark tasks:run</code> venga eseguito ogni minuto da cron, così i vecchi accessi e i consensi vengono eliminati regolarmente.',
    'pruneStale'    => 'I dati personali sono stati eliminati l\'ultima volta {0}. Lo scheduler potrebbe essersi fermato: assicurati che <code>php spark tasks:run</code> venga eseguito ogni minuto da cron.',
];
