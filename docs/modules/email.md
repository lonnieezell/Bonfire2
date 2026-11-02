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

## Moving mail settings into code

Mail transport settings are moving out of the database, to `.env` and an `app/Config/Mailer.php` file for
[Postal](https://github.com/lonnieezell/postal). This command prints both for your install:

```console
php spark bonfire:email-env
```

It writes no files. It reads each value through `setting()` and prints:

- where each value comes from: `database`, `Config\Email`, or `.env`
- the `.env` lines, such as `mailer.mailers.smtp.host`, which replace any `email.*` keys you have
- a `Mailer.php` that defines a matching `smtp`, `sendmail` or `mail` mailer and makes it the default. The username
  and password are blank there, because they are set by the `.env` lines

With no database rows and only framework defaults in `Config\Email`, it prints "Nothing to migrate".

!!! warning "Some values don't survive `.env`"

    CodeIgniter trims quotes from the ends of a config override, turns the words `true` and `false` into booleans, and
    expands `${name}`. The command prints a warning under any line this affects. Change that value, or set it from
    `env('...')` in the constructor of your `Mailer.php`.

### Removing the old settings

SMTP passwords are stored in the settings table as plain text. Once the new `Mailer.php` is in place, delete them:

```console
php spark bonfire:email-env --purge
```

It asks first; add `--force` to skip the question. It removes the transport keys only: `protocol`, `mailPath`,
`SMTPHost`, `SMTPPort`, `SMTPUser`, `SMTPPass`, `SMTPCrypto`, `SMTPTimeout` and `SMTPKeepAlive`. Your From name and
address stay. Values in `Config\Email` or `.env` aren't touched, so if your credentials are in `.env` `email.*` keys, you
only need to rename them.

`--purge` refuses to run while the app has no Mailer config of its own, even with `--force`, because those settings are
still what your mail is sent with. Purging them would fall back to the `Config\Email` defaults and send through PHP's
`mail()`.
