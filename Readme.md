## Creating a settings page for Wordpress only
## Usage
1. Put Settings.php file in your project
2. Put assets folder in same path
3. Include Settings.php in your project (through php function or through composer)

### Example in plugin
```php
require 'src/Settings.php';
```

### Example through composer.json file

```json
{
...
    "autoload": {
        "psr-4": {
            ...
            "Ozplugin\\Core\\": "src/ozplugin-core/"
        },
    },
    "require": {
       ...
    }
}
```

4. Create new class instance on WP hook 'init'
```php
add_action('init', 'set_settings_page');

function set_settings_page() {
    $settings = new Ozplugin\Core\Settings();
    $settings->registerAjaxHook(); // need to save option by ajax
    $settings->add_page('Settings example'); // create admin page
    $settings->enqueue_scripts(); // enqueue React script
}

add_filter('oz_plugin_settings', 'oz_add_page_option'); // create list of your options here

function oz_add_page_option() {
    return [
            'pages' => [
                'settings' => [
                    'name' => 'Horizontal tab name example',
                    'view' => [
                        'type' => 'settings', // only this type supports at moment
                    ],
                    'tabs' => [
                        [
                            'name' => 'General',
                            'options' => [
                                [
                                    'title' => 'Option example',
                                    'description' => '',
                                    'order' => 10,
                                    'fields' => [
                                        [
                                            'name' => 'option_name',
                                            'value' => '',
                                            'type' => 'input'
                                        ]
                                    ]
                                ],
                            ]
                        ],
                        [
                            'name' => 'Second settings',
                            'options' => [
                                
                            ]
                        ],

                    ]
                ],
            ]
        ];
}

```