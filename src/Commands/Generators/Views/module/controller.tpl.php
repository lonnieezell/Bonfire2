<@php

namespace <?= $namespace ?>\Controllers;

<?php
$uses = ['Bonfire\Core\AdminController', 'CodeIgniter\HTTP\RedirectResponse', $modelClass];
sort($uses);
?>
<?php foreach ($uses as $use) : ?>
use <?= $use ?>;
<?php endforeach ?>

class <?= $controller ?> extends AdminController
{
    protected $viewPrefix = '<?= $namespace ?>\Views\\';

    /**
     * Lists the <?= $plural ?>. An htmx request, from the search box or the
     * pager, gets only the table.
     *
     * @return RedirectResponse|string
     */
    public function list()
    {
        if (! auth()->user()->can('<?= $slug ?>.list')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $model  = model(<?= $modelShort ?>::class);
        $search = trim((string) $this->request->getGet('search'));

        if ($search !== '') {
            $model->like('<?= $fields[0] ?>', $search);
        }

        $isHtmx = $this->request->hasHeader('HX-Request');

        return $this->render($this->viewPrefix . ($isHtmx ? '_table' : 'list'), [
<?php $key = static fn (string $name): string => str_pad("'{$name}'", max(strlen($listVar), 6) + 2) ?>
            <?= $key($listVar) ?> => $model->orderBy('<?= $primaryKey ?>', 'desc')->paginate(setting('Site.perPage')),
            <?= $key('pager') ?> => $model->pager,
            <?= $key('total') ?> => $model->pager->getTotal(),
            <?= $key('search') ?> => $search,
        ]);
    }

    /**
     * Shows the form for a new <?= $singular ?>.
     *
     * @return RedirectResponse|string
     */
    public function create()
    {
        if (! auth()->user()->can('<?= $slug ?>.create')) {
            return redirect()->to(url_to('<?= $slug ?>-list'))->with('error', lang('Bonfire.notAuthorized'));
        }

        return $this->render($this->viewPrefix . 'form');
    }

    /**
     * Shows the form for an existing <?= $singular ?>.
     *
     * @return RedirectResponse|string
     */
    public function edit(int $id)
    {
        if (! auth()->user()->can('<?= $slug ?>.edit')) {
            return redirect()->to(url_to('<?= $slug ?>-list'))->with('error', lang('Bonfire.notAuthorized'));
        }

        $<?= $var ?> = model(<?= $modelShort ?>::class)->find($id);
        if ($<?= $var ?> === null) {
            return redirect()->to(url_to('<?= $slug ?>-list'))->with('error', lang('Bonfire.resourceNotFound', ['<?= $singular ?>']));
        }

        return $this->render($this->viewPrefix . 'form', ['<?= $var ?>' => $<?= $var ?>]);
    }

    /**
     * Creates a <?= $singular ?>, or saves an existing one, with the model's
     * validation rules.
     *
     * @return RedirectResponse
     */
    public function save(?int $id = null)
    {
        if (! auth()->user()->can($id === null ? '<?= $slug ?>.create' : '<?= $slug ?>.edit')) {
            return redirect()->to(url_to('<?= $slug ?>-list'))->with('error', lang('Bonfire.notAuthorized'));
        }

        $model = model(<?= $modelShort ?>::class);
        if ($id !== null && $model->find($id) === null) {
            return redirect()->to(url_to('<?= $slug ?>-list'))->with('error', lang('Bonfire.resourceNotFound', ['<?= $singular ?>']));
        }

        $data  = $this->request->getPost(['<?= implode("', '", $fields) ?>']);
        $saved = $id === null ? $model->insert($data) : $model->update($id, $data);

        if ($saved === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to(url_to('<?= $slug ?>-edit', $id ?? $model->getInsertID()))
            ->with('message', lang('Bonfire.resourceSaved', ['<?= $singular ?>']));
    }
}
