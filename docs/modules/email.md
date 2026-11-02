# Email Settings

The Email settings screen controls how your site sends email, such as password resets, email verification codes and
two-factor login codes. Open **Email** under **Settings** in the sidebar. You need the `admin.settings` permission.

Change what you need, then click **Save Settings**.

## Set up SMTP

Most email providers, such as Mailgun, Postmark, Amazon SES or your web host, give you SMTP details to send through.

1. Under **From**, enter the name and address your emails come from. Use an address your provider lets you send
   from.
2. Under **Delivery**, choose **SMTP**.
3. Enter the **Host**, **Username** and **Password** your provider gave you.
4. Choose the **Port** and **Encryption** your provider asks for. The usual pairs are:

    | Port | Encryption |
    | --- | --- |
    | 587 | TLS |
    | 465 | SSL |
    | 25 or 2525 | None or TLS |

    If your provider uses another port, choose **other** and type it in.

5. Click **Save Settings**.

To check the settings, send yourself an email from the site, for example with "forgot password" on the login page.

## Settings

### From

| Setting | What it does |
| --- | --- |
| Name | The sender name emails show, such as your site's name. Required, at least 2 characters. |
| Email | The address emails are sent from. Required. |

### Delivery

| Setting | What it does |
| --- | --- |
| Protocol | How mail is sent: **SMTP** through a mail server, **Mail** through PHP's `mail()` function, or **Sendmail** through the server's sendmail program. |
| Sendmail path | Where the sendmail program is, such as `/usr/sbin/sendmail`. Shown for **Sendmail**. |

**Mail** has no options of its own and only works on Linux servers that are set up to send mail.

### SMTP

These are shown when **Protocol** is **SMTP**.

| Setting | What it does |
| --- | --- |
| Host | The mail server's address, such as `smtp.example.com`. |
| Port | The mail server's port: 25, 587, 465, 2525, or **other** to type your own. |
| Username | The login for the mail server. |
| Password | The password for the mail server. It's stored in the settings table as plain text. |
| Encryption | **None**, **TLS** or **SSL**. Use what your provider asks for. |
| Timeout | How many seconds to wait for the mail server before giving up. |
| Persistent connection | Keeps the connection to the mail server open between emails. Helps when the site sends many emails at once. |

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

The Mailables render the views named in `Auth.views`, under `action_email_activate_email` and `action_email_2fa_email`,
with the same `$code`, `$user`, `$ipAddress`, `$userAgent` and `$date` as before. If you customised those views, they
keep working. The two emails are always sent as HTML.

Both Mailables implement Postal's `Previewable`, so they can be rendered with sample data, an unsaved user and a fake
code, without touching the database. A preview reads `Auth.views` from your `Auth` config only, not the settings table.

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

Bonfire's activation and two-factor emails go through it. Mail sent with `service('email')` or Shield's `emailer()`,
which is how the magic link email goes out, doesn't pass through the bridge and logs no notice. Without a Mailer config,
`emailer()` already uses those same settings.

!!! warning "Deprecated"

    The bridge is removed in Bonfire 2.0. Until you leave it, the first send through it in each request logs a
    deprecation notice. Notices are only written if `Config\Logger::$threshold` includes them.

### Leaving the bridge

The bridge turns off as soon as your app has its own `Config\Mailer` class, so set that file up before you deploy it.

1. Run `php spark publish` to create `app/Config/Mailer.php`, or create it by hand.
2. Set `$default` and define the mailer in that file. This one matches an SMTP server from the settings above:

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
