# Widgets

Widgets are elements that display information on the dashboard. They are either a small informational card, or
a chart of some sort.

## Configuration

Some settings are available in the administration settings area. Each type of widget has its own specific settings,
which can be used to specify which information is displayed for the widget, as well as whether it's animated, where
labels are displayed, etc.

---

## Statistic widget

The first widget is the statistics widget, which is just a counter with a shortcut that allows me to open the
list of elements of a module. It could be used to display a year-to-day revenue at a glance, total number of users
in the system, new users this month, or any other simple statistic.

Adding a new widget is done by editing the `initAdmin` method of your `Module.php` file, similar to adding a menu:

```php
// Settings widgets stats on dashboard
$widgets = service('widgets');
$users = new UserModel();
$statsItem = new StatsItem([
    'bgColor' => 'bg-blue',
    'title' => 'Users',
    'value' => $users->countAll(),
    'url' => ADMIN_AREA . '/users',
    'faIcon' => 'fa fa-user',
]);
$widgets->widget("stats")->collection('stats')->addItem($statsItem);
```

**bgColor**

bgColor represents the background color of the widget.
The available options are:

```css
- bg-blue
- bg-red
- bg-orange
- bg-light
- bg-dark
- bg-inverse
- bg-indigo
- bg-purple
- bg-pink
- bg-yellow
- bg-green
- bg-teal
- bg-lime
- bg-cyan
- bg-white
- bg-gray
- bg-gray-dark
```

**title**

The title of the widget displayed at the top left

**value**

The value of the widget displayed under the title: typically the total number of records

**url**

The address that displays the list of objects on your module. It will be associated with the word "view detail"

**faIcon**

The FontAwesome icon



## Configuration

All settings are available in the `Bonfire\Widgets\Config\Stats` class, or in the admin settings area.

In the administration settings area, you can choose to:
- Display the "View Detail" link

---

# Charts widget

Adding a new chart widget is done in the `initAdmin` method of your `Module.php` file, similar to adding a menu:

```php
$statsItem = new ChartsItem([
    'title'   => 'User classification by group',
    'type'   => 'line',
    'cssClass'   => 'col-6',
]);
$statsItem->addDataset('auth_groups_users', 'group', 'user_id');
$widgets->widget('charts')->collection('charts')->addItem($statsItem);
```

**title**

The title of the chart displayed at the top center

**type**

The type of chart.
The available options are:
```
- line
- bar
- doughnut
- pie
- polarArea
```

**cssClass**

Set the width of the chart.
Examples options are:
```
- col-3
- col-6
- col-9
- col-12
```

**addDataset(string $tableName, string $groupField, string $countField, string $selectMode = 'count')**

Currently, only one dataset can be added to the chart. The required parameters are:

- Name of the table
- Grouping field
- Field to count

It is possible to indicate a fourth parameter to choose the counting mode; the possible values are:

```php
- count
- avg
- max
- min
- sum
```

- if indicate **count** then **selectCount($field)** is executed
- if indicate **avg** then **selectAvg($field)**  is executed
- if indicate **max** then **selectMax($field)**  is executed
- if indicate **min** then **selectMin($field)**  is executed
- if indicate **sum** then **selectSum($field)**  is executed

---

## Configuration

All settings are available in the administration settings area.

Each type of widget has its own specific settings.

---

### Charts ###

***Line Chart***

You can set the default values in the `Bonfire\Widgets\Config\LineChart` Class

In the administration settings area, you can choose to:
- Display the title
- View the legend
- Choose the location of the legend
- Enable animation
- You can choose the value of the tension: Bezier curve tension of the line. Set to 0 to draw straightlines.

Advanced settings:

- You can set Line color
- You can set Line width

---

***Bar Chart***

You can set the default values in the `Bonfire\Widgets\Config\BarChart` Class

In the administration settings area, you can choose to:
- Display the title
- View the legend
- Choose the location of the legend
- Enable animation
- Assign a predefined color scheme to fill the chart

---

***Doughnut Chart***

You can set the default values in the `Bonfire\Widgets\Config\DoughnutChart` Class

In the administration settings area, you can choose to:
- Display the title
- View the legend
- Choose the location of the legend
- Enable animation
- Assign a predefined color scheme to fill the chart

---

***Pie Chart***

You can set the default values in the `Bonfire\Widgets\Config\PieChart` Class

In the administration settings area, you can choose to:
- Display the title
- View the legend
- Choose the location of the legend
- Enable animation
- Assign a predefined color scheme to fill the chart

---

***Polar Area Chart***

You can set the default values in the `Bonfire\Widgets\Config\PolarAreaChart` Class

In the administration settings area, you can choose to:
- Display the title
- View the legend
- Choose the location of the legend
- Enable animation
- Assign a predefined color scheme to fill the chart
