# View Metadata

Bonfire provides a `Metadata` service that simplifies working with the meta infomration, scripts, and styles in your application.

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
```

The `addMeta`, `addLink` and `addScript` methods all take an array of key value pairs. Each of the pairs will have
all of the key/value pairs rendered as `key=value` within the tags, so you can fully customize what each one contains.

## Outputting the Metadata

Within your layouts you can use the `render()` method to render any of the data types.

```php
// Render the title tag - <title>...</title>
<?= $viewMeta->render('title') ?>
// Get just the title value
<?= $viewMeta->title ?>

// Render all meta tags
<?= $viewMeta->render('meta') ?>

// Render all link tags
<?= $viewMeta->render('link') ?>

// Render all script tags
<?= $viewMeta->render('script') ?>
<?= $viewMeta->render('rawScripts') ?>
```
