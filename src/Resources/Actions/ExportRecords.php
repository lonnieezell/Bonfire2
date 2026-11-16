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
use Bonfire\Users\User;
use RuntimeException;
use Throwable;

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

    /**
     * The export on list `$list` that `$user` may run, which the Export CSV button above the list runs, or null.
     */
    public static function offered(string $list, User $user): ?self
    {
        foreach (service('actions')->for($list)->bulk($user) as $action) {
            if ($action instanceof self) {
                return $action;
            }
        }

        return null;
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

        $path = $dir . bin2hex(random_bytes(16)) . '.csv';
        $file = fopen($path, 'wb') ?: throw new RuntimeException("Can't write the export to {$dir}.");

        try {
            $this->write($file, $context);
        } catch (Throwable $e) {
            // A half-written export is no use to anyone.
            fclose($file);
            unlink($path);

            throw $e;
        }

        fclose($file);

        return ActionResult::file(
            $path,
            $this->resource->slug . '-' . date('Y-m-d-His') . '.csv',
            lang('Resources.exported', [mb_strtolower($this->resource->title())]),
        );
    }

    /**
     * Writes the header and a row per record. Each chunk loads again through the resource's `query()`, so the file
     * has only records the list shows, with what its joins add, such as a relation's column.
     *
     * @param resource $file
     */
    private function write($file, ActionContext $context): void
    {
        $columns = $this->resource->columns();

        // The byte order mark tells Excel the file is UTF-8.
        fwrite($file, "\u{FEFF}");
        fputcsv($file, array_map(static fn (Column|Field $column): string => $column->label, $columns), escape: '');

        foreach ($context->chunks() as $records) {
            $model = model($this->resource->model, false);
            $key   = $model->table . '.' . $model->primaryKey;
            $ids   = array_map(static fn (object $record): mixed => Column::get($record, $model->primaryKey), $records);
            $done  = [];

            foreach ($this->resource->query($model)->whereIn($key, $ids)->orderBy($key, 'asc')->findAll() as $record) {
                // A join to a one-to-many relation repeats a record.
                $id = (string) Column::get($record, $model->primaryKey);

                if (! isset($done[$id])) {
                    $done[$id] = true;
                    fputcsv($file, array_map(self::defuse(...), $this->resource->cells($columns, $record)), escape: '');
                }
            }
        }
    }

    /**
     * Quotes a cell that a spreadsheet would run as a formula, as OWASP advises against CSV injection.
     */
    private static function defuse(string $cell): string
    {
        return $cell !== '' && str_contains("=+-@\t\r", $cell[0]) ? "'" . $cell : $cell;
    }
}
