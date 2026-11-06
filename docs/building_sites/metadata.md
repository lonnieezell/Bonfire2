# View Metadata

Bonfire provides a `Metadata` service that simplifies working with the meta information, scripts, and styles in your application.

```php
$viewMeta = service('viewMeta');

// Add the page title
$viewMeta->setTitle('My Site');

// Add misc page meta data
$viewMeta->addMeta(['description' => 'This is the description of the page']);
$viewMeta->addMeta(['property' => 'og:title', 'content' => 'This is the title of the page']);

// Add link tags
$viewMeta->addLink(['rel' => 'canonical', 'href' => 'https://example.com/']);
$viewMeta->addLink(['rel' => 'icon', 'href' => 'favicon.ico', 'type' => 'image/x-icon']);

// Add script tags
$viewMeta->addScript(['src' => 'https://example.com/js/jquery.min.js']);
$viewMeta->addScript(['src' => 'https://example.com/js/bootstrap.min.js', 'type' => 'text/javascript']);

// Adding script blocks
$script = <<<TEXT
    // all your javscript code here
TEXT;
$viewMeta->addRawScript($script);

// Adding style blocks
$viewMeta->addRawStyle('.hero { background: #222; }');
```

The `addMeta`, `addLink`, `addScript` and `addStyle` methods all take an array of key value pairs. Each of the pairs will have
all of the key/value pairs rendered as `key="value"` within the tags, so you can fully customize what each one contains.

## Escaping and duplicates

Attribute values are escaped when they are rendered, so a `"` or `&` in a value is safe. Pass the plain value, not one
you have already run through `esc()`, or it is escaped twice.

The content of `addRawScript()` and `addRawStyle()` is output as it is, between `<script>` or `<style>` tags. Never put
anything a visitor typed in it.

An entry identical to one already added is ignored, so `render()` outputs it once. That goes for `addMeta()`, `addLink()`,
`addScript()` and `addStyle()` entries with the same keys and values in the same order, and for raw scripts and raw
styles with the same content.

## Outputting the Metadata

Within your layouts you can use the `render()` method to render any of the data types.

```php
// Render the title tag - <title>...</title>
<?= $viewMeta->render('title') ?>
// Get just the title value
<?= $viewMeta->title() ?>

// Render all meta tags
<?= $viewMeta->render('meta') ?>

// Render all link tags
<?= $viewMeta->render('link') ?>

// Render all script tags
<?= $viewMeta->render('script') ?>
<?= $viewMeta->render('rawScripts') ?>

// Render all style tags, and the raw style blocks
<?= $viewMeta->render('style') ?>
<?= $viewMeta->render('rawStyles') ?>
```

The App theme's layout outputs `meta`, `title`, `link`, `style`, `rawStyles` and `script`. It doesn't output `rawScripts`. If you
keep your own copy of the layout, make sure it renders every type your site adds.

## Assets from block partials

A partial that needs a stylesheet or script can add it itself, however many times the page renders it. Because identical
entries are output once, three copies of a `gallery` partial add one stylesheet:

```php
<?php service('viewMeta')
    ->addStyle(['rel' => 'stylesheet', 'href' => asset('site/css/gallery.css', 'css')])
    ->addScript(['src' => asset('site/js/gallery.js', 'js'), 'defer' => 'defer']); ?>
<div class="gallery">...</div>
```

Keep the arrays the same in each copy. `['rel' => 'stylesheet', 'href' => 'a.css']` and
`['href' => 'a.css', 'rel' => 'stylesheet']` are two entries.

A layout renders after the views it wraps, so a partial can add assets the layout prints in the `<head>`.

## Assets for one page

Bonfire doesn't add assets for a page on its own. To give one entry its own CSS or JavaScript, add a field to the
[blueprint](../content/blueprints.md) and read it in your template:

```yaml
fields:
  - handle: page_css
    type: text
    label: Page CSS
    instructions: A path under public/, like site/css/landing.css.
```

```php
<?php if ($css = $entry->field('page_css')): ?>
    <?php service('viewMeta')->addStyle(['rel' => 'stylesheet', 'href' => base_url($css)]); ?>
<?php endif ?>
```

Use `addRawStyle()` the same way for a field that holds CSS itself. Only do that for a field that editors you trust can
fill in, since raw content isn't escaped.
