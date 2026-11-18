# Media

The media library holds the files your site uploads: images, PDFs and other documents. Open **Media** in the sidebar. You need the `media.access` permission to see it.

Each file is a row in the `media` table, so run `php spark migrate --all` after updating to get the table.

## Permissions

| Permission | What it allows |
| --- | --- |
| `media.access` | Seeing the library |
| `media.edit` | Uploading files, and changing their alt text and folder |
| `media.delete` | Deleting files |

New installs give all three to the `superadmin` and `admin` groups. On an existing install, run
`php spark bonfire:permissions` to add them.

## Browsing and searching

The library shows the newest files first. The folder links along the top show the files in one folder, or in **No
folder**, and **All files** shows every one. Search finds files whose original name or alt text contains what you type.

## Uploading

Choose one or more files, type a folder or leave it empty, and press **Upload**. A file that is refused doesn't stop the
others, and the message names it. A file is refused when:

- its extension isn't in `$allowedExtensions`, or
- it is larger than `$maxSize`.

A file is saved under a lower-case, hyphenated version of its name, such as `my-cat.jpg` for `My Cat.JPG`. If a file
already has that name, it gets `-2`, `-3` and so on. The name it was uploaded with is kept and shown.

## Alt text and folders

Click a file to see its URL, type, size and dimensions, and to change its alt text and folder. Folders are labels: a file
stays where it is on disk when you move it to another folder, so its URL never changes. A folder exists while a file is in
it.

## Deleting

Deleting a file sends it to the Recycler, under **Tools**, where you can restore it. The file stays on disk, and its URL keeps
working, until it is purged there. Purging removes the file.

## Configuration

Copy `Bonfire\Media\Config\Media` to `app/Config/Media.php` to change these:

| Property | Default | What it does |
| --- | --- | --- |
| `$containers` | `['public' => ['path' => FCPATH . 'uploads/media', 'url' => 'uploads/media']]` | Where uploads are stored on disk, and the URL they're served at, relative to the base URL. The admin uploads to `public`. |
| `$maxSize` | `10240` | The largest upload accepted, in kilobytes. PHP's own `upload_max_filesize` and `post_max_size` still apply. |
| `$allowedExtensions` | images, PDF, office documents, text, CSV, ZIP and common audio and video | The extensions accepted, in lower case. |

!!! warning "SVG files"

    SVG is not allowed by default. An SVG file can hold script, which runs when the file is opened from your site. Only
    add `svg` if everyone who can upload is trusted.

## File and image fields

Blueprints, resource forms and action inputs pick a file from the library with a [`file` or `image`
field](../content/field_types.md#file-and-image). The field stores the file's ID. Its picker is the library in a dialog,
with an upload form for those with `media.edit`.

## Using files in code

The `media()` helper gives the file a field holds, or `null` when the field is empty or the file was deleted. Controllers
that extend Bonfire's load it for you:

```php
echo media($product->photo)?->url();
```

To load a file by ID yourself, ask the model:

```php
use Bonfire\Media\Models\MediaModel;

$media = model(MediaModel::class)->find($id);

echo $media->url();   // https://example.com/uploads/media/my-cat.jpg
echo $media->alt;
```

To store an upload from your own controller, pass it to the storage service. It throws a `Bonfire\Media\MediaException`,
with a message for the user, when the file is refused:

```php
$media = service('mediaStorage')->store($this->request->getFile('photo'), 'public', 'products');
```
