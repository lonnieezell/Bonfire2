# Documentation

The Documentation module shows this guide inside the admin, so you can read the docs for the exact version you have
installed. Open it from **Documentation** in the sidebar, or go to `/admin/docs`.

## Who can see it

The guide only appears outside production. Anyone with `admin.access` can read it, and there's no separate permission
to grant.

| `CI_ENVIRONMENT` | Sidebar group | `/admin/docs` |
|------------------|---------------|---------------|
| `production`     | Hidden        | 404           |
| Anything else    | Shown         | Shown         |

!!! warning "Staging sites show it too"
    Every environment except `production` counts, so anyone with admin access to a staging site can read the guide.
    It's the same guide that's public on the web, but a site you've left on `development` will show it as well.

## How it finds the pages

The guide comes from two parts of the installed package, `vendor/lonnieezell/bonfire/`:

- `mkdocs.yml` sets the structure. Each top-level `nav` section becomes a link in the sidebar, in the same order, and its
  pages are listed beside the page you're reading.
- `docs/` holds the pages. Only pages listed in `nav` can be opened, which is why the decision records in `docs/adr/`
  aren't available.

If either is missing, say because your deploy leaves them out, the sidebar group is hidden and `/admin/docs` is a 404.
The same happens when `mkdocs.yml` can't be parsed, and the reason goes to the error log.

## What's different from the website

The pages are the same, but a few things differ from the [public site](https://lonnieezell.github.io/Bonfire2/):

- There's no search. Use your browser's find for the current page, or search the files in your editor.
- Code highlighting comes from highlight.js, loaded from a CDN, so its colors differ and it needs internet access. Without
  it, code shows as plain text.
- Raw HTML in a page is removed, which turns the cards on the home page into a plain list.
