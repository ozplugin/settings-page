<?php

/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      https://oz-plugin.com/
 * @copyright 2023 Ozplugin
 * @ver 1.0
 */

namespace Ozplugin\Core;

use WP_Error;

/**
 * settings structure
 * {
 *  pages: [{
 *  settings: {
 *  name: 'Settings',
 *  tabs: [
 *      {
 *      name: 'Main settings',
 *      options: [{
 *              title: '',
 *              description: '',
 *              order: '',
 *              fields: [
 *                      name: '',
 *                      value: '',
 *                      type: '', input,textarea,select,checkbox,color,switch,html,shortcodes
 *                      multiple: '',
 *                      title: '',
 *                      description: '',
 *                      values: '',
 *                  ]
 *              }]
 *      }
 *  ]
 *  }]
 * }
 * }
 */

/**
 * Class for Settings page.
 */
class Settings
{
    const VER = '1.0';

    const PREFIX = 'oz_';

    const FILE = __FILE__;

    public $page_name = '';

    public $page_capability = 10;

    public $menu_slug = '';

    public $page_icon = 'dashicons-admin-settings';

    public $page_priority = 10;
    
    /**
     * Creating WP settings page
     *
     * @param  string $name Name of the page. Required
     * @param  string $capability Permission who can see this page. By default is administrator
     * @param  string $menu_slug The slug name to refer to this menu by. Should be unique for this menu page and only include lowercase alphanumeric, dashes, and underscores characters to be compatible with sanitize_key() .
     * @param  string $icon The URL to the icon to be used for this menu.
                            *Pass a base64-encoded SVG using a data URI, which will be colored to match the color scheme. This should begin with 'data:image/svg+xml;base64,'.
                            *Pass the name of a Dashicons helper class to use a font icon, e.g. 'dashicons-chart-pie'.
                            *Pass 'none' to leave div.wp-menu-image empty so an icon can be added via CSS.
     * @param  int $priority The position in the menu order this item should appear.
     * @return void
     */
    public function add_page($name, $capability = 'administrator', $menu_slug = '', $icon = 'dashicons-admin-settings', $priority = 10) {
        if (!$name) return new WP_Error('name_missed', 'Name of setings page is required');
        add_action( 'admin_menu', [$this, 'add_menu_page'] );
        $this->page_name = $name;
        $this->page_capability = $capability;
        $this->menu_slug = $menu_slug ?: static::FILE;
        $this->page_icon = $icon;
        $this->page_priority = $priority;
    }
    
    /**
     * Adding menu page
     *
     * @return void
     */
    public function add_menu_page() {
        add_menu_page(
            $this->page_name,
            $this->page_name, 
            $this->page_capability, 
            $this->menu_slug, 
            [$this, 'page'],
            $this->page_icon,
            $this->page_priority
        );  
    }


    /**
     * Output html code for render settings page
     *
     * @return void
     */
    public function page()
    {
        $styles = [
            'position' => 'fixed',
            'z-index' => '99999',
            'top' => '0',
            'left' => '0',
            'width' => '100%',
            'height' => '100%',
            'overflow' => 'auto',
            'background' => '#f9fbfe',
        ];
        $styles_css = '';
        foreach ($styles as $key => $style) {
            $styles_css .= "$key:$style;";
        }
        echo '<div style="' . $styles_css . '" class="ozplugin_settings_page" id="' . static::PREFIX . 'admin_page"></div>';
    }

    /**
     * Return all options as array
     *
     * @return array
     */
    public final function getOptions()
    {
        $options = [];
        $options = array_merge($options, $this->setOptions());
        return apply_filters(static::PREFIX . 'plugin_settings', $options);
    }

    /**
     * Function to add options in child classes
     * settings structure
     * {
     *  pages: [{
     *  settings: {
     *  name: 'Settings',
     *  view: {
     *      type: 'settings', // todo other page types like addons, table of post type
     * },
     *  tabs: [
     *      {
     *      name: 'Main settings',
     *      options: [{
     *              title: '',
     *              description: '',
     *              order: '',
     *              isPro: false,
     *              fields: [
     *                     {
     *                      name: '',
     *                      value: '',
     *                      type: '',
     *                      multiple: '',
     *                      title: '',
     *                      description: '',
     *                      values: '',
     *                      }
     *                  ]
     *              }]
     *      }
     *  ]
     *  }]
     * }
     * }
     * @return array
     */
    /**
     * Set options from child classes
     *
     * @return array
     */
    public function setOptions()
    {
        return [];
    }

    /**
     * Register ajax hook to save option
     *
     * @return void
     */
    public function registerAjaxHook()
    {
        add_action('wp_ajax_ozplugin_save_option', [$this, 'save']);
    }

