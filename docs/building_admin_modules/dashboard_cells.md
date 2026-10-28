# Dashboard Cells

You can customize the dashboard with the use of [view cells](https://codeigniter.com/user_guide/outgoing/view_cells.html).
You can specify which cells to display by editing the `Bonfire\Dashboard\Config\Dashboard` config class;

```php
class Dashboard extends BaseConfig
{
    public array $cells = [
        'Bonfire\Dashboard\DashboardCells::quickLinks',
        'Bonfire\Widgets\Cells\WidgetCells::stats',
        'Bonfire\Widgets\Cells\WidgetCells::logs',
        'Bonfire\Widgets\Cells\WidgetCells::charts',
    ];
}
```

The cells make up the default layout of the Dashboard, in the order listed. You can comment out any class to have it not show.
The stats, logs and charts cells that come on by default stand for the stats, log and chart widgets switched on in the admin area,
which take their place in that order. Every other cell becomes one full-width widget with the id `cell:` plus its
`Class::method`, which admins can move, size or remove when they [customize their dashboard](../modules/dashboard.md#customizing-the-dashboard).
For more control over a widget's title, icon, sizes or permission, write a [dashboard widget](widgets.md#your-own-dashboard-widget) instead.

## Creating the Cell

The cells are very simple class methods that return the HTML or `view()` results. Here is the Quick Links cell as an example:

```php
class DashboardCells
{
    /**
     * Displays a selection of "Quick Links" in the admin dashboard.
     * This is a view cell that uses the "content" sidebar menu
     * items to determine the links to show.
     */
    public function quickLinks()
    {
        return view('Bonfire\Dashboard\Views\quick_links', [
            'menu' => service('menus')->menu('sidebar')->collection('content'),
        ]);
    }
}
```
