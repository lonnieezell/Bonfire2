# Email Settings

The Email settings screen shows how your site sends email, such as password resets, email verification codes and
two-factor login codes. Open **Email** under **Settings** in the sidebar. You need the `admin.settings` permission.

You can't change mailers or their credentials here. They live in `app/Config/Mailer.php` and `.env`, and Bonfire never
stores them. See the [Postal docs](https://lonnieezell.github.io/postal/) for how to configure a mailer. This screen
lets you check that setup, send a test message, and set the From name and address.

## Mailers

The screen lists every mailer in `config('Mailer')`, with its transport and settings, and marks the default.

A setting whose name matches `pass`, `secret`, `key`, `token`, `credential` or `auth`, in any case and at any depth,
shows only **Set** or **Empty**. So SES's `credentials` and `dkim.privateKey` never show their values, and neither do
those of a custom transport. The masking happens on the server, so the values aren't in the page at all. Credentials
inside a URL are hidden too, so `smtp://user:password@host` shows as `smtp://***@host`.

!!! warning "What masking can't catch"

    Masking goes by the setting's name, so usernames, hosts and paths show in full. So does a secret under any other
    name, one in a URL's path or query string, such as `https://hooks.example.com/abc123`, or one in a value with no
    `scheme://`, such as `user:password@host`. Give secrets in a custom
    transport a name the pattern matches. `authType` shows as **Set** even though it only names a login method, because
    its name matches `auth`.

A failover mailer lists the mailers it tries, in order, with a link to each.

### Warnings

| Warning | What it means | What to do |
| --- | --- | --- |
| The legacy mailer is in use | The app has no `app/Config/Mailer.php`, so mail goes through the [legacy mail bridge](#the-legacy-mail-bridge). | Run `php spark bonfire:email-env` and follow [Moving mail settings into code](#moving-mail-settings-into-code). |
| Old mail settings are still stored | The app has its own Mailer config, but the settings table still holds old transport values, possibly a plain text password. | Run `php spark bonfire:email-env --purge`. |
| The default mailer discards mail | The default mailer, or the first mailer in its failover chain, uses the `null` transport, so nothing is sent and nothing reports an error. | Set `$default` in `app/Config/Mailer.php`. |
| The default mailer isn't defined | `$default` names a mailer that isn't in `$mailers`, so sends fail. | Fix `$default` or add the mailer. |
| The default mailer can't send | Its transport isn't registered, or its failover chain is empty, loops, or has a mailer that can't be built. | Fix the problem shown on that mailer. |

A mailer also shows a warning if its transport isn't registered in `$transports`, or if its failover chain loops back
on itself, is empty, or has a mailer that can't be built. Postal builds the whole chain before it sends, so one bad
mailer stops all of them, even an earlier one that would work. A chain entry that isn't defined has no link.

## Send a test message

**Test send** sends a fixed message to check a mailer. Choose the mailer, type the recipient, which starts as your own
address, and click **Send test**. The result appears above the form.

- It uses the saved From name and address, so save any changes to them first. Without a From address it fails.
- A failure shows the mailer's error, with the secrets the screen knows about replaced by `***`. An encoded secret
  can't be recognised, and values under 4 characters are left alone, so those would still show.
- The `null` transport and the `log` transport report success without delivering anything, so the result says the
  message was discarded or logged. A failover mailer goes by the first mailer in its chain.
- A failover mailer reports success if any mailer in its chain accepted the message. It doesn't say which one did. If a
  real mailer fails and a later `null` or `log` mailer takes over, the result still says the message was sent.
- It can't send through a failover chain that loops.

## From

| Setting | What it does |
| --- | --- |
| Name | The sender name emails show, such as your site's name. Required, at least 2 characters. |
| Email | The address emails are sent from. Required. |

Click **Save Settings** to save them. They're the default for all mail. See [The From address](#the-from-address).

## How Bonfire sends mail

Bonfire sends its mail through [Postal](https://github.com/lonnieezell/postal), which is installed with it.

| Email | How it's sent |
| --- | --- |
| Account activation | The Postal Mailable `Bonfire\Auth\Mails\ActivationMail` |
| Two-factor login code | The Postal Mailable `Bonfire\Auth\Mails\TwoFactorMail` |
| Magic link | Shield's own `emailer()`, which Postal handles |

All three go through the same mailer: the default one in `app/Config/Mailer.php`, or without that file, the
[legacy mail bridge](#the-legacy-mail-bridge) for the two Mailables and the same saved settings for the magic link.

### Your email templates

The activation and two-factor emails come in two versions. Which one is sent depends on the keys in `$views` in your
`app/Config/Auth.php`:

| Version | Keys | Who gets it |
| --- | --- | --- |
| Branded Markdown, with a plain text part | `action_email_activate_markdown`, `action_email_2fa_markdown` | New installs. It's used whenever the key is there. |
| HTML only | `action_email_activate_email`, `action_email_2fa_email` | Installs whose `$views` doesn't have the Markdown keys, which includes every install from before they were added. |

The two emails switch separately, since each has its own key.

The Markdown version shows your site name from Settings → General, under a band in your accent color. The code is in a
panel, and there's no button, because the pages for entering a code only work in the session that asked for it.

#### Switching to the Markdown version

Updating Bonfire doesn't change your emails, because your published `app/Config/Auth.php` keeps the `$views` it was
installed with. To switch, add the two keys to it:

```php
public array $views = [
    // ...
    'action_email_activate_markdown' => '\Bonfire\Views\Mail\activation_email',
    'action_email_2fa_markdown'      => '\Bonfire\Views\Mail\two_factor_email',
];
```

Remove them to go back to the HTML version. If you've saved `Auth.views` in the settings table, that row wins over the
file for sent mail, so change it there too. An app that never published `Auth.php` inherits Bonfire's `$views`, so it
gets the Markdown version as soon as it updates. Publish the file and leave these keys out to keep the HTML version.

#### Customizing the Markdown version

Point a key at your own view. It's a [Postal Markdown view](https://lonnieezell.github.io/postal/markdown-mailables/),
and gets two variables: `$code`, and `$details`, the username, IP address, device and date as label and value pairs.

A visitor chooses their own user agent and username, and `esc()` wouldn't stop `[Reset your password](https://evil.example)`
from becoming a link in a security email. So the values in `$details` are Markdown code spans, which can't form a link
in the HTML, and empty ones are left out. The text part still shows the words and any URL in them, as plain text.

PHP drops a newline right after a closing `?>`, so leave an extra blank line after one. To share a partial between
views, call `view('partial', ['code' => $code], ['debug' => false])`, or view debug comments can end up in the mail.

To change the page around the content, add an `action_email_markdown_layout` key that names your own layout view. It
gets the content as `$content`. Without the key, Bonfire's layout is used.

#### HTML views

The HTML views get `$code`, `$user`, `$ipAddress`, `$userAgent` and `$date`, as they always have, and are sent as HTML
only. If you customised them, they keep working.

#### Previews

Both Mailables implement Postal's `Previewable`, so they can be rendered with sample data, an unsaved user and a fake
code. A preview writes nothing. It reads `Auth.views` from your `Auth` config only, not the settings table, so it decides
between the two versions from the file. The Markdown version's branding is read from your real settings. See them under
[Mail Previews](mail_previews.md).

### The From address

The **From** name and address on this screen are the site-wide default. A Mailable without a From gets them, including
Bonfire's and your own. A Mailable that sets its own From keeps it. Without a From here, the activation and two-factor
emails fail with an error instead of going out.

Mail sent with `service('email')` doesn't use this default. It needs a From before it reaches Postal: from `setFrom()`,
or else from `fromEmail` in `Config\Email`. Without one, `send()` returns `false`.

## The legacy mail bridge

Bonfire sends its mail through [Postal](https://github.com/lonnieezell/postal), which reads its mailers from
`app/Config/Mailer.php`. Postal's own default mailer is `null`, which discards every message without an error. So an
install without that file doesn't lose the mail it sends through Postal Mailables, Bonfire adds a mailer named `legacy`
and makes it the default. You don't have to do anything for this.

The `legacy` mailer sends through the transport your existing settings describe. They are read each time a message
is sent, the same way as before: a value in the Settings table wins, then `email.*` in `.env`, then
`app/Config/Email.php`. With nothing set anywhere, mail goes through PHP's `mail()` function.

The Email settings screen shows the `legacy` mailer with the settings it would send with, masked like any other, under
a deprecation warning. You can't edit them there. To change one before you leave the bridge, set it in `.env` or
`app/Config/Email.php`, or remove its row from the settings table, since a row wins.

Bonfire's activation and two-factor emails go through it. Mail sent with `service('email')` or Shield's `emailer()`,
which is how the magic link email goes out, doesn't pass through the bridge and logs no notice. Without a Mailer config,
`emailer()` already uses those same settings.

!!! warning "Deprecated"

    The bridge is removed in Bonfire 2.0. Until you leave it, the first send through it in each request logs a
    deprecation notice. Notices are only written if `Config\Logger::$threshold` includes them.

### Leaving the bridge

The bridge turns off as soon as your app has its own `Config\Mailer` class, so set that file up before you deploy it.

1. Run `php spark publish` to create `app/Config/Mailer.php`, or create it by hand.
2. Set `$default` and define the mailer in that file. This one defines an SMTP server:

    ```php
    <?php

    namespace Config;

    use Myth\Postal\Config\Mailer as PostalMailer;

    class Mailer extends PostalMailer
    {
        public string $default = 'smtp';

        public array $mailers = [
            'smtp' => [
                'transport'  => 'smtp',
                'host'       => 'smtp.example.com',
                'port'       => 587,
                'encryption' => 'tls',
                'authType'   => 'login',
            ],
        ];

        public function __construct()
        {
            parent::__construct();

            $this->mailers['smtp']['username'] = env('SMTP_USER');
            $this->mailers['smtp']['password'] = env('SMTP_PASS');
        }
    }
    ```

3. Put the credentials in `.env`.

!!! danger "The published file discards mail until you edit it"

    The file `php spark publish` creates inherits `$default = 'null'`. Bonfire steps aside for any app Mailer config,
    even one left at `null`, so mail is discarded from then on, with no error, until you set `$default`.

The deprecation notice points to `php spark bonfire:email-env`, which prints these values for your install. See
[Moving mail settings into code](#moving-mail-settings-into-code).

## Moving mail settings into code

Mail transport settings are moving out of the database, to `.env` and an `app/Config/Mailer.php` file for
[Postal](https://github.com/lonnieezell/postal). This command prints both for your install:

```console
php spark bonfire:email-env
```

It writes no files. It reads each value through `setting()` and prints:

- where each value comes from: `database`, `Config\Email`, or `.env`
- the `.env` lines, such as `mailer.mailers.smtp.host`, which replace any `email.*` transport keys you have. Keep
  `email.fromEmail` and `email.fromName`: your From address is still read from them
- a `Mailer.php` that defines a matching `smtp`, `sendmail` or `mail` mailer and makes it the default. The username
  and password are blank there, because they are set by the `.env` lines

With no database rows and only framework defaults in `Config\Email`, it prints "Nothing to migrate".

!!! warning "Some values don't survive `.env`"

    CodeIgniter trims quotes from the ends of a config override, turns the words `true` and `false` into booleans,
    expands `${name}`, and reads `.env` one line at a time. The command prints a warning under any line this affects.
    Change that value, or set it from `env('...')` in the constructor of your `Mailer.php`.

### Removing the old settings

SMTP passwords are stored in the settings table as plain text. Once the new `Mailer.php` is in place, delete them:

```console
php spark bonfire:email-env --purge
```

It asks first; add `--force` to skip the question. It removes the transport keys only: `protocol`, `mailPath`,
`SMTPHost`, `SMTPPort`, `SMTPUser`, `SMTPPass`, `SMTPCrypto`, `SMTPTimeout` and `SMTPKeepAlive`, including any stored
under a settings context. The command only prints values it would send with, so it reports how many rows are under a
context without showing them: copy any you still need before purging. Your From name and address stay. Values in
`Config\Email` or `.env` aren't touched, so if your credentials are in `.env` `email.*` keys, you only need to rename
them.

`--purge` refuses to run while the app has no Mailer config of its own, even with `--force`, because those settings are
still what your mail is sent with. Purging them would fall back to the `Config\Email` defaults and send through PHP's
`mail()`.
