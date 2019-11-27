# WP Twig

> WordPress with Twig on budget.

This overrides the dispatching of PHP files
and replaces it by parsing Twig files.
Data can be injected using special filter
or by manipulating `$wp_query->query_vars` .

<table>
    <tr>
        <th>Before</th>
        <th>After</th>
    </tr>
    <tr>
        <td>
            <ul>
                <li>404.php</li>
                <li>index.php</li>
                <li>search.php</li>
                <li>single-cpt-foo.php</li>
                <li>...</li>
            </ul>
        </td>
        <td>
            <ul>
                <li>404.html.twig</li>
                <li>index.html.twig</li>
                <li>search.html.twig</li>
                <li>single-cpt-foo.html.twig</li>
                <li>...</li>
            </ul>
        </td>
    </tr>
</table>

**Why ".html.twig" instead of ".twig" only?** <br>
Because this way Twig magically handles escaping.


## Templating / How Twig works


See given files in this repo like the "index.html.twig" file:

```twig
<html>
<body>
    <div class="menu"> <a href="/">HOME</a> :: <a href="/index.php?s=404">Why?</a> </div>
    <hr>
  
    {% block foo %}
        Hello world!
    {% block %}

</body>
</html>
```

So by default this page shows "Hello world".
In other templates we can use the surrounding HTML but replace blocks:

```twig
{% extends 'wp-twig/index.html.twig' %}

{% block foo %}
    Search results are:

    ...
{% endblock %}
```

Note: The path to the theme itself is also needed.
This way it is possible to access files of other themes.

#### Using (WordPress-)Functions


Twig is extended in a way that any function can be used as a filter.
This means functions like `date_i18n('l, d.m.Y')` or `wp_list_pluck` are available as filter and function:

```twig
{{ "l, d.m.Y"|date_i18n }}
{# same as #}
{{ date_i18n("l, d.m.Y") }}

{{ some_posts|wp_list_pluck('ID') }}
{{ wp_list_pluck(some_posts, 'ID') }}

{{ __("translate this!", "wp-twig") }}
```

But for the sake of segregation
and performance, don't do `get_posts('post_type=foo')` within a template.
Data, lists and other expensive things should be injected as follows.


## Data- / Model-Layer


### Injecting data via WP_Query

Since WordPress 1.5 you can inject data in templates using `$wp_query->query_vars`. 
The `load_template` function (and `locate_template` in some cases)
extracts the data as variables
and so does wp-twig:

```php
<?php

add_filter(
    'init',
    static function () {
        global $wp_query;
  
        $wp_query->query_vars['user'] = wp_get_current_user();  
    }
);
```

This is how you may have injected data in templates until now
and it is still available in Twig:

```twig
{% if user %}
    Hello {{ user.user_nicename }}
{% else %}
    Hello guest!
{% endif %}
```


### Injecting variables in the Twig-Context


It gives you four filter for injecting data:

1. `wp_twig_template_{type}_block_{name}`
  (e.g. "wp_twig_template_404_block_foo")
2. `wp_twig_block_{name}`
  (e.g. "wp_twig_block_foo")
3. `wp_twig_template_{type}`
  (e.g. "wp_twig_template_index" or "wp_twig_template_search")
4. `wp_twig_context`

Those are executed in the given order so you can basically add data to templates:

```php
<?php

add_filter(
  'wp_twig_template_page_block_foo',
  static function ( array $context ) {
    $context['bar'] = 42;

    return $context;
  }
);
```

The order is important to bypass already added data
on the more generic levels:

```php
<?php

add_filter(
  'wp_twig_block_foo', // more generic than "wp_twig_template_page_block_foo" from above
  static function ( array $context ) {
    if ( ! array_key_exists( 'bar', $context ) ) {
        $context['bar'] = apply_filters( 'fetch_some_expensive_data', [] );
    }

    return $context;
  }
);
``` 

Bypassing the generation of data spares some time which results in a better performance of the theme.
We suggest that you encapsulate data in a Generator of other lazy contructs to enhance this even more.
