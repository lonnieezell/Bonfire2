<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="<?= esc(lang('Users.usersModTitle')) ?>" count="<?= $total ?>">
	<?php if (auth()->user()->can('users.create')): ?>
		<a href="<?= url_to('user-new') ?>" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> <?= lang('Users.newUser')?></a>
	<?php endif ?>
</x-page-head>

<x-admin-box>
	<div x-data="{filtered: false}" hx-target="#content-list" hx-include=".bf-toolbar-form, .filters .filter-check">
		<div class="bf-toolbar">
			<form class="bf-toolbar-form" action="<?= current_url() ?>" method="get"
				hx-get="<?= current_url() ?>"
				hx-trigger="input delay:300ms from:[name=search], change from:[name=role]">
				<div class="bf-toolbar-search">
					<i class="fas fa-search" aria-hidden="true"></i>
					<input type="search" name="search" class="form-control" value="<?= esc($search) ?>"
						placeholder="<?= lang('Users.searchUsers') ?>" aria-label="<?= lang('Users.searchUsers') ?>">
				</div>
				<div class="bf-chips" role="radiogroup" aria-label="<?= lang('Users.headers.groups') ?>">
					<x-chip name="role" value="" label="<?= esc(lang('Users.allGroups')) ?>" count="<?= $total ?>" active="<?= $role === '' ? '1' : '' ?>" />
					<?php foreach ($roles as $alias => $title) : ?>
						<x-chip name="role" value="<?= esc($alias) ?>" label="<?= esc($title) ?>" count="<?= $groupCounts[$alias] ?? 0 ?>" active="<?= $role === $alias ? '1' : '' ?>" />
					<?php endforeach ?>
				</div>
			</form>
			<x-filter-link />
		</div>

		<div class="bf-list-with-filters">
			<!-- List Users -->
			<div id="content-list">
				<?= $this->include('Bonfire\Users\Views\_table') ?>
			</div>

			<!-- Filters -->
			<div class="bf-filter-panel" x-cloak x-show="filtered" x-transition.duration.240ms>
				<?= view_cell('Bonfire\Core\Cells\Filters::renderList', 'model=UserFilter target=#content-list') ?>
			</div>
		</div>
	</div>

</x-admin-box>
<?php $this->endSection() ?>
