# Markdown and components

`$entry->content()` turns an entry's Markdown body into HTML. `content_markdown()` does the same for any Markdown
string. Both can also expand [View Components](../building_sites/components.md) written in the Markdown.

## Components in Markdown

Write a component's `<x-…>` tag in the Markdown, as you would in a view:

```markdown
Some text.

<x-callout type="warning" />
```

`content()` converts the Markdown to HTML first, then expands the components. A component's own HTML is added after the
conversion, so indented lines in its template never turn into a code block.

This happens in `content()` itself, so it works outside a view too: in a controller that returns JSON, or in a
[feed](feeds_and_sitemaps.md). The expanded HTML passes through a view's decorator unchanged.

To get the Markdown as HTML and leave the `<x-…>` tags as written, pass `components: false`:

```php
$entry->content(components: false);
```

Any component the theme or the app has can be used. There is no allow-list, so like the rest of the body, treat the
Markdown as code you trust.

### Components that wrap Markdown

Markdown inside a component's opening and closing tags follows CommonMark's rule for raw HTML: **it's converted to HTML
only if blank lines separate it from the tags.**

```markdown
<x-callout>

This is **Markdown**, and ends up in the callout as HTML.

</x-callout>
```

Without the blank lines the whole block is raw HTML, so the Markdown inside it is left as written:

```markdown
<x-callout>
This is **not** converted.
</x-callout>
```

### An unknown component

A tag for a component that doesn't exist throws a `RuntimeException` that names it, `View not found for component:
callout`, in development and testing. In production it's logged as an error, and the tag is removed from the output,
along with whatever sat between a pair of tags.

## Rendering any Markdown string

`content_markdown()` runs a string through the same steps as `content()`. Use it for Markdown in front matter fields, or in
the fields of a block:

```php
<?= content_markdown($entry->field('intro')) ?>
```

```php
content_markdown($text);                       // Markdown to HTML, with components expanded
content_markdown($text, components: false);    // Markdown to HTML only
```

It's named `content_markdown()` because the `markdown()` function already belongs to Postal's mail views.

## What stays escaped

Raw HTML passes through, but GitHub's rules disallow some tags, and the converter escapes them. `<style>` and
`<script>`, for example, show as text. Links to `javascript:` and the like are dropped. The output of a component is
not filtered: it's your template.
