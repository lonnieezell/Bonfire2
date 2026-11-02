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

## The legacy mail bridge

Bonfire's mail is moving to [Postal](https://github.com/lonnieezell/postal), which reads its mailers from
`app/Config/Mailer.php`. Postal's own default mailer is `null`, which discards every message without an error. So an
install without that file doesn't lose its activation and two-factor emails, Bonfire adds a mailer named `legacy` and
makes it the default. You don't have to do anything for this.

The `legacy` mailer sends through the transport your existing settings describe. They are read each time a message
is sent, in the same order as before: the Settings table, then `app/Config/Email.php`, then `email.*` in `.env`. With
nothing saved anywhere, mail goes through PHP's `mail()` function.

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

The deprecation notice points to `php spark bonfire:email-env`, which will print these values for your install. That
command isn't available yet, so use the steps above for now.
