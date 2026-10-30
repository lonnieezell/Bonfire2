# Consent

With the [Global Data Protection Regulation](https://gdpr.eu/) and other data privacy regulations being put in place
across the globe, you have various requirements as web developers that you didn't use to. The Consent module helps
you manage collecting consent from the user before storing any cookies through an opt-in system for all non-required
cookies. It provides the mechanism for displaying the consent form, and recording the user's preferences.

For a step-by-step example, see [Load Analytics Only After Visitors Consent](../building_sites/consent.md).

## Defining the Consent Types

The Consent module allows you to define the types of consent that you would like to collect from the user. These are defined in the `Config\Consent` class. The default consents are:

```php
public array $consents = [
    'required' => [
        'locked' => true,
        'name'   => 'Consent.requiredName',
        'desc'   => 'Consent.requiredDesc',
    ],
    'performance' => [
        'name' => 'Consent.performanceName',
        'desc' => 'Consent.performanceDesc',
    ],
    'targeting' => [
        'name' => 'Consent.targetingName',
        'desc' => 'Consent.targetingDesc',
    ],
];
```

A consent marked `'locked' => true` is always accepted: its checkbox is checked and disabled, and the server ignores
any attempt to reject it. Use it for cookies the site can't work without, like the session cookie.

`name` and `desc` can be language keys (`File.key`), which are shown in the visitor's language, or literal text.

### Adding or changing a consent

When you add a key to `$consents`, visitors who answered before see the form again on their next visit. Their earlier
answers are pre-filled and the new consent is unchecked.

Consent is given for a stated purpose. If you change what an existing consent is used for, give it a **new key**
instead of editing its description, so every visitor is asked about the new purpose:

```php
// Before: 'performance' covered analytics only.
// After: analytics and session recordings need fresh consent.
'performance_v2' => [
    'name' => 'Performance',
    'desc' => 'Analytics and anonymous session recordings that help us improve the site.',
],
```

Then check the new key with `has_consent('performance_v2')`.

## Using the Helper

A helper method, `has_consent`, is provided so that you can easily meet the consent requirements when building your site.

The helper is loaded automatically by the BaseController. As long as you extend the BaseController (or one of its
children, like the AuthController) then you do not need to explicitly load the helper.

The helper can be used both within the views and the controllers to limit which services are called (like ad-tracking
or analytics) and which cookies you set.

Pass the consent's key from `$consents`. Within a view you might wrap a Google Analytics tag within a check against the `performance` consent:

```php
<?php if (has_consent('performance')) : ?>
<script>
    <!-- Global Site Tag (gtag.js) - Google Analytics -->
    ... (several lines of code) ...
</script>
<?php endif ?>
```

## Letting Visitors Change Their Consent

Visitors must be able to withdraw consent as easily as they gave it. Put `consent_settings_link()` somewhere on every
page, usually the footer:

```php
<footer>
    <?= consent_settings_link() ?>
</footer>
```

It renders a "Cookie settings" link that reopens the consent form with the visitor's current choices pre-filled. Pass
your own text with `consent_settings_link('Privacy choices')`. It returns an empty string while consent is off.

## How Consent Is Saved

The consent form posts to the `consent-save` route (`POST /consent`), with a CSRF token when CSRF protection is on. The
route:

- saves the choices in the `bf_consent` cookie for the whole site (`path=/`, `SameSite=Lax`, `Secure` over HTTPS),
  lasting `$consentLength` days;
- appends a row to the `consent_records` table: a random `consent_id` (also kept in the cookie), the choices, the time,
  and the user's ID when they are signed in. No IP address or user agent is stored;
- redirects back, so scripts behind `has_consent()` start or stop on the reloaded page.

Closing the form with its X keeps what the visitor accepted before and rejects everything else. For a first-time
visitor, that rejects all non-locked consents.

The records let you show when a browser or user consented, and to what. There is no admin screen for them.

## Customizing the Consent Form

The consent form is a view that can be customized to fit your site's design. The view can be changed by editing the `Consent` config file:

```php
public string $consentForm = 'Bonfire\Consent\Views\consent_form';
```

A custom form must keep the contract of the default view:

- a `<form>` with `id="consent-popup"` that posts to `url_to('consent-save')` and includes `csrf_field()`;
- the `hidden` attribute when `$show` is false;
- a checkbox named `consents[<alias>]` with value `1` for each consent, `checked` when `$choices[<alias>]` is set,
  and `checked disabled` when the consent is `locked`;
- submit buttons named `choice` with the value `accept`, `reject`, `custom` (save the checked boxes) or `close`.

The view receives `$consents` (with `name` and `desc` already translated), `$choices` (the visitor's saved answers),
`$show` and `$message`. `{policy_url}` in the output is replaced with the cookie policy link.

You can also specify the CSS and Javascript files that are loaded with the form:

```php
public string $consentFormStyles  = 'Bonfire\Consent\Views\consent_styles';
public string $consentFormScripts = 'Bonfire\Consent\Views\consent_scripts';
```

## Configuration

All settings are available in the `Config\Consent` class. `$requireConsent`, `$consentLength` and `$policyUrl` can also
be changed in the admin area under **Settings → Consent**, which saves the values to the database. The text (the message,
the policy title and the consents) lives only in the config and language files, so it can be translated.

**$requireConsent**

This determines whether the consent system is enabled or not. If `true`, the consent form will be automatically
inserted into all front-end views by the Consent controller filter. If `false`, the consent system will not display
and the helper will always return `true`. The default value is `false`;

**$consentLength**

The number of days before a visitor is asked for their consent again. The default value is 180 days (6 months).

**$consentForm**

The view that is displayed to site visitors that a) asks for their basic consent and b) allows them to accept/reject
the various consents. Locked consents can't be rejected; by default that is the `required` consent, which covers things
like the Auth cookie that determines if you're logged in or not.

**$consentFormStyles**
**$consentFormScripts**

The views that will be loaded that contain the CSS and Javascript, respectively. The contents of these views will
be inserted into each page upon page load.

**$policyUrl**

A URL (either full or relative) that points to the page that details the cookie policy. On many sites this will be
included in the Privacy Policy or Terms of Service pages, or similar. The default value is `privacy`.

**$policyTitle**

The link text used for the cookie policy link in the consent form. The default value is the `Consent.policyLinkTitle`
language key ("Cookie Policy").

**$consentMessage**

The message shown to visitors at the top of the consent form. Put `{policy_url}` where the cookie policy link should
appear; it is replaced with a link to `$policyUrl` that uses `$policyTitle` as its text. The default value is the
`Consent.message` language key.

Like `$policyTitle` and each consent's `name` and `desc`, it can be a language key (`File.key`), which is translated, or
literal text, which is shown as-is.

**$consents**

This is an array of the initial consents that will be asked for in the Consent Form. Each array item includes
the alias the consent can be checked with, and the following items:

- **locked**: Optional. `true` means the consent is always accepted.
- **name**: The name as it is displayed to the visitors.
- **desc**: The description of the consent that describes the purpose of the cookies/services and what might be
  no longer functional if the consent is rejected.
