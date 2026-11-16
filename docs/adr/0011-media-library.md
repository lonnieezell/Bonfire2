# ADR 0011: A media library of uploads in a database table

- **Status:** Accepted
- **Date:** 2026-11-16
- **Amends:** ADR 0003 (file and image fields are no longer out of scope)

## Context

Bonfire can't store, browse or reuse files. Products need images, block pages
need hero and gallery images, and user avatars have their own one-off storage
(`AvatarStorage`). ADR 0003 put file and image fields out of scope for v1.

The media epic (#845) adds a library, file and image fields, a multi-file
field, image transforms and usage tracking. This ADR records the decisions the
first slice, the library and its admin browser, makes for all of them.

## Decision

| Decision | Chose | Over | Why |
|---|---|---|---|
| Module name | `Bonfire\Media`, "media file" | "assets" | `Bonfire\Assets` already serves theme CSS and JS |
| Library store | a `media` table, one row per file | sidecar YAML files plus an index, like Content | alt text, folders, search and usage are queries, and uploads aren't in git anyway |
| File ID | a UUID primary key | auto-increment | references in front matter stay unique and safe to grep, which usage tracking needs |
| Storage | local disk only, through `Config\Media::$containers` (a `path` and a `url` per name) | a filesystem abstraction (Flysystem, S3) now | CodeIgniter has no disk abstraction; named containers leave room for one later |
| Folders | a virtual `folder` column; the file's place on disk is fixed at upload | real directories that move on disk | moving a file is a one-column update and never breaks its URL |
| Delete | soft delete through the Recycler; purging removes the file | hard delete | matches Content entries; a purge is final |

File and image fields come off ADR 0003's out-of-scope list. They arrive in
later slices of #845, built on this library.

## Not decided here

Remote storage, empty folders, renaming folders, drag-and-drop, replacing a
file's bytes, and moving avatars onto the library are all left for later.

## Consequences

- Uploads are not in git and not in the content files, so a site's media has to
  be backed up with its database and its container directories.
- A file's URL never changes after upload, so links to it stay valid when it
  moves between folders.
- SVG uploads are refused by default, because an SVG served from the site's own
  origin can run script.
