# Customizing the Dashboard

Every admin can arrange their own dashboard without changing anyone else's. Until they do, they see the
[site default](#the-site-default). Other widgets are added from the widget library.

## Edit mode

Click **Customize** to enter edit mode. Nothing is stored until you click **Save layout**; **Cancel** puts the
dashboard back the way it was. Every change can be undone and redone with the arrows in the edit bar, or Ctrl+Z
(⌘Z) and Shift+Ctrl+Z, and the message after a removal or deletion has its own **Undo**.

In edit mode you can:

- drag a widget to reorder it, also into another section;
- open a widget's settings from its sliders button, when it has settings you may change;
- remove a widget with its trash button;
- reset to the site default with the clock button. Saving the site default as your layout forgets yours, so you
  follow the site default again when a site admin changes it.

## Sections

A layout is a list of sections, each with an optional heading. From a section's ⋯ menu you can add a widget to it,
move it up or down, insert a section below it, or delete it along with its widgets. Sections left without widgets
are dropped on save, and a layout holds at most 20 sections.

## Sizes

Size a widget from the tools above it, or drag its bottom-right corner. The sizes are ¼, ⅓, ½, ⅔ and Full width
(3, 4, 6, 8 or 12 of 12 columns), within the range that widget allows. Quick Links is always full width.

## The widget library

**Add widget** opens the library. It lists every widget available to you, grouped (Quick Links, Stats, Charts,
Custom), and marks the ones already on your dashboard. Click **Add**, or drag one straight onto a section. An added
widget shows a placeholder until you save.

## Keyboard and screen readers

Every edit also works from the keyboard. With a widget focused:

| Key | Does |
| --- | --- |
| Arrow keys | Move it. Up and Down, and Left and Right past either end, move it to the section before or after. |
| Shift + Left / Right | Resize it. |
| Delete | Remove it. |

Screen readers hear each change announced.

## The site default

Admins who have not saved their own layout see the site default. Out of the box it is the `$layout` property of the
Dashboard config file (see [The Dashboard](dashboard.md#configuration)).

Admins with the `widgets.settings` permission get **Save as site default** in the edit bar. It stores the layout as
shown as the site default, which every admin without their own layout then sees. Admins who customized keep theirs.

**Settings → Widgets → Reset site default** forgets the stored site default, so it goes back to the configured
`$layout`. Admins' own layouts are kept.

The dashboard picks the first of these that exists:

1. the admin's own saved layout;
2. the site default saved with **Save as site default**;
3. the `$layout` of the Dashboard config file, or, when that is `null`, the plain cells of `$cells` in order.

## Which widgets are available

**Settings → Widgets** decides which widgets are available to everyone. Every widget is available until a site admin
switches it off. A switched-off widget is left out of every library and every layout.

A widget can also need a permission. Admins who lack it don't see it in their library or on their dashboard, its
data is never fetched for them, and it is dropped from any layout they save. The built-in widgets need:

| Widget | Permission |
| --- | --- |
| Users by Group, Users in Recycler | `users.list` |
| User Groups and the group charts | `groups.settings` |
| Log activity | `logs.view` |
| Quick Links | none |

A widget that becomes available later shows up in the library of admins who have customized, never in their layout.
