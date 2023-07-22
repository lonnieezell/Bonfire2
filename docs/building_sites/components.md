# View Components

View Components (or just Components) allow you to create custom HTML elements to use within your views. 

## Self-Closing Tags

At their most basic, components serve as dynamic templates that allow you to reduce the typing in your 
application. This can help boil longer, complex sections down to a single HTML tag. This is especially
useful with CSS utility frameworks like TailWind, or when using the utilities in Bootstrap 5, etc. Using
components in these situations allows you to keep the style info in one place where making changes to 
one file changes every instance of the view throughout the application.

To create a component, simply create a new view file within the theme's `Component` directory, or the 
application's, `app/Components` directory.

A simple avatar image might look something like this: 

```
// app/Components/avatar.php
<img
  src="<?= $src ?? '' ?>"
  class="rounded-circle shadow-4"
  style="width: <?= $width ?? '150px' ?>;"
  alt="<?= $alt ?? '' ?>"
/> 
```

When using the component within a view, you would insert a tag with `x-` prepended to the filename: 

```
<x-avatar src="<?= $user->avatarUrl() ?>" alt="<?= $user->name ?>" />
```

Any attributes provided when you insert the component like this are made available as variables within 
the component view. In this case, the `$src` and `$alt` attributes are passed to the component view, resulting
in the following output: 

```
<img
  src="http://example.com/avatars/foo.jpg"
  class="rounded-circle shadow-4"
  style="width: 150px"
  alt="John Smith"
/> 
```

## Custom Tags

You can include the content within the opening and closing tags by inserting the reserved `$slot` variable: 

```
<x-green-button>Click Me!</x-green-button>
```

```
// app/Components/green-button.php
<button class="btn btn-success">
    <?= $slot ?>
</button>
```

## Controlled Components

Finally, you can create a class to add additional logic to the output. The file must be in the same directory
as the component view, and should have a name that is the PascalCase version of the filename, with 'Component'
added to the end of it. 

A `famous-qoutes` component would have a view called `famous-quotes.php` and a controlling class called
`FamousQuotesComponent.php`. The class must extend `Bonfire\View\Component`. The only requirement is that you 
implment a method called `render()`. 
