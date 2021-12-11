# Charts widget

Adding a new chart widget is done by Module.php for your module, similar to adding a menu:

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

**addDataset()**

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
- if indicate **sum** then **selectCount($field)**  is executed