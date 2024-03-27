# Consent

With the [Global Data Protection Regulation](https://gdpr.eu/) and other data privacy regulations being put in place
across the globe, you have various requirements as web developers that you didn't use to. The Consent module helps
you manage collecting consent from the user before storing any cookies through an opt-in system for all non-required
cookies. It provides the mechanism for displaying the consent form, and recording the user's preferences. A helper
method, `has_consent`, is provided so that you can easily meet the consent requirements when building your site.

The helper is loaded automatically by the BaseController. As long as you extend the BaseController (or one of its
children, like the AuthController) then you do not need to explicitly load the helper.

The helper can be used both within the views and the controllers to limit which services are called (like ad-tracking
or analytics) and which cookies you set.

## Defining the Consent Types

The Consent module allows you to define the types of consent that you would like to collect from the user. These are defined in the `Config\Consent` class. The default consents are:

```php
public array $consents = [
        'required' => [
            'name' => 'Functionality',
            'desc' => 'These cookies are required for the normal operation of this website.',
        ],
        'performance' => [
            'name' => 'Performance',
            'desc' => 'These cookies help us measure how visitors use the site, what the traffic sources are,
                and which pages are popular. This helps to improve the website for everyone. If you reject these
                we will be unable to use your visits to make site improvements.',
        ],
        'targeting' => [
            'name' => 'Targeting',
            'desc' => 'These are usually placed by third-party advertising networks, which may use information about
                your visits to develop a profile of your interests. This information may be shared with other networks or sites to deliver more relevant advertising across multiple sites.',
        ],
    ];
```

## Using the Helper

The helper is loaded automatically by the BaseController. As long as you extend the BaseController (or one of its
children, like the AuthController) then you do not need to explicitly load the helper.

The helper can be used both within the views and the controllers to limit which services are called (like ad-tracking
or analytics) and which cookies you set.

Within a view you might wrap a Google Analytics tag within a check against the `performing` consent:

```php
<?php if (has_consent('performing')) : ?>
<script>
    <!-- Global Site Tag (gtag.js) - Google Analytics -->
    ... (several lines of code) ...
</script>
<?php endif ?>
```

## Customizing the Consent Form

The consent form is a view that can be customized to fit your site's design. The view can be changed by editing the `Consent` config file:

```php
public string $consentForm = 'Bonfire\Consent\Views\consent_form';
```

You can also specify the CSS and Javascript files that are loaded with the form:

```php
public string $consentFormStyles  = 'Bonfire\Consent\Views\consent_styles';
public string $consentFormScripts = 'Bonfire\Consent\Views\consent_scripts';
```

## Configuration

All settings are available in the `Config\Consent` class, or in the admin settings area.

**$requireConsent**

This determines whether the consent system is enabled or not. If `true`, the consent form will be automatically
inserted into all front-end views by the Consent controller filter. If `false`, the consent system will not display
and the helper will always return `true`. The default value is `false`;

**$consentLength**

The number of days before a visitor is asked for their consent again. The default value is 180 days (6 months).

**$consentForm**

The view that is displayed to site visitors that a) asks for their basic consent and b) allows them to accept/reject
the various consents. The `required` consent is unable to be rejected and contains things like the Auth cookie that
determines if you're logged in or not.

**$consentFormStyles**
**$consentFormScripts**

The views that will be loaded that contain the CSS and Javascript, respectively. The contents of these views will
be inserted into each page upon page load.

**$policyUrl**

A URL (either full or relative) that points to the page that details the cookie policy. On many sites this will be
included in the Privacy Policy or Terms of Service pages, or similar.

**$consents**

This is an array of the initial consents that will be asked for in the Consent Form. Each array item includes
the alias the consent can be checked with, and the following items:

- name: The name as it is displayed to the visitors.
- desc: The description of the consent that describes the purpose of the cookies/services and what might be
  no longer functional if the consent is rejected.
