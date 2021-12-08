# Statistic widget

The statistics widget is just a counter with a shortcut that allows me to open the list of elements of a module.

Adding a new widget is done by Module.php for your module, similar to adding a menu:

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

### bgColor

bgColor represents the background color of the widget.
The available options are:

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

### title

The title of the widget displayed at the top left

### value

The value of the widget displayed under the title: typically the total number of records

### url

The address that displays the list of objects on your module. It will be associated with the word "view detail"

### faIcon

The FontAwesome icon
