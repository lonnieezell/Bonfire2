# Dashboard Cells

You can customize the dashboard with the use of [view cells](https://codeigniter.com/user_guide/outgoing/view_cells.html).
You can specify which cells to display by editing the `Bonfire\Dashboard\Config\Dashboard` config class;

```php
class Dashboard extends BaseConfig
{
    public array $cells = [
        'Bonfire\Dashboard\DashboardCells::quickLinks',
        'Bonfire\Widgets\Cells\WidgetCells::stats',
        'Bonfire\Widgets\Cells\WidgetCells::charts',
    ];
}
```

The cells are displayed in the order listed, from top to bottom in the Dashboard. You can comment out any class to have it not show.
The stats and charts cells that come on by default are used to display the widgets, that can be configured in the admin area.

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
