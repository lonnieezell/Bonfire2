# Dashboard Cells

You can customize the dashboard with the use of [view cells](https://codeigniter.com/user_guide/outgoing/view_cells.html).
You can specify which cells to display by editing the `Bonfire\Dashboard\Config\Dashboard` config class;

```php
class Dashboard extends BaseConfig
{
    public array $cells = [
        'Bonfire\Widgets\Cells\WidgetCells::stats',
        'Bonfire\Widgets\Cells\WidgetCells::logs',
        'Bonfire\Widgets\Cells\WidgetCells::charts',
    ];
}
```

The cells make up the default layout of the Dashboard, in the order listed. You can comment out any class to have it not show.
The stats, logs and charts cells that come on by default stand for the stats, log and chart widgets: those are not on the
default layout, and admins add them from the palette. Every other cell becomes one full-width widget with the id `cell:` plus its
`Class::method`, which admins can move, size or remove when they [customize their dashboard](../modules/customizing_the_dashboard.md).
For more control over a widget's title, icon, sizes or permission, write a [dashboard widget](dashboard_widgets.md) instead,
as Quick Links now is.

## Creating the Cell

The cells are very simple class methods that return the HTML or `view()` results:

```php
class ShopCells
{
    /**
     * Lists the orders waiting to ship.
     */
    public function openOrders()
    {
        return view('Shop\Views\open_orders', [
            'orders' => model(OrderModel::class)->open(),
        ]);
    }
}
```

Add `'Shop\Cells\ShopCells::openOrders'` to `$cells` and it becomes the widget `cell:Shop\Cells\ShopCells::openOrders`.
