<?php

/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      https://oz-plugin.com/
 * @copyright 2023 Ozplugin
 * @ver 1.2
 */

namespace Ozplugin\Core;

use WP_Error;
use WP_Query;
use WP_User_Query;

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
 *              col: 2, // todo how it works?
 *              grid: 1, // todo how it works?
 *              fields: [
 *                      name: '',
 *                      value: '',
 *                      type: '', input,textarea,select,checkbox,color,switch,html,shortcodes,text
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

    public function __construct()
    {
        $this->registerHooks();
    }

    protected function registerHooks()
    {
        return false;
    }

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
    public function add_page($name, $capability = "administrator", $menu_slug = "", $icon = "dashicons-admin-settings", $priority = 10)
    {
        if (!$name) return new WP_Error('name_missed', 'Name of setings page is required');
        $this->page_name = $name;
        $this->page_capability = $capability;
        $this->menu_slug = $menu_slug ?: static::FILE;
        $this->page_icon = $icon;
        $this->page_priority = $priority;
        add_action('admin_menu', [$this, 'add_menu_page']);
    }

    /**
     * Adding menu page
     *
     * @return void
     */
    public function add_menu_page()
    {
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
        $pages = $options['pages'];
        array_multisort($pages, SORT_NUMERIC, array_column($pages, 'order'));
        $options['pages'] = $pages;
        return apply_filters(static::PREFIX . 'plugin_settings', $options);
    }

    protected function canDoThis()
    {
        return wp_doing_ajax() && wp_verify_nonce($_POST['_wpnonce'], 'ozplugin-nonce');
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
        add_action('wp_ajax_ozplugin_get_table', [$this, 'getTable']);
        add_action('wp_ajax_ozplugin_get_post', [$this, 'getPost']);
        add_action('wp_ajax_ozplugin_save_post_data', [$this, 'savePostData']);
        add_action('wp_ajax_ozplugin_save_post', [$this, 'savePost']);
        add_action('wp_ajax_ozplugin_delete_post', [$this, 'deletePost']);
        add_action('wp_ajax_ozplugin_restore_post', [$this, 'restorePost']);
        //add_action('wp_ajax_ozplugin_search', [$this, 'search']); // todo make async select fields
    }

    /**
     * Save option. Expect $_POST array with option name, value, type keys. Echoing json with result of saving
     *
     * @return void
     */
    public function save()
    {
        if ($this->canDoThis()) {
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
                $old_option = get_option($name);
                if (maybe_serialize($value) == maybe_serialize($old_option)) {
                    $res = [
                        'success' => true,
                        'value' => '',
                        'text' => 'There are no changes',
                    ];
                } else {
                    $suc = $name && isset($_POST['value']) ? update_option($name, $value) : false;
                    $res = [
                        'success' => $name && isset($_POST['value']) && $suc,
                        'value' => $value,
                        'text' => !$suc ? 'Error with saving' : '',
                    ];
                }
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
        $screen = function_exists('get_current_screen') ? get_current_screen() : false;
        $base = $screen && $screen->base ? str_replace('toplevel_page_', '', $screen->base) : '';
        if ($base && strpos($this->menu_slug, '.php') !== false) {
            $base = $base . '.php';
        }
        if ($base && wp_normalize_path($base) == wp_normalize_path($this->menu_slug) || apply_filters(static::PREFIX . 'enqueue_settings_scripts', false)) {
            $abs_url = wp_normalize_path(dirname(__FILE__));
            $ABSPATH = wp_normalize_path(ABSPATH);
            $abs_url = str_replace($ABSPATH, '', $abs_url);
            wp_enqueue_script(
                static::PREFIX . 'settings',
                site_url('/') . $abs_url . '/assets/js/admin.js',
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
                'datetimeFormat' => 'MM/dd/yy hh:mm a'
            ];
            wp_localize_script(static::PREFIX . 'settings', 'ozplugin_vars', apply_filters(static::PREFIX . 'plugin_vars', $vars));
            wp_localize_script(static::PREFIX . 'settings', 'ozplugin_lang', apply_filters(static::PREFIX . 'plugin_lang', $lang));
        }
    }

    /**
     * Genereate select option from array
     *
     * @param  array $arr key => value
     * @return array
     */
    public static function arrayToSelect($arr = [])
    {
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
            'addnew' => __('Add New'),
            'somethingwentwrong' => __('Something went wrong')
        ];
    }

    /**
     * Render wp_editor as option type
     *
     * @param  string $text default editor value
     * @param  string $option_name Option name
     * @return string html code
     */
    public static function Editor($text = '', $option_name = '', $tpl_name = '')
    {
        $hash = wp_hash($option_name);
        ob_start();
        wp_editor(
            $text,
            $hash,
            [
                'textarea_name' => $option_name,
                'editor_height' => 425,
                'wpautop' => false,
                'tinymce' => [
                    'forced_root_block' => false,
                    //'valid_elements' => '*[*]',
                    //'valid_elements' => 'head,html,body,meta,img[class=myclass|!src|border:0|alt|title|width|height|style]',
                ],
                'editor_css' => 0,
                'editor_class' => 'ozplugin_editor'
            ]
        );
?>
        <div data-option="<?php echo $option_name; ?>" data-id="<?php echo $hash; ?>" data-name="<?php echo $tpl_name; ?>" class="oz_set_defemail btn btn-primary btn-sm my-2"><?php _e('Load default template', 'oz-donator'); ?></div>
<?php
        $editor = ob_get_clean();
        return $editor;
    }

    /**
     * Return get_option value. If needs value from array $name should be like name[key]
     *
     * @param  string $name option key
     * @param  string $def default value if option does not exist
     * @return mixed
     */
    public function opts($name = '', $def = '')
    {
        $name = preg_replace('/\[|]/m', ' ', $name);
        $name = explode(' ', $name);
        if (isset($name[1])) {
            $default = [];
            $default[$name[1]] = $def;
            return isset(get_option($name[0], $default)[$name[1]]) ? get_option($name[0], $default)[$name[1]] : '';
        } else {
            return get_option($name, $def);
        }
    }

    /**
     * Format post data to table
     *
     * @param  string $post_type Post type
     * @param  array $columns columns with data
     * @param  array $args additional params
     * @return array
     */
    public function toTable($post_type = false, $columns = [], $args = [], $filter = [])
    {
        if (!$columns || !$post_type) return [];
        $posts_per_page = isset($args['posts_per_page']) ? (int)($args['posts_per_page']) : 3;
        $post_status = isset($args['post_status']) ? $args['post_status'] : 'publish';
        $paged = isset($args['paged']) ? (int)($args['paged']) : 1;
        $args = [
            'post_type' => $post_type,
            'post_status' => $post_status == 'publish' ? $post_status : ['draft', 'trash'],
            'posts_per_page' => $posts_per_page,
            'paged' => $paged
        ];
        if ($filter) {
            foreach ($filter as $fil) {
                $val = $fil['value'];
                switch ($fil['validation']) {
                    case 'number':
                        $val = (int)($val);
                        break;
                    default:
                        $val = sanitize_text_field($val);
                }
                switch ($fil['type']) {
                    case 'meta':
                        if (!isset($args['meta_query'])) {
                            $args['meta_query'] = [];
                        }
                        $args['meta_query'][] = [
                            'key' => sanitize_key($fil['filter']),
                            'value' => $val
                        ];
                        break;
                }
            }
        }
        $posts = new WP_Query($args);
        $isLastPage = $paged * $posts_per_page > $posts->found_posts;
        if ($posts->have_posts()) {
            $ans = [];
            while ($posts->have_posts()) {
                $posts->the_post();
                $tr = [];
                if (!isset($columns['post_status'])) {
                    $columns['post_status'] = [
                        'col' => 'post_status',
                        'name' => __('Post Status', 'oz-donator'),
                        'hidden' => true
                    ];
                }
                foreach ($columns as $key => $column) {
                    $val = '';
                    $type = isset($column['type']) ? $column['type'] : '';
                    switch ($type) {
                        case '':
                            switch ($column['col']) {
                                case 'ID':
                                    $val = get_the_ID();
                                    break;
                                case 'post_title':
                                    $val = get_the_title();
                                    break;
                                case 'post_status':
                                    $val = get_post_status();
                                    break;
                                case 'post_date':
                                    $val = get_the_date(get_option('date_format') . ' h:i a');
                                    break;
                            }
                            break;
                        case 'meta':
                            $val = get_post_meta(get_the_ID(), sanitize_key($column['col']), true);
                            break;
                        case 'posts':
                            $post_type = sanitize_key($column['col']);
                            $args = [
                                'post_type' => $post_type,
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                                'meta_query' => [
                                    [
                                        'key' => sanitize_key($column['relation_meta']),
                                        'value' => get_the_ID(),
                                    ]
                                ]
                            ];
                            if (isset($column['args'])) {
                                if (isset($column['args']['meta_query'])) {
                                    $meta = array_merge($args['meta_query'], $column['args']['meta_query']);
                                    $column['args']['meta_query'] = $meta;
                                }
                                $args = array_merge($args, $column['args']);
                            }
                            $found_posts = get_posts($args);
                            $val = count($found_posts);
                            break;
                        default:
                            $val = '';
                    }
                    $tr[sanitize_key($key)] = apply_filters(static::PREFIX . 'column_value', $val, $column);
                }

                $ans[] = $tr;
            }
            return [
                'table' => $ans,
                'data' => [
                    'isLastPage' => $isLastPage,
                    'found' => $posts->found_posts
                ]
            ];
        }
        return [];
    }
    
    /**
     * Fromat user data to table
     *
     * @param  string $role User rolw
     * @param  array $columns Table columns
     * @param  array $args
     * @param  array $filter filter posts by this filter
     * @return array [table, data]
     */
    public function UsersToTable($role = false, $columns = [], $args = [], $filter = [])
    {
        if (!$columns || !$role) return [];
        $posts_per_page = isset($args['posts_per_page']) ? (int)($args['posts_per_page']) : 3;
        $paged = isset($args['paged']) ? (int)($args['paged']) : 1;
        $args1 = [
            'role__in' => $role ? [$role] : [],
            //'post_status' => $post_status == 'publish' ? $post_status : ['draft', 'trash'],
            'number' => $posts_per_page,
            'orderby' => isset($args['orderby']) ? sanitize_text_field($args['orderby']) : 'ID',
            'order' => isset($args['order']) && $args['order'] == 'ASC' ? 'ASC' : 'DESC',
            'paged' => $paged
        ];
        $users = new WP_User_Query($args1);
        $results = $users->get_results();
        $count = $users->get_total();
        $isLastPage = $paged * $posts_per_page > $count;
        $ans = [];
        if (!empty($results)) {
            foreach ($results as $result) {
                $tr = [];
                foreach ($columns as $key => $column) {
                    $type = isset($column['type']) ? $column['type'] : '';
                    $val = '';
                    switch ($type) {
                        case '':
                            switch ($column['col']) {
                                case 'ID':
                                    $val = $result->ID;
                                    break;
                                case 'user_login':
                                    $val = $result->user_login;
                                    break;
                                case 'user_email':
                                    $val = $result->user_email;
                                    break;
                                default:
                                    $val = $result->has_prop($column['col']) ? $result->get($column['col']) : '';
                            }
                            break;
                        case 'meta':
                            $val = get_user_meta($result->ID, sanitize_key($column['col']), true);
                            break;
                        case 'posts':
                            $post_type = sanitize_key($column['col']);
                            $args = [
                                'post_type' => $post_type,
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                                'meta_query' => [
                                    [
                                        'key' => sanitize_key($column['relation_meta']),
                                        'value' => $result->ID,
                                    ]
                                ]
                            ];
                            if (isset($column['args'])) {
                                if (isset($column['args']['meta_query'])) {
                                    $meta = array_merge($args['meta_query'], $column['args']['meta_query']);
                                    $column['args']['meta_query'] = $meta;
                                }
                                $args = array_merge($args, $column['args']);
                            }
                            $found_posts = get_posts($args);
                            $val = count($found_posts);
                            break;
                        default:
                            $val = '';
                    }
                    $tr[sanitize_key($key)] = apply_filters(static::PREFIX . 'user_column_value', $val, $column);
                }
                $ans[] = $tr;
            }
            return [
                'table' => $ans,
                'data' => [
                    'isLastPage' => $isLastPage,
                    'found' => $count
                ]
            ];
        }
        return $ans;
    }

    /**
     * Return WP Posts for table interface
     *
     * @return void
     */
    public function getTable()
    {
        if ($this->canDoThis()) {
            $columns = isset($_POST['columns']) ? json_decode(stripslashes($_POST['columns']), 1) : [];
            $args = isset($_POST['args']) ? json_decode(stripslashes($_POST['args']), 1) : [];
            $filter = isset($_POST['filter']) ? json_decode(stripslashes($_POST['filter']), 1) : [];
            $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
            $users_role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
            if ($post_type)
                $table = $this->toTable($post_type, $columns, $args, $filter);
            else if ($users_role)
                $table = $this->UsersToTable($users_role, $columns, $args, $filter);
            echo json_encode([
                'success' => !empty($table),
                'payload' => $table
            ]);
        }
        wp_die();
    }

    /**
     * Find nested array by key
     *
     * @param  string $search Key name
     * @param  array $lists Array where need to search
     * @param  array $conditions additional params
     * @param  array $next_key next array on the same level
     * @return array|false
     */
    public static function findNestedArrayByKey($search, $lists = [], $conditions = [], $next_key = [])
    {
        if (!is_array($lists)) return false;
        $i = 0;
        foreach (array_keys($lists) as $value) {

            if ($value !== $search) {
                if (is_array($lists[$value])) {
                    $next_key = isset(array_keys($lists)[$i + 1]) ? $lists[array_keys($lists)[$i + 1]] : [];
                    $ans = self::findNestedArrayByKey($search, $lists[$value], $conditions, $next_key);
                    if (!$ans) continue;
                    else return $ans;
                } else {
                    continue;
                }
            } else {
                if (is_array($lists[$value])) {
                    if (empty($conditions)) {
                        return $lists[$value];
                    } else {
                        $matches = 0;
                        foreach (array_keys($conditions) as $key) {
                            if (isset($lists[$value][$key]) && $lists[$value][$key] == $conditions[$key]) {
                                $matches++;
                            }
                        }
                        if ($matches == count($conditions)) {
                            return $lists[$value];
                        } else {
                            // search on same level but in next array
                            //return !empty($next_key) ?  self::findNestedArrayByKey($search, $next_key, $conditions) : false;
                            //$ans = self::findNestedArrayByKey($search, $next_key, $conditions);
                            continue;
                        }
                    }
                }
                return is_array($lists[$value]) ? $lists[$value] : false;
            }
            $i++;
        }

        return false;
    }

    /**
     * Do not show field on edit post form if does not meet conditions
     *
     * @param  array $field current edit field
     * @param  array $fields all edit fields
     * @return bool
     */
    public function IsMeetsCondition($field, $fields)
    {
        global $post;
        $conditions = $field['condition'];
        $matches = 0;
        foreach ($conditions as $condition) {
            $fil = array_filter($fields, function ($val) use ($condition, &$matches, $post) {
                if (isset($val['name']) && $val['name'] == $condition['key']) {
                    $val = $this->getValue($val, $post);
                    if ($val == $condition['value']) {
                        $matches++;
                    }
                }
            });
        }
        return $matches == count($conditions);
    }

    /**
     * Fill in edit fields
     *
     * @param  WP_Post $one_post Post 
     * @param  string $post_type Post type
     * @return array
     */
    public function toPost($one_post, $post_type)
    {
        if (!$one_post || !$post_type) return false;
        $fields = self::findNestedArrayByKey('view', $this->setOptions(), ['post_type' => $post_type]);
        global $post;
        $post = $one_post;
        setup_postdata($post);
        if ($post_type == $fields['post_type']) {
            foreach ($fields['edit_post'] as $keys => &$edit_post) {
                if ($edit_post['fields']) {
                    foreach ($edit_post['fields'] as $key => $field) {
                        if ($field['type'] != 'html') {
                            if (isset($edit_post['fields'][$key]['condition']) && !empty($edit_post['fields'][$key]['condition'])) {
                                $arr = [];
                                $arr = array_column($fields['edit_post'], 'fields');
                                $arr = array_merge(...$arr);
                                if (!$this->IsMeetsCondition($edit_post['fields'][$key], $arr)) {
                                    continue;
                                }
                            }
                            $edit_post['fields'][$key]['value'] = apply_filters(static::PREFIX . 'edit_post_value', $this->getValue($field, $post), $field);
                            //$edit_post['fields'][$key]['value'] = $key;
                        }
                    }
                }
            }
            return $fields['edit_post'];
        }
        wp_reset_postdata();
        return $fields['edit_post'];
    }
    
    /**
     * Fill in edit fields for user
     *
     * @param  string $users_role User role
     * @param  int $user_id User ID
     * @return array
     */
    public function UserToPost($users_role, $user_id)
    {
        $fields = self::findNestedArrayByKey('view', $this->setOptions(), ['role' => $users_role]);
        $user = get_user_by('ID', $user_id);
        if ($users_role == $fields['role']) {
            foreach ($fields['edit_post'] as $keys => &$edit_post) {
                if ($edit_post['fields']) {
                    foreach ($edit_post['fields'] as $key => $field) {
                        if ($field['type'] != 'html') {
                            if (isset($edit_post['fields'][$key]['condition']) && !empty($edit_post['fields'][$key]['condition'])) {
                                $arr = [];
                                $arr = array_column($fields['edit_post'], 'fields');
                                $arr = array_merge(...$arr);
                                if (!$this->IsMeetsCondition($edit_post['fields'][$key], $arr)) {
                                    continue;
                                }
                            }
                            $edit_post['fields'][$key]['value'] = apply_filters(static::PREFIX . 'edit_user_value', $this->getUserValue($field, $user), $field);
                            //$edit_post['fields'][$key]['value'] = $key;
                        }
                    }
                }
            }
            return $fields['edit_post'];
        }
        return $fields['edit_post'];
    }

    /**
     * Echoing json array with post data
     *
     * @return void
     */
    public function getPost()
    {
        if ($this->canDoThis()) {
            $post_id = isset($_POST['ID']) ? (int)($_POST['ID']) : 0;
            $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 0;
            $users_role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
            if ($post_type) {
                global $post;
                $post = get_post($post_id);
                $payload = $this->toPost($post, $post_type);
            } else if ($users_role) {
                $payload = $this->UserToPost($users_role, $post_id);
            }
            echo json_encode([
                'success' => true,
                'payload' => $payload,
            ]);
        }
        wp_die();
    }

    /**
     * Save post and echoing json array with saving results
     *
     * @return void
     */
    public function savePost()
    {
        if ($this->canDoThis()) {

            $data = $_POST['payload'] ? json_decode(stripslashes($_POST['payload']), 1) : [];
            $this->data = [];
            $this->payload = false;
            foreach ($data as $dat) {
                $this->name = $dat['name'];
                $this->value = $dat['value'];
                $this->type = isset($dat['data_validation']) ? $dat['data_validation'] : '';
                $this->field_type = isset($dat['data_type']) ? $dat['data_type'] : 'main';
                $this->sanitizingAndProcessing();
            }

            $this->data['post_type'] = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
            $this->data['post_status'] = 'publish';

            if (!isset($this->data['post_title']) || !$this->data['post_title']) {
                $this->data['post_title'] = wp_hash(time());
            }

            $id = wp_insert_post(apply_filters('ozdon_pre_data', $this->data), true);

            echo json_encode([
                'success' => !is_wp_error($id),
                'payload' => is_wp_error($id) ? $id->get_error_message() : $id,
            ]);
        }
        wp_die();
    }

    /**
     * Delete post and echoing json array with deleting results
     *
     * @return void
     */
    public function deletePost()
    {
        if ($this->canDoThis()) {
            $id = isset($_POST['ID']) ? (int)($_POST['ID']) : 0;
            if ($id) {
                $delete = wp_trash_post($id);
                echo json_encode([
                    'success' => !empty($delete) && $delete != false,
                    'payload' => $delete,
                ]);
            }
        }
        wp_die();
    }

    /**
     * Return post from trash and echoing json array with results
     *
     * @return void
     */
    public function restorePost()
    {
        if ($this->canDoThis()) {
            $id = isset($_POST['ID']) ? (int)($_POST['ID']) : 0;
            if ($id) {
                add_filter('wp_untrash_post_status', [$this, 'returnPublish']);
                $delete = wp_untrash_post($id);
                remove_filter('wp_untrash_post_status', [$this, 'returnPublish']);
                echo json_encode([
                    'success' => !empty($delete) && $delete != false,
                    'payload' => $delete,
                ]);
            }
        }
        wp_die();
    }

    /**
     * Filter post status to publish when wp_untrash_post function work
     *
     * @param  mixed $status
     * @return void
     */
    public function returnPublish($status)
    {
        return 'publish';
    }

    /**
     * Sanitizing and processing input data
     *
     * @return void
     */
    public function sanitizingAndProcessing()
    {
        // sanitizing
        switch ($this->type) {
            case 'boolean':
                $this->value = $this->value == 'true';
                break;
            case 'int':
                $this->value = (int)($this->value);
                break;
            default:
                $this->value = sanitize_text_field($this->value);
        }

        if (isset($this->post_id)) {
            $this->data['ID'] = $this->post_id;
            $old_post = get_post($this->post_id);
            if ($old_post)
                $this->data['post_status'] = $old_post->post_status;
            $this->data['post_type'] = $old_post->post_type;
        }
        switch ($this->field_type) {
            case 'main':
                switch ($this->name) {
                    case 'post_title':
                        $this->data['post_title'] = $this->value;
                        break;
                }
                break;
            case 'meta':
                if (isset($this->post_id) && $this->post_id) :
                    update_post_meta($this->post_id, $this->name, $this->value);
                    $this->payload = true;
                endif;
                $this->data['meta_input'][$this->name] = $this->value;
                break;
        }
    }

    /**
     * Save post data
     *
     * @return void
     */
    public function savePostData()
    {
        if ($this->canDoThis()) {
            $this->post_id = isset($_POST['ID']) ? (int)($_POST['ID']) : 0;
            $this->name = isset($_POST['name']) ? sanitize_key($_POST['name']) : '';
            $this->value = isset($_POST['value']) ? $_POST['value'] : '';
            $this->type = isset($_POST['type']) ? $_POST['type'] : '';
            $this->field_type = isset($_POST['data_type']) ? sanitize_text_field($_POST['data_type']) : 'main';
            $this->payload = false;
            $this->data = [];


            $this->sanitizingAndProcessing();



            $id = !$this->payload ? wp_insert_post(apply_filters('ozdon_pre_data', $this->data), true) : $this->payload;
            echo json_encode([
                'success' => !is_wp_error($id),
                'payload' => !is_wp_error($id) ? $id : $id->get_error_message(),
            ]);
        }
        wp_die();
    }

    /**
     * getValue
     *
     * @param  array $field Field
     * @param  WP_Post $post Post
     * @return mixed
     */
    public function getValue($field, $post)
    {
        switch ($field['name']) {
            case 'ID':
                $val = get_the_ID();
                break;
            case 'post_title':
                $val = get_the_title();
                break;
            case 'post_date':
                $val = get_the_date();
                break;
            default:
                $val = get_post_meta(get_the_ID(), $field['name'], true);
        }
        $field['value'] = $val ?: $field['value'];

        return $field['value'];
    }

    /**
     * getValue
     *
     * @param  array $field Field
     * @param  WP_Post $post Post
     * @return mixed
     */
    public function getUserValue($field, $user)
    {
        $field['value'] = $user->has_prop($field['name']) ? $user->get($field['name']) : '';

        return $field['value'];
    }

    /**
     * todo make async select fields
     *
     * @return void
     */
    public function search()
    {
        if ($this->canDoThis()) {
            $type = isset($_POST['type']) ? $_POST['type'] : '';
            $word = isset($_POST['word']) ? $_POST['word'] : '';
            $ans = [];
            $query = false;
            switch ($type) {
                case '':
                    break;
                default:
                    $query = [
                        's' => sanitize_text_field($word)
                    ];
                    $ans = $this->WP_Query_toSelect($query);
            }
            echo json_encode([
                'success' => true,
                'payload' => !empty($ans) && !is_wp_error($query) ? $ans : [],
            ]);
        }
        wp_die();
    }

    /**
     * todo search results to select field
     *
     * @param  mixed $query
     * @return void
     */
    private function WP_Query_toSelect($query)
    {
        $query = new WP_Query($query);
        $ans = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $ans[] = [
                    'label' => get_the_title(),
                    'value' => get_the_ID(),
                ];
            }
        }
        return $ans;
    }
}
