# Alerts

Bonfire uses [Tatter\Alerts](https://github.com/tattersoftware/codeigniter4-alerts) to provide support for
simple alerts. The usage is simple: 

```
alert('success', 'Your message goes here.');
```

This displays a green message that appears in the top-right corner of the admin area. The first argument (`success`)
specifies a [Bootstrap alert type](https://getbootstrap.com/docs/5.0/components/alerts/#examples) that is added
to the class automatically to specify how it looks. The second argument is the content that should be in the alert. 

You can have multiple alerts active at one time. 

Additionally, you can use flash messages in the session to display `info`, `error`, and `errors` variables. 
This would typically be used during a redirect: 

```
return redirect()->back()->with('message', 'It worked!');
return redirect()->back()->with('error', 'Did not work');
return redirect()->back()->with('error', ['not good', 'nope', 'cannot do it']);
```

When the `errors` array is used it will display a new alert for each element in the array.
