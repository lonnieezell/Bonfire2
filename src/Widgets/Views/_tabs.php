<ul class="nav nav-tabs nav-fill" style="margin-bottom: -2px;">
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'basics') : ?> active <?php endif ?>"
           href="<?= site_url(ADMIN_AREA . '/settings/widgets') ?>">
            Widgets
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'stats') : ?> active <?php endif ?>"
           href="<?= site_url(ADMIN_AREA . '/settings/widgets/stats') ?>">
            Stats/Cards
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'linechart') : ?> active <?php endif ?>"
           href="<?= site_url(ADMIN_AREA . '/settings/widgets/linechart') ?>">
            Line Chart
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'barchart') : ?> active <?php endif ?>"
           href="<?= site_url(ADMIN_AREA . '/settings/widgets/barchart') ?>">
            Bar Chart
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'doughnutchart') : ?> active <?php endif ?>"
           href="<?= site_url(ADMIN_AREA . '/settings/widgets/doughnutchart') ?>">
            Doughnut Chart
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'piechart') : ?> active <?php endif ?>"
           href="<?= site_url(ADMIN_AREA . '/settings/widgets/piechart') ?>">
            Pie Chart
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'polarareachart') : ?> active <?php endif ?>"
           href="<?= site_url(ADMIN_AREA . '/settings/widgets/polarareachart') ?>">
            Polar Area Chart
        </a>
    </li>
    <!--?= service('resourceTabs')->renderTabsFor('widgets') ?-->


</ul>
