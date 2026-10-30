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
    'systemInfoModTitle'    => 'Sistemos info',
    'logsModTitle'          => 'Žurnalai',
    'log'                   => 'Žurnalas',
    'empty'                 => 'Žurnalų failų nėra.',
    'level'                 => 'Lygmuo',
    'date'                  => 'Data',
    'file'                  => 'Failo pavadinimas',
    'deleteFile'            => 'Trinti žurnalo failą',
    'deleteSelected'        => 'Trinti pažymėtus',
    'deleteAll'             => 'Trinti visus žurnalų failus',
    'deleteSelectedConfirm' => 'Ar tikrai norite ištrinti visus pažymėtus failus?',
    'deleteSuccess'         => 'Failas(-ai) sėkmingai ištrintas(-i).',
    'deleteAllSuccess'      => 'Visi failai buvo sėkmingai ištrinti.',
    'deleteError'           => 'Nepavyksta ištrinti.',
    'noLogsSelected'        => 'Nepasirinkote failų ištrynimui.',
    // system info
    'viewPhpInfo'        => 'Peržiūrėti PHP info',
    'copyAsText'         => 'Kopijuoti kaip tekstą',
    'copied'             => 'Nukopijuota',
    'server'             => 'Serveris',
    'filesystem'         => 'Failų sistema',
    'notWritableWarning' => 'Kai kurie /writable failai ar aplankai neturi rašymo teisių. Bonfire ten turi rašyti žurnalus, podėlį ir įkeltus failus.',
    'present'            => 'Yra',
    'missing'            => 'Trūksta',
    'writable'           => 'Įrašomas',
    'notWritable'        => 'Neįrašomas',
    // logs
    'navigateFiles' => 'Naršymas tarp failų',
    'stackTrace'    => 'Iškvietimų dėklas',
    'levelErrors'   => 'Klaidos',
    'levelWarnings' => 'Įspėjimai',
    'levelInfo'     => 'Informacija',
    'levelDebug'    => 'Derinimas',
    // system info server table
    'phpVersion'    => 'PHP versija',
    'ciVersion'     => 'CodeIgniter versija',
    'sqlEngine'     => 'SQL variklis',
    'serverOs'      => 'Serverio OS',
    'serverLoad'    => 'Serverio apkrova',
    'maxUpload'     => 'Didžiausias įkėlimas',
    'maxPost'       => 'Didžiausias POST',
    'memoryLimit'   => 'Atminties riba',
    'unknown'       => 'Nežinoma',
    'pruneNeverRun' => 'Asmens duomenys dar niekada nebuvo išvalyti. Įsitikinkite, kad <code>php spark tasks:run</code> kas minutę paleidžiamas per cron, kad seni prisijungimai ir sutikimų įrašai būtų ištrinami laiku.',
    'pruneStale'    => 'Asmens duomenys paskutinį kartą išvalyti {0}. Planuoklis galėjo sustoti: įsitikinkite, kad <code>php spark tasks:run</code> kas minutę paleidžiamas per cron.',
];
