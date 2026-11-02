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
    'emailModTitle' => 'Email',
    'from'          => 'From',
    'fromDesc'      => 'The default name and address emails are sent from.',
    'fromName'      => 'Name',
    'fromEmail'     => 'Email',

    'mailers'                 => 'Mailers',
    'mailersDesc'             => 'Mail is configured in app/Config/Mailer.php and .env, not here.',
    'mailersDocs'             => 'Read the Postal docs',
    'mailerDefault'           => 'Default',
    'mailerBridged'           => 'Deprecated',
    'mailerChain'             => 'Tries, in order',
    'mailerChainMissing'      => 'not defined',
    'mailerNoSettings'        => 'No settings.',
    'mailerUnknownTransport'  => 'This transport is not registered in Config\Mailer::$transports, so this mailer cannot send.',
    'mailerBrokenChain'       => 'A mailer in this failover chain is missing or cannot send, or the chain is empty, so this mailer cannot send at all.',
    'mailerLoops'             => 'This failover chain loops back on itself, so this mailer cannot send.',
    'maskSet'                 => 'Set',
    'maskEmpty'               => 'Empty',
    'warningBridge'           => 'This site has no app/Config/Mailer.php, so its mail goes through the deprecated legacy mailer, which is removed in Bonfire 2.0. Run `php spark bonfire:email-env` to move to code.',
    'warningLeftoverRows'     => 'Old mail settings, possibly including a plain text password, are still in the settings table. Run `php spark bonfire:email-env --purge` to delete them.',
    'warningNullDefault'      => 'The default mailer discards all mail. Nothing is being sent.',
    'warningBrokenDefault'    => 'The default mailer, {0}, cannot send, so sending mail fails. See its entry below.',
    'warningUndefinedDefault' => 'The default mailer, {0}, is not defined, so sending mail fails.',

    'testSend'      => 'Test send',
    'testSendDesc'  => 'Sends a fixed test message through the mailer you choose, using the saved From.',
    'testMailer'    => 'Mailer',
    'testRecipient' => 'Recipient',
    'testButton'    => 'Send test',
    'testSent'      => 'Test message sent to {0}.',
    'testDiscarded' => 'This mailer discards mail: nothing was delivered.',
    'testLogged'    => 'This mailer writes mail to the log: nothing was delivered.',
    'testNotSent'   => 'The test message was not sent.',
    'testSubject'   => 'Bonfire test message',
    'testBody'      => 'This is a test message from Settings -> Email. Receiving it means this mailer works.',

    'previewsModTitle'     => 'Mail Previews',
    'previewsEmpty'        => 'No mail to preview',
    'previewsEmptyHint'    => 'Mailables that implement Previewable appear here.',
    'previewName'          => 'Mail',
    'previewSubject'       => 'Subject',
    'previewTabHtml'       => 'HTML',
    'previewTabText'       => 'Plain text',
    'previewTabRaw'        => 'Raw MIME',
    'previewSendToMe'      => 'Send to me',
    'previewSendToMeDesc'  => 'Sends this preview to {0} and nobody else.',
    'previewMailer'        => 'Mailer',
    'previewSent'          => 'Preview sent to {0}.',
    'previewNotSent'       => 'The preview was not sent.',
    'previewUnknownMailer' => 'That mailer is not defined.',
    'previewNoFrom'        => 'No From address: set one in Settings -> Email.',
];
