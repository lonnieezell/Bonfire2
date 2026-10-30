# Load Analytics Only After Visitors Consent

This guide turns on Bonfire's cookie consent form and loads an analytics script only for visitors who accept
performance cookies. For every setting and how to customize the form, see the [Consent](../modules/consent.md) reference.

## 1. Turn on the consent form

In the admin area, go to **Settings → Consent** and enable consent. Alternatively, set it in `app/Config/Consent.php`:

```php
public bool $requireConsent = true;
```

While consent is off, `has_consent()` always returns `true` and no form is shown.

## 2. Point to your cookie policy

On the same settings screen, set the cookie policy URL, or set `$policyUrl` in the config file. The form links to
this page. To change the link text, set `$policyTitle` in `app/Config/Consent.php`.

## 3. Wrap the script in a consent check

In your view or layout, wrap the analytics tag in `has_consent()`, passing the consent's key:

```php
<?php if (has_consent('performance')) : ?>
<script>
    <!-- Global Site Tag (gtag.js) - Google Analytics -->
    ... (several lines of code) ...
</script>
<?php endif ?>
```

The helper is loaded automatically in controllers that extend Bonfire's `BaseController`.

Visitors who have not answered, or who rejected performance cookies, no longer load the script. The same check works
in controllers, before you set a cookie or call a tracking service.

## 4. Let visitors change their mind

Add the settings link to your layout's footer so visitors can reopen the form and withdraw consent:

```php
<?= consent_settings_link() ?>
```

When a visitor unchecks Performance and saves, the page reloads and `has_consent('performance')` returns `false`, so
the analytics script is no longer loaded.

If you later use performance cookies for something new, add a new consent key instead of rewording the old one. See
[Adding or changing a consent](../modules/consent.md#adding-or-changing-a-consent).
