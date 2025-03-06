# View Components

View Components (or just Components) allow you to create custom HTML elements to use within your views. 

## Self-Closing Tag Components

At their most basic, components serve as dynamic templates that allow you to reduce the typing in your 
application. This can help boil longer, complex sections down to a single HTML tag. This is especially
useful with CSS utility frameworks like TailWind, or when using the utilities in Bootstrap 5, etc. Using
components in these situations allows you to keep the style info in one place where making changes to 
one file changes every instance of the view throughout the application.

To create a component, simply create a new view file within the theme's `Component` directory, or the 
application's, `app/Components` directory.

A simple avatar image might look something like this: 

```php
// app/Components/avatar.php
<img
  src="<?= $src ?? '' ?>"
  class="rounded-circle shadow-4"
  style="width: <?= $width ?? '150px' ?>;"
  alt="<?= $alt ?? '' ?>"
/> 
```

When using the component within a view, you would insert a tag with `x-` prepended to the filename: 

```php
<x-avatar src="<?= $user->avatarUrl() ?>" alt="<?= $user->name ?>" />
```

Any attributes provided when you insert the component like this are made available as variables within 
the component view. In this case, the `$src` and `$alt` attributes are passed to the component view, resulting
in the following output: 

```html
<img
  src="http://example.com/avatars/foo.jpg"
  class="rounded-circle shadow-4"
  style="width: 150px"
  alt="John Smith"
/> 
```

## Components With Opening and Closing Tags

You can include the content within the opening and closing tags by inserting the reserved `$slot` variable: 

```html
<x-green-button>Click Me!</x-green-button>
```

```php
// app/Components/green-button.php
<button class="btn btn-success">
    <?= $slot ?>
</button>
```

## Controlled Components

Finally, you can create a class to add additional logic to the output for both self-closing tags and tags 
with content. The file must be in the same directory as the component view, and should have a name that is
the PascalCase version of the filename, with 'Component' added to the end of it. Any attributes that you 
set on the custom element and any content within element will be passed to the class of the controlled 
component to be, optionally, processed by the component class and/or rendered by the comonent view file. 
The data you pass will be available within the class as it's `$data` property (the tag content – as 
`$data['slot']` property). 

A `famous-qoutes` component would have a view called `famous-quotes.php` and a controlling class called
`FamousQuotesComponent.php`. The class must extend `Bonfire\View\Component`. The only requirement is that you
implement a method called `render()`.

Below are example files of a usable `famous-quotes` component, that can be included in any of these three ways:

```html
<x-famous-quotes />
<x-famous-quotes seconds="30" />
<x-famous-quotes seconds="30">Famous Quotes</x-famous-quotes>
```

It's controlling class file `FamousQuotesComponent.php`:

```php
namespace App\Components;

/**
 * This example controlled component allows to retrieve a random famous quote
 * from the ZenQuotes API and cache it for a specified number of seconds.
 * Number of seconds can be passed as a parameter to the component.
 * If you want to add a title to the component, you can pass it as a $slot variable.
 */
class FamousQuotesComponent extends \Bonfire\View\Component
{
    protected $defaultNoOfSeconds         = 600;
    protected string $famousQuotesAPINode = 'https://zenquotes.io/api/random';

    public function render(): string
    {
        return $this->renderView($this->view, $this->getFamousQuote());
    }

    public function getFamousQuote(): array
    {
        helper('cache');

        if (isset($this->data['seconds']) && is_numeric($this->data['seconds'])) {
            $this->data['seconds'] = (int) round($this->data['seconds'], 0);
        } else {
            $this->data['seconds'] = $this->defaultNoOfSeconds;
        }

        $cacheKey = 'famous_quote';

        if ($cachedQuote = cache($cacheKey)) {
            return $cachedQuote;
        }

        $response  = file_get_contents($this->famousQuotesAPINode);
        $quoteData = json_decode($response, true);

        if (isset($quoteData[0])) {
            $this->data['quote'] = [
                'text'   => $quoteData[0]['q'],
                'author' => $quoteData[0]['a'],
            ];

            cache()->save($cacheKey, $this->data, $this->data['seconds']);
        } else {
            $this->data['quote'] = [
                'text'   => 'Sucking at something is the first step to becoming sorta good at something.',
                'author' => 'Jake (from "Adventure Time")',
            ];
        }

        return $this->data;
    }
}
```

And it's view file `famous-quotes.php`:

```php
<div class="card mb-3">
    <div class="card-header">
        <?= esc($slot ?? '') ?>
    </div>
    <div class="card-body mt-3">
        <p class="card-text text-center"><?= esc($quote['text']) ?></p>
        <footer class="blockquote-footer text-center"><em><?= esc($quote['author']) ?></em></footer>
    </div>
    <div class="card-footer" style="font-size:10px;">Cached for <?= esc($seconds) ?> seconds</div>
</div>
```