    /**
     * Save option. Expect $_POST array with option name, value, type keys. Echoing json with result of saving
     *
     * @return void
     */
    public function save()
    {
        if (wp_doing_ajax() && wp_verify_nonce($_POST['_wpnonce'], 'ozplugin-nonce')) {
            if (apply_filters('ozplugin_canSaveSettings', true)) {
                $name = sanitize_text_field($_POST['name']);
                // if option is array. for each key in array like option_name[suboption][subsuboption]
                preg_match('/^([a-zA-Z0-9_]+)(\[[a-zA-Z0-9_]+\])(\[[a-zA-Z0-9_]+\])?(\[[a-zA-Z0-9_]+\])?/', $name, $iSaArrayName);
                $key = '';
                $values = [];
                if ($iSaArrayName && count($iSaArrayName) > 2) {
                    $key = $iSaArrayName[1];
                }
                $value = $_POST['value'];
                $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'string';
                switch ($type) {
                    case 'number':
                        $value = (int)($value);
                        break;
                    case 'object':
                        $json_decoded = json_decode(stripslashes($value), 1);
                        if ($json_decoded && is_array($json_decoded)) {
                            $value = $json_decoded;
                            array_walk_recursive($value, 'Ozplugin\Utils::sanitize_json');
                        }
                        if ($value) {
                            $value = is_array($value) ? $value : explode(',', $value);
                            $mapping = isset($_POST['objectValuesType']) && $_POST['objectValuesType'] == 'number' ? 'intval' : 'sanitize_text_field';
                            if (!$json_decoded) {
                                $value = array_map($mapping, $value);
                            }
                        }
                        break;
                    case 'boolean':
                        $value = $value == 'true';
                        break;
                    case 'html':
                        add_filter('safe_style_css', [$this, 'safe_styles']);
                        $post = array_merge(wp_kses_allowed_html('post'), [
                            'body' => ['class' => 1],
                            'center' => ['class' => 1, 'style' => 1],
                            'head' => ['class' => 1],
                            'html' => ['class' => 1],
                            'meta' => ['charset' => 1, 'name' => 1, 'content' => 1, 'http-equiv' => 1],
                            'style' => []
                        ]);
                        $value = wp_kses($value, $post);
                        remove_filter('safe_style_css', [$this, 'safe_styles']);
                        break;
                    default:
                        $value = esc_html($value);
                }

                if ($iSaArrayName && count($iSaArrayName) > 2 && $key) {
                    $values = get_option($key);
                    if (!is_array($values)) {
                        $values = [];
                    }
                    if (count($iSaArrayName) == 3 && $iSaArrayName[2]) {
                        $k = str_replace(['[', ']'], '', $iSaArrayName[2]);
                        if (!isset($values[$k])) {
                            $values[$k] = '';
                        }
                        $values[$k] = $value;
                    } elseif (count($iSaArrayName) == 4 && $iSaArrayName[3]) {
                        $k = str_replace(['[', ']'], '', $iSaArrayName[2]);
                        $t = str_replace(['[', ']'], '', $iSaArrayName[3]);
                        if (!isset($values[$k])) {
                            $values[$k] = [];
                        }
                        if (!isset($values[$k][$t])) {
                            $values[$k][$t] = '';
                        }
                        $values[$k][$t] = $value;
                    }
                    $value = $values;
                    $name = $key;
                }
                $suc = $name && isset($_POST['value']) ? update_option($name, $value) : false;
                $res = [
                    'success' => $name && isset($_POST['value']),
                    'value' => $value,
                    'text' => !$suc ? 'Error with saving' : '',
                ];
            } else {
                $res = [
                    'success' => false,
                    'text' => __('You do not have enough permissions to change the settings'),
                ];
            }

            echo (json_encode($res));
        }
        wp_die();
    }

    /**
     * Sanitize json array.
     *
     * @param  mixed $item 
     * @param  mixed $key
     * @return void
     */
    public static function sanitize_json(&$item, $key)
    {
        if (is_numeric($item)) {
            $item = floatval($item);
        } elseif ($item == 'true' || $item == 'false' || !$item) {
            $item = boolval($item);
        } else {
            if ($item && $key == 'values') {
                $item = $item; // todo how to sanitize it but skip \n symbols
            } else {
                $item = sanitize_text_field($item);
            }
        }
    }

    /**
     * Enqueue script (React) to render settings page.
     *
     * @return void
     */
    public function enqueue_scripts()
    {
        add_action('admin_enqueue_scripts', [$this, 'scripts']);
    }

    /**
     * Enqueuу script and options
     *
     * @return void
     */
    public function scripts()
    {
        wp_enqueue_script(
            static::PREFIX . 'settings',
            plugins_url('/', static::FILE) . 'assets/js/admin.js',
            [],
            static::VER
        );
        $lang = $this->getStrings();
        $vars = [
            'logo' => [
                'img' => wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full'),
                'url' => get_admin_url()
            ],
            'adminAjax' => admin_url('admin-ajax.php'),
            'adminURL' => get_admin_url(),
            'nonce' => wp_create_nonce('ozplugin-nonce'),
            'settings' => $this->getOptions(),
            'customNotice' => [
                'variant' => 'warning',
                'text' => 'custom notice'
            ],
        ];
        wp_localize_script(static::PREFIX . 'settings', 'ozplugin_vars', apply_filters(static::PREFIX . 'plugin_vars', $vars));
        wp_localize_script(static::PREFIX . 'settings', 'ozplugin_lang', apply_filters(static::PREFIX . 'plugin_lang', $lang));
    }
    
    /**
     * Genereate select option from array
     *
     * @param  array $arr key => value
     * @return array
     */
    public static function arrayToSelect($arr = []) {
        $options = [];
        if (is_array($arr)) {
            foreach ($arr as $label => $ar) {
                $options[] = [
                    'label' => $label,
                    'value' => $ar
                ];
            }
        }
        return $options;
    }

    /**
     * Return strings of settings page interface
     * todo how to transalte it on other languages
     *
     * @return array
     */
    public function getStrings()
    {
        return [
            'backtowp' => 'Back to WP',
            'wrongpagetype' => 'Wrong page type. Please choose page type to view this page',
            'nosettingsthistab' => 'No settings in this tab.',
            'copied' => 'Copied',
        ];
    }
}
