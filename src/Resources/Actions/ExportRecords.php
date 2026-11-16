<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources\Actions;

use Bonfire\Actions\Action;
use Bonfire\Actions\ActionContext;
use Bonfire\Actions\ActionResult;
use Bonfire\Actions\ActionRuns;
use Bonfire\Actions\Placement;
use Bonfire\Fields\Field;
use Bonfire\Resources\Column;
use Bonfire\Resources\Resource;

/**
 * Downloads the records as a CSV of the list's columns, with the text the list shows. Anyone who may see the list
 * may export it. A large export is queued, and its toast links to the file.
 */
class ExportRecords extends Action
{
    public string $icon         = 'fas fa-file-csv';
    public Placement $placement = Placement::Bulk;

    public function __construct(private readonly Resource $resource)
    {
        $this->label      = lang('Resources.exportCsv');
        $this->permission = $resource->permission . '.list';
    }

    public function handle(ActionContext $context): ActionResult
    {
        $dir = WRITEPATH . 'exports/';

        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        // Nothing serves an export after a day, so each export clears out the expired ones.
        foreach (glob($dir . '*.csv') ?: [] as $expired) {
            if (filemtime($expired) < time() - ActionRuns::FILE_LIFETIME) {
                @unlink($expired);
            }
        }

        $path    = $dir . bin2hex(random_bytes(16)) . '.csv';
        $columns = $this->resource->columns();
        $file    = fopen($path, 'wb');

        // The byte order mark tells Excel the file is UTF-8.
        fwrite($file, "\u{FEFF}");
        fputcsv($file, array_map(static fn (Column|Field $column): string => $column->label, $columns), escape: '');

        foreach ($context->chunks() as $records) {
            foreach ($records as $record) {
                fputcsv($file, array_map(self::defuse(...), $this->resource->cells($columns, $record)), escape: '');
            }
        }

        fclose($file);

        return ActionResult::file(
            $path,
            $this->resource->slug . '-' . date('Y-m-d-His') . '.csv',
            lang('Resources.exported', [mb_strtolower($this->resource->title())]),
        );
    }

    /**
     * Quotes a cell that a spreadsheet would run as a formula, as OWASP advises against CSV injection.
     */
    private static function defuse(string $cell): string
    {
        return $cell !== '' && str_contains("=+-@\t\r", $cell[0]) ? "'" . $cell : $cell;
    }
}
