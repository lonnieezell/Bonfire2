# Alerts

Bonfire uses [Tatter\Alerts](https://github.com/tattersoftware/codeigniter4-alerts) to provide support for
simple alerts. The usage is simple: 

```
alert('success', 'Your message goes here.');
```

This displays a green message that appears in the top-right corner of the admin area. The first argument (`success`)
specifies a [Bootstrap alert type](https://getbootstrap.com/docs/5.0/components/alerts/#examples) that is added
to the class automatically to specify how it looks. The second argument is the content that should be in the alert
and can be a single string or an array of strings to apply multiple alerts.

Additionally, you can use flash messages in the session to trigger alerts. 
This would typically be used during a redirect: 

```
return redirect()->back()->with('message', 'It worked!');
return redirect()->back()->with('error', 'Did not work');
return redirect()->back()->with('error', ['not good', 'nope', 'cannot do it']);
```

When an array is used it will display a new alert for each element in the array.

You can adjust which session variables are checked and their class mapping using the
Alerts config file. Read more in the docs at the repo linked above.
