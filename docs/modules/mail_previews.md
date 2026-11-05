# Mail Previews

Mail Previews shows what your site's emails look like, with sample data, and can send one to you. Open **Mail Previews**
under **Tools** in the sidebar. You need the `email.preview` permission.

It works in every environment, production included, because admin login and the permission protect it. Postal's own
`postal/preview` route is a separate, development-only screen. Bonfire doesn't use it, and it stays off unless you set
`postal.enablePreview`.

## The list

Emails are grouped by module, each with its subject. An email that throws while it's built shows the error in place of
its subject, and the rest of the list still works.

The list shows previewable Mailables from:

- the `Mails/` folder of every Bonfire module: core, app and package
- the namespaces in `Config\Postal::$mailableNamespaces`, which by default is `App\Mails` in `app/Mails`

Bonfire's own activation and two-factor emails are under **Auth**. They show the
[version your `Auth` config selects](email.md#your-email-templates), with your site's real name and accent color. The
magic link is still sent by Shield and isn't a Mailable, so it has no preview.

## A preview

Click an email to open it. There are three tabs:

| Tab | What it shows |
| --- | --- |
| HTML | The HTML body. It's in a sandboxed frame, so scripts in the email don't run and it can't reach the admin page. The tab is left out if the email has no HTML. |
| Plain text | The text body, or text made from the HTML if the email has none. |
| Raw MIME | The full message as it would be sent, apart from the From address, which is added when a message is sent. |

## Send to me

**Send to me** sends the preview to your own account's email address and nobody else. The email's own To, Cc and Bcc
addresses are dropped, along with its return path, any `Resent-*` headers and any read-receipt request
(`Disposition-Notification-To`), and there's no way to enter another address. The email's Reply-To stays, since it's part
of what you're previewing, so a reply to a preview goes to the Mailable's own reply-to address. The one exception is
your own code: an `email.composing` or `email.sending` listener that adds a recipient, such as a site-wide archive,
still runs, because a preview goes through the normal send.

Choose which of the mailers in `Config\Mailer` to send through. This overrides any mailer the Mailable names itself. The
default mailer is selected to start with.

If the send fails, or there's no From address in [Settings → Email](email.md#from), the error shows as an alert and
nothing is sent.

## Making your own Mailable previewable

Implement Postal's `Previewable` and return an instance with sample data from `previewInstance()`. Put the class in a
module's `Mails/` folder, and it appears in the list:

```php
namespace App\Modules\Shop\Mails;

use Myth\Postal\Mailable;
use Myth\Postal\Previewable;

class OrderShippedMail extends Mailable implements Previewable
{
    public function __construct(private readonly string $orderNumber)
    {
        parent::__construct();
    }

    public static function previewInstance(): static
    {
        return new static('SAMPLE-1001');
    }

    protected function build(): void
    {
        $this->to('customer@example.com')
            ->subject('Order ' . $this->orderNumber . ' has shipped')
            ->markdown('App\Modules\Shop\Views\emails\shipped', ['orderNumber' => $this->orderNumber]);
    }
}
```

The module's namespace and class file name have to match, because the list finds classes by their file names. Only files
directly in `Mails/` are found, not those in subfolders.

!!! danger "`previewInstance()` must not persist anything"

    `previewInstance()` and `build()` run each time someone opens the list or a preview, so anyone with `email.preview`
    triggers them just by looking. Don't save records, create tokens or call other services there, and don't use real
    customer data. Build unsaved entities with fake values, as Bonfire's own Mailables do.

## Giving access

`email.preview` is granted to the `superadmin` group (through `email.*`) and the `admin` group. Anyone without it sees
no menu item and gets a 404 from these pages.

An install has its own copy of `app/Config/AuthGroups.php`, so after updating Bonfire the screen stays hidden until you
grant the permission:

```console
php spark bonfire:permissions
```

This adds the grants above once. You can also grant `email.preview` to any group from **Settings → Groups**.
