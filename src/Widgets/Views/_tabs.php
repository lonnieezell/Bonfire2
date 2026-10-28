<x-tabs>
    <?php foreach (['' => 'Widgets', 'stats' => 'Statistics', 'linechart' => 'Line Chart', 'barchart' => 'Bar Chart', 'doughnutchart' => 'Doughnut Chart', 'piechart' => 'Pie Chart', 'polarareachart' => 'Polar Area Chart', 'logactivity' => 'Log Activity'] as $alias => $title) : ?>
        <li class="nav-item">
            <a class="nav-link<?= $tab === ($alias ?: 'basics') ? ' active' : '' ?>"
               href="<?= site_url(ADMIN_AREA . '/settings/widgets' . ($alias === '' ? '' : '/' . $alias)) ?>">
                <?= $title ?>
            </a>
        </li>
    <?php endforeach ?>
</x-tabs>
