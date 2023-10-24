<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @copyright 2023 Ozplugin
 * @link      https://oz-plugin.com/
 * @ver       1.3
 */

namespace Ozplugin\Core;

use WP_Error;
use WP_Query;
use WP_User_Query;

/**
 * Settings structure
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
 *              // phpcs:ignore Generic.Commenting.Todo.TaskFound
 *              col: 2, // todo how it works?
 *              // phpcs:ignore Generic.Commenting.Todo.TaskFound
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
class Settings {
    /**
     * Version
     *
     * @var string
     */
    const VER = '1.3';
    /**
     * Prefix
     *
     * @var string
     */
    const PREFIX = 'oz_';
    /**
     * File
     *
     * @var string
     */
    const FILE = __FILE__;
    /**
     * Page name
     *
     * @var string
     */
    public $page_name = '';
    /**
     * Page capability
     *
     * @var int
     */
    public $page_capability = 10;
    /**
     * Menu slug
     *
     * @var string
     */
    public $menu_slug = '';
    /**
     * Page icon
     *
     * @var string
     */
    public $page_icon = 'dashicons-admin-settings';
    /**
     * Page priority
     *
     * @var int
     */
    public $page_priority = 10;
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {
        $this->registerHooks();
    }
    /**
     * Register WP hooks
     *
     * @return bool
     */
    protected function registerHooks() {
        return false;
    }

    /**
     * Creating WP settings page
     *
     * @param string $name       Name of the page. Required
     * @param string $capability Permission who can see this page. By default is administrator
     * @param string $menu_slug  The slug name to refer to this menu by. Should be unique for this menu page and only include lowercase alphanumeric, dashes, and underscores characters to be compatible with sanitize_key() .
     * @param string $icon       The URL to the icon to be used for this menu.
     *                           Pass a base64-encoded SVG using a data URI,
     *                           which will be colored to match the color
     *                           scheme. This should begin with
     *                           'data:image/svg+xml;base64,'. Pass the name
     *                           of a Dashicons helper class to use a font
     *                           icon, e.g. 'dashicons-chart-pie'. Pass 'none'
     *                           to leave div.wp-menu-image empty so an icon
     *                           can be added via CSS.
     *
     * @param  int    $priority   The position in the menu order this item should appear.
     * @return void|\WP_Error
     */
    public function add_page( $name, $capability = 'administrator', $menu_slug = '', $icon = 'dashicons-admin-settings', $priority = 10 ) {
        if ( ! $name ) {
			return new WP_Error('name_missed', 'Name of setings page is required');
        }
        $this->page_name       = $name;
        $this->page_capability = $capability;
        $this->menu_slug       = $menu_slug ? $menu_slug : static::FILE;
        $this->page_icon       = $icon;
        $this->page_priority   = $priority;
        add_action('admin_menu', array( $this, 'add_menu_page' ));
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
            array( $this, 'page' ),
            $this->page_icon,
            $this->page_priority
        );
    }


    /**
     * Output html code for render settings page
     *
     * @return void
     */
    public function page() {
        $styles     = array(
            'position'   => 'fixed',
            'z-index'    => '99999',
            'top'        => '0',
            'left'       => '0',
            'width'      => '100%',
            'height'     => '100%',
            'overflow'   => 'auto',
            'background' => '#f9fbfe',
        );
        $styles_css = '';
        foreach ( $styles as $key => $style ) {
            $styles_css .= "$key:$style;";
        }
        echo '<div style="' . esc_attr($styles_css) . '" class="ozplugin_settings_page" id="' . esc_attr(static::PREFIX) . 'admin_page"></div>';
    }

    /**
     * Return all options as array
     *
     * @return array
     */
    final public function getOptions() {
        $options = array();
        $options = array_merge($options, $this->setOptions());
        $pages   = $options['pages'];
        array_multisort($pages, SORT_NUMERIC, array_column($pages, 'order'));
        $options['pages'] = $pages;
        //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
        return apply_filters(static::PREFIX . 'plugin_settings', $options);
    }

    /**
     * Return true if user can work via ajax
     *
     * @return bool
     */
    protected function canDoThis() {
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
     *
     * @return array
     */
    /**
     * Set options from child classes
     *
     * @return array
     */
    public function setOptions() {
        return array();
    }

    /**
     * Register ajax hook to save option
     *
     * @return void
     */
    public function registerAjaxHook() {
        add_action('wp_ajax_ozplugin_save_option', array( $this, 'save' ));
        add_action('wp_ajax_ozplugin_get_table', array( $this, 'getTable' ));
        add_action('wp_ajax_ozplugin_get_post', array( $this, 'getPost' ));
        add_action('wp_ajax_ozplugin_save_post_data', array( $this, 'savePostData' ));
        add_action('wp_ajax_ozplugin_save_post', array( $this, 'savePost' ));
        add_action('wp_ajax_ozplugin_delete_post', array( $this, 'deletePost' ));
        add_action('wp_ajax_ozplugin_restore_post', array( $this, 'restorePost' ));
        //phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        // add_action('wp_ajax_ozplugin_search', [$this, 'search']); // todo make async select fields.
    }

    /**
     * Save option. Expect $_POST array with option name, value, type keys. Echoing json with result of saving
     *
     * @return void
     */
    public function save() {
        if ( $this->canDoThis() ) {
            //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            if ( apply_filters('ozplugin_can_save_settings', true) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $name = sanitize_text_field($_POST['name']);
                // if option is array. for each key in array like option_name[suboption][subsuboption].
                preg_match('/^([a-zA-Z0-9_]+)(\[[a-zA-Z0-9_]+\])(\[[a-zA-Z0-9_]+\])?(\[[a-zA-Z0-9_]+\])?/', $name, $iSaArrayName);
                $key    = '';
                $values = array();
                if ( $iSaArrayName && count($iSaArrayName) > 2 ) {
                    $key = $iSaArrayName[1];
                }
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $value = $_POST['value'];
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'string';
                switch ( $type ) {
					case 'number':
						$value = (int) ( $value );
                        break;
					case 'object':
						$json_decoded = json_decode(stripslashes($value), 1);
						if ( $json_decoded && is_array($json_decoded) ) {
							$value = $json_decoded;
							array_walk_recursive($value, 'Ozplugin\Utils::sanitize_json');
						}
						if ( $value ) {
							$value = is_array($value) ? $value : explode(',', $value);
                            // phpcs:ignore WordPress.Security.NonceVerification.Missing
							$mapping = isset($_POST['objectValuesType']) && 'number' == $_POST['objectValuesType'] ? 'intval' : 'sanitize_text_field';
							if ( ! $json_decoded ) {
								$value = array_map($mapping, $value);
							}
						}
                        break;
					case 'boolean':
						$value = 'true' == $value;
                        break;
					case 'html':
						add_filter('safe_style_css', array( $this, 'safe_styles' ));
						$post  = array_merge(
                            wp_kses_allowed_html('post'),
                            array(
								'body'   => array( 'class' => 1 ),
								'center' => array(
									'class' => 1,
									'style' => 1,
								),
								'head'   => array( 'class' => 1 ),
								'html'   => array( 'class' => 1 ),
								'meta'   => array(
									'charset'    => 1,
									'name'       => 1,
									'content'    => 1,
									'http-equiv' => 1,
								),
								'style'  => array(),
                            )
						);
						$value = wp_kses($value, $post);
						remove_filter('safe_style_css', array( $this, 'safe_styles' ));
                        break;
					default:
						$value = esc_html($value);
                }

                if ( $iSaArrayName && count($iSaArrayName) > 2 && $key ) {
                    $values = get_option($key);
                    if ( ! is_array($values) ) {
                        $values = array();
                    }
                    if ( count($iSaArrayName) == 3 && $iSaArrayName[2] ) {
                        $k = str_replace(array( '[', ']' ), '', $iSaArrayName[2]);
                        if ( ! isset($values[ $k ]) ) {
                            $values[ $k ] = '';
                        }
                        $values[ $k ] = $value;
                    } elseif ( count($iSaArrayName) == 4 && $iSaArrayName[3] ) {
                        $k = str_replace(array( '[', ']' ), '', $iSaArrayName[2]);
                        $t = str_replace(array( '[', ']' ), '', $iSaArrayName[3]);
                        if ( ! isset($values[ $k ]) ) {
                            $values[ $k ] = array();
                        }
                        if ( ! isset($values[ $k ][ $t ]) ) {
                            $values[ $k ][ $t ] = '';
                        }
                        $values[ $k ][ $t ] = $value;
                    }
                    $value = $values;
                    $name  = $key;
                }
                $old_option = get_option($name);
                if ( maybe_serialize($value) == maybe_serialize($old_option) ) {
                    $res = array(
                        'success' => true,
                        'value'   => '',
                        'text'    => 'There are no changes',
                    );
                } else {
                    // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $suc = $name && isset($_POST['value']) ? update_option($name, $value) : false;
                    $res = array(
                        // phpcs:ignore WordPress.Security.NonceVerification.Missing
                        'success' => $name && isset($_POST['value']) && $suc,
                        'value'   => $value,
                        'text'    => ! $suc ? 'Error with saving' : '',
                    );
                }
            } else {
                $res = array(
                    'success' => false,
                    'text'    => __('You do not have enough permissions to change the settings', 'ozplugin-settings'),
                );
            }
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
            do_action(static::PREFIX . 'on_option_saved', $res);
            echo ( wp_json_encode($res) );
        }
        wp_die();
    }

    /**
     * Sanitize json array.
     *
     * @param  mixed $item Item
     * @param  mixed $key Key
     * @return void
     */
    public static function sanitize_json( &$item, $key ) {
        if ( is_numeric($item) ) {
            $item = floatval($item);
        } elseif ( 'true' == $item || 'false' == $item || ! $item ) {
            $item = boolval($item);
        } elseif ( $item && 'values' == $key ) {
                $item = $item; // todo how to sanitize it but skip \n symbols.
		} else {
			$item = sanitize_text_field($item);
        }
    }

    /**
     * Enqueue script (React) to render settings page.
     *
     * @return void
     */
    public function enqueue_scripts() {
        add_action('admin_enqueue_scripts', array( $this, 'scripts' ));
    }

    /**
     * Enqueuу script and options
     *
     * @return void
     */
    public function scripts() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : false;
        $base   = $screen && $screen->base ? str_replace('toplevel_page_', '', $screen->base) : '';
        if ( $base && strpos($this->menu_slug, '.php') !== false ) {
            $base = $base . '.php';
        }
        //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
        if ( $base && wp_normalize_path($base) == wp_normalize_path($this->menu_slug) || apply_filters(static::PREFIX . 'enqueue_settings_scripts', false) ) {
            $abs_url = wp_normalize_path(__DIR__);
            $ABSPATH = wp_normalize_path(ABSPATH);
            $abs_url = str_replace($ABSPATH, '', $abs_url);
            wp_enqueue_script(
                static::PREFIX . 'settings',
                site_url('/') . $abs_url . '/assets/js/admin.js',
                array(),
                static::VER,
                true
            );
            $lang = $this->getStrings();
            $vars = array(
                'logo'           => array(
                    'img' => wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full'),
                    'url' => get_admin_url(),
                ),
                'adminAjax'      => admin_url('admin-ajax.php'),
                'adminURL'       => get_admin_url(),
                'nonce'          => wp_create_nonce('ozplugin-nonce'),
                'settings'       => $this->getOptions(),
                'customNotice'   => array(
                    'variant' => 'warning',
                    'text'    => 'custom notice',
                ),
                'datetimeFormat' => 'MM/dd/yy hh:mm a',
            );
            //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
            wp_localize_script(static::PREFIX . 'settings', 'ozplugin_vars', apply_filters(static::PREFIX . 'plugin_vars', $vars));
            //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
            wp_localize_script(static::PREFIX . 'settings', 'ozplugin_lang', apply_filters(static::PREFIX . 'plugin_lang', $lang));
        }
    }

    /**
     * Genereate select option from array
     *
     * @param  array $arr key => value
     * @return array
     */
    public static function arrayToSelect( $arr = array() ) {
        $options = array();
        if ( is_array($arr) ) {
            foreach ( $arr as $label => $ar ) {
                $options[] = array(
                    'label' => $label,
                    'value' => $ar,
                );
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
    public function getStrings() {
        return array(
            'backtowp'           => __('Back to WP', 'ozplugin-settings'),
            'wrongpagetype'      => __('Wrong page type. Please choose page type to view this page', 'ozplugin-settings'),
            'nosettingsthistab'  => __('No settings in this tab.', 'ozplugin-settings'),
            'copied'             => __('Copied', 'ozplugin-settings'),
            'addnew'             => __('Add New', 'ozplugin-settings'),
            'somethingwentwrong' => __('Something went wrong', 'ozplugin-settings'),
        );
    }

    /**
     * Render wp_editor as option type
     *
     * @param  string $text        default editor value
     * @param  string $option_name Option name
     * @param  string $tpl_name Template name
     * @return string html code
     */
    public static function Editor( $text = '', $option_name = '', $tpl_name = '' ) {
        $hash = wp_hash($option_name);
        ob_start();
        wp_editor(
            $text,
            $hash,
            array(
                'textarea_name' => $option_name,
                'editor_height' => 425,
                'wpautop'       => false,
                'tinymce'       => array(
                    'forced_root_block' => false,
                    //phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                    // 'valid_elements' => '*[*]',
                    //phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                    // 'valid_elements' => 'head,html,body,meta,img[class=myclass|!src|border:0|alt|title|width|height|style]',
                ),
                'editor_css'    => 0,
                'editor_class'  => 'ozplugin_editor',
            )
        );
        ?>
        <div data-option="<?php echo esc_attr($option_name); ?>" data-id="<?php echo esc_attr($hash); ?>" data-name="<?php echo esc_attr($tpl_name); ?>" class="oz_set_defemail btn btn-primary btn-sm my-2"><?php esc_attr_e('Load default template', 'ozplugin-settings'); ?></div>
        <?php
        $editor = ob_get_clean();
        return $editor;
    }

    /**
     * Return get_option value. If needs value from array $name should be like name[key]
     *
     * @param  string $name option key
     * @param  string $def  default value if option does not exist
     * @return mixed
     */
    public function opts( $name = '', $def = '' ) {
        $name = preg_replace('/\[|]/m', ' ', $name);
        $name = explode(' ', $name);
        if ( isset($name[1]) ) {
            $default             = array();
            $default[ $name[1] ] = $def;
            return isset(get_option($name[0], $default)[ $name[1] ]) ? get_option($name[0], $default)[ $name[1] ] : '';
        } else {
            return get_option($name, $def);
        }
    }

    /**
     * Format post data to table
     *
     * @param  string $post_type Post type
     * @param  array  $columns   columns with data
     * @param  array  $args      additional params
     * @param  array  $filter      Additional meta query
     * @return array
     */
    public function toTable( $post_type = false, $columns = array(), $args = array(), $filter = array() ) {
        if ( ! $columns || ! $post_type ) {
			return array();
        }
        $posts_per_page = isset($args['posts_per_page']) ? (int) ( $args['posts_per_page'] ) : 3;
        $post_status    = isset($args['post_status']) ? $args['post_status'] : 'publish';
        $paged          = isset($args['paged']) ? (int) ( $args['paged'] ) : 1;
        $args           = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish' == $post_status ? $post_status : array( 'draft', 'trash' ),
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
        );
        if ( $filter ) {
            foreach ( $filter as $fil ) {
                $val = $fil['value'];
                switch ( $fil['validation'] ) {
					case 'number':
						$val = (int) ( $val );
                        break;
					default:
						$val = sanitize_text_field($val);
                }
                switch ( $fil['type'] ) {
					case 'meta':
						if ( ! isset($args['meta_query']) ) {
							$args['meta_query'] = array();
						}
						$args['meta_query'][] = array(
							'key'   => sanitize_key($fil['filter']),
							'value' => $val,
						);
                        break;
                }
            }
        }
        $posts      = new WP_Query($args);
        $isLastPage = $paged * $posts_per_page > $posts->found_posts;
        if ( $posts->have_posts() ) {
            $ans = array();
            while ( $posts->have_posts() ) {
                $posts->the_post();
                $tr = array();
                if ( ! isset($columns['post_status']) ) {
                    $columns['post_status'] = array(
                        'col'    => 'post_status',
                        'name'   => __('Post Status', 'ozplugin-settings'),
                        'hidden' => true,
                    );
                }
                foreach ( $columns as $key => $column ) {
                    $val  = '';
                    $type = isset($column['type']) ? $column['type'] : '';
                    switch ( $type ) {
						case '':
							switch ( $column['col'] ) {
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
							$args      = array(
								'post_type'      => $post_type,
								'post_status'    => 'publish',
								'posts_per_page' => -1,
								'meta_query'     => array(
									array(
										'key'   => sanitize_key($column['relation_meta']),
										'value' => get_the_ID(),
									),
								),
							);
							if ( isset($column['args']) ) {
								if ( isset($column['args']['meta_query']) ) {
									$meta                         = array_merge($args['meta_query'], $column['args']['meta_query']);
									$column['args']['meta_query'] = $meta;
								}
								$args = array_merge($args, $column['args']);
							}
							$found_posts = get_posts($args);
							$val         = count($found_posts);
                            break;
						default:
							$val = '';
                    }
                    //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
                    $tr[ sanitize_key($key) ] = apply_filters(static::PREFIX . 'column_value', $val, $column);
                }

                $ans[] = $tr;
            }
            return array(
                'table' => $ans,
                'data'  => array(
                    'isLastPage' => $isLastPage,
                    'found'      => $posts->found_posts,
                ),
            );
        }
        return array();
    }

    /**
     * Format user data to table
     *
     * @param  string $role    User rolw
     * @param  array  $columns Table columns
     * @param  array  $args Args
     * @return array [table, data]
     */
    public function UsersToTable( $role = false, $columns = array(), $args = array() ) {
        if ( ! $columns || ! $role ) {
			return array();
        }
        $posts_per_page = isset($args['posts_per_page']) ? (int) ( $args['posts_per_page'] ) : 3;
        $paged          = isset($args['paged']) ? (int) ( $args['paged'] ) : 1;
        $args1          = array(
            'role__in' => $role ? array( $role ) : array(),
            //phpcs:ignore Squiz.PHP.CommentedOutCode.Found
            // 'post_status' => $post_status == 'publish' ? $post_status : ['draft', 'trash'],
            'number'   => $posts_per_page,
            'orderby'  => isset($args['orderby']) ? sanitize_text_field($args['orderby']) : 'ID',
            'order'    => isset($args['order']) && 'ASC' == $args['order'] ? 'ASC' : 'DESC',
            'paged'    => $paged,
        );
        $users      = new WP_User_Query($args1);
        $results    = $users->get_results();
        $count      = $users->get_total();
        $isLastPage = $paged * $posts_per_page > $count;
        $ans        = array();
        if ( ! empty($results) ) {
            foreach ( $results as $result ) {
                $tr = array();
                foreach ( $columns as $key => $column ) {
                    $type = isset($column['type']) ? $column['type'] : '';
                    $val  = '';
                    switch ( $type ) {
						case '':
							switch ( $column['col'] ) {
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
							$args      = array(
								'post_type'      => $post_type,
								'post_status'    => 'publish',
								'posts_per_page' => -1,
								'meta_query'     => array(
									array(
										'key'   => sanitize_key($column['relation_meta']),
										'value' => $result->ID,
									),
								),
							);
							if ( isset($column['args']) ) {
								if ( isset($column['args']['meta_query']) ) {
									$meta                         = array_merge($args['meta_query'], $column['args']['meta_query']);
									$column['args']['meta_query'] = $meta;
								}
								$args = array_merge($args, $column['args']);
							}
							$found_posts = get_posts($args);
							$val         = count($found_posts);
                            break;
						default:
							$val = '';
                    }
                    //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
                    $tr[ sanitize_key($key) ] = apply_filters(static::PREFIX . 'user_column_value', $val, $column);
                }
                $ans[] = $tr;
            }
            return array(
                'table' => $ans,
                'data'  => array(
                    'isLastPage' => $isLastPage,
                    'found'      => $count,
                ),
            );
        }
        return $ans;
    }

    /**
     * Return WP Posts for table interface
     *
     * @return void
     */
    public function getTable() {
        if ( $this->canDoThis() ) {
            //phpcs:disable WordPress.Security.NonceVerification.Missing
            $columns    = isset($_POST['columns']) ? json_decode(stripslashes($_POST['columns']), 1) : array();
            $args       = isset($_POST['args']) ? json_decode(stripslashes($_POST['args']), 1) : array();
            $filter     = isset($_POST['filter']) ? json_decode(stripslashes($_POST['filter']), 1) : array();
            $post_type  = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
            $users_role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
            //phpcs:enable
            if ( $post_type ) {
                $table = $this->toTable($post_type, $columns, $args, $filter);
            } elseif ( $users_role ) {
                $table = $this->UsersToTable($users_role, $columns, $args, $filter);
            }
            echo wp_json_encode(
                array(
					'success' => ! empty($table),
					'payload' => $table,
                )
            );
        }
        wp_die();
    }

    /**
     * Find nested array by key
     *
     * @param  string $search     Key name
     * @param  array  $lists      Array where need to search
     * @param  array  $conditions additional params
     * @param  array  $next_key   next array on the same level
     * @return array|false
     */
    public static function findNestedArrayByKey( $search, $lists = array(), $conditions = array(), $next_key = array() ) {
        if ( ! is_array($lists) ) {
			return false;
        }
        $i = 0;
        foreach ( array_keys($lists) as $value ) {

            if ( $value !== $search ) {
                if ( is_array($lists[ $value ]) ) {
                    $next_key = isset(array_keys($lists)[ $i + 1 ]) ? $lists[ array_keys($lists)[ $i + 1 ] ] : array();
                    $ans      = self::findNestedArrayByKey($search, $lists[ $value ], $conditions, $next_key);
                    if ( ! $ans ) {
						continue;
                    } else {
						return $ans;
                    }
                } else {
                    continue;
                }
            } else {
                if ( is_array($lists[ $value ]) ) {
                    if ( empty($conditions) ) {
                        return $lists[ $value ];
                    } else {
                        $matches = 0;
                        foreach ( array_keys($conditions) as $key ) {
                            if ( isset($lists[ $value ][ $key ]) && $lists[ $value ][ $key ] == $conditions[ $key ] ) {
                                ++$matches;
                            }
                        }
                        if ( count($conditions) == $matches ) {
                            return $lists[ $value ];
                        } else {
                            // phpcs:disable Squiz
                            // search on same level but in next array.
                            // return !empty($next_key) ?  self::findNestedArrayByKey($search, $next_key, $conditions) : false;
                            // $ans = self::findNestedArrayByKey($search, $next_key, $conditions);
                            // phpcs:enable
                            continue;
                        }
                    }
                }
                return is_array($lists[ $value ]) ? $lists[ $value ] : false;
            }
            ++$i;
        }

        return false;
    }

    /**
     * Do not show field on edit post form if does not meet conditions
     *
     * @param  array $field  current edit field
     * @param  array $fields all edit fields
     * @return bool
     */
    public function IsMeetsCondition( $field, $fields ) {
        global $post;
        $conditions = $field['condition'];
        $matches    = 0;
        foreach ( $conditions as $condition ) {
            $fil = array_filter(
                $fields,
                function ( $val ) use ( $condition, &$matches, $post ) {
                    if ( isset($val['name']) && $val['name'] == $condition['key'] ) {
                        $val = $this->getValue($val, $post);
                        if ( $val == $condition['value'] ) {
                            $matches++;
                        }
                    }
                }
            );
        }
        return count($conditions) == $matches;
    }

    /**
     * Fill in edit fields
     *
     * @param  WP_Post $one_post  Post
     * @param  string  $post_type Post type
     * @return array
     */
    public function toPost( $one_post, $post_type ) {
        if ( ! $one_post || ! $post_type ) {
			return false;
        }
        $fields = self::findNestedArrayByKey('view', $this->setOptions(), array( 'post_type' => $post_type ));
        global $post;
        //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $post = $one_post;
        setup_postdata($post);
        if ( $post_type == $fields['post_type'] ) {
            foreach ( $fields['edit_post'] as $keys => &$edit_post ) {
                if ( $edit_post['fields'] ) {
                    foreach ( $edit_post['fields'] as $key => $field ) {
                        if ( 'html' !== $field['type'] ) {
                            if ( isset($edit_post['fields'][ $key ]['condition']) && ! empty($edit_post['fields'][ $key ]['condition']) ) {
                                $arr = array();
                                $arr = array_column($fields['edit_post'], 'fields');
                                $arr = array_merge(...$arr);
                                if ( ! $this->IsMeetsCondition($edit_post['fields'][ $key ], $arr) ) {
                                    continue;
                                }
                            }
                            //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
                            $edit_post['fields'][ $key ]['value'] = apply_filters(static::PREFIX . 'edit_post_value', $this->getValue($field, $post), $field);
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
     * @param  int    $user_id    User ID
     * @return array
     */
    public function UserToPost( $users_role, $user_id ) {
        $fields = self::findNestedArrayByKey('view', $this->setOptions(), array( 'role' => $users_role ));
        $user   = get_user_by('ID', $user_id);
        if ( $users_role == $fields['role'] ) {
            foreach ( $fields['edit_post'] as $keys => &$edit_post ) {
                if ( $edit_post['fields'] ) {
                    foreach ( $edit_post['fields'] as $key => $field ) {
                        if ( 'html' !== $field['type'] ) {
                            if ( isset($edit_post['fields'][ $key ]['condition']) && ! empty($edit_post['fields'][ $key ]['condition']) ) {
                                $arr = array();
                                $arr = array_column($fields['edit_post'], 'fields');
                                $arr = array_merge(...$arr);
                                if ( ! $this->IsMeetsCondition($edit_post['fields'][ $key ], $arr) ) {
                                    continue;
                                }
                            }
                            //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
                            $edit_post['fields'][ $key ]['value'] = apply_filters(static::PREFIX . 'edit_user_value', $this->getUserValue($field, $user), $field);
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
    public function getPost() {
        if ( $this->canDoThis() ) {
            //phpcs:disable WordPress.Security.NonceVerification.Missing
            $post_id    = isset($_POST['ID']) ? (int) ( $_POST['ID'] ) : 0;
            $post_type  = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 0;
            $users_role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
            //phpcs:enable
            if ( $post_type ) {
                global $post;
                //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                $post    = get_post($post_id);
                $payload = $this->toPost($post, $post_type);
            } elseif ( $users_role ) {
                $payload = $this->UserToPost($users_role, $post_id);
            }
            echo wp_json_encode(
                array(
					'success' => true,
					'payload' => $payload,
                )
            );
        }
        wp_die();
    }

    /**
     * Save post and echoing json array with saving results
     *
     * @return void
     */
    public function savePost() {
        if ( $this->canDoThis() ) {
            //phpcs:ignore WordPress.Security.NonceVerification.Missing
            $data          = $_POST['payload'] ? json_decode(stripslashes($_POST['payload']), 1) : array();
            $this->data    = array();
            $this->payload = false;
            foreach ( $data as $dat ) {
                $this->name       = $dat['name'];
                $this->value      = $dat['value'];
                $this->type       = isset($dat['data_validation']) ? $dat['data_validation'] : '';
                $this->field_type = isset($dat['data_type']) ? $dat['data_type'] : 'main';
                $this->sanitizingAndProcessing();
            }
            //phpcs:ignore WordPress.Security.NonceVerification.Missing
            $this->data['post_type']   = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
            $this->data['post_status'] = 'publish';

            if ( ! isset($this->data['post_title']) || ! $this->data['post_title'] ) {
                $this->data['post_title'] = wp_hash(time());
            }

            $id = wp_insert_post(apply_filters('ozdon_pre_data', $this->data), true);
            //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
            do_action(static::PREFIX . 'on_option_post_saved', $id);

            echo wp_json_encode(
                array(
					'success' => ! is_wp_error($id),
					'payload' => is_wp_error($id) ? $id->get_error_message() : $id,
                )
            );
        }
        wp_die();
    }

    /**
     * Delete post and echoing json array with deleting results
     *
     * @return void
     */
    public function deletePost() {
        if ( $this->canDoThis() ) {
            //phpcs:ignore WordPress.Security.NonceVerification.Missing
            $id = isset($_POST['ID']) ? (int) ( $_POST['ID'] ) : 0;
            if ( $id ) {
                $delete = wp_trash_post($id);
                echo wp_json_encode(
                    array(
						'success' => ! empty($delete) && false !== $delete,
						'payload' => $delete,
                    )
                );
            }
        }
        wp_die();
    }

    /**
     * Return post from trash and echoing json array with results
     *
     * @return void
     */
    public function restorePost() {
        if ( $this->canDoThis() ) {
            //phpcs:ignore WordPress.Security.NonceVerification.Missing
            $id = isset($_POST['ID']) ? (int) ( $_POST['ID'] ) : 0;
            if ( $id ) {
                add_filter('wp_untrash_post_status', array( $this, 'returnPublish' ));
                $delete = wp_untrash_post($id);
                remove_filter('wp_untrash_post_status', array( $this, 'returnPublish' ));
                echo wp_json_encode(
                    array(
						'success' => ! empty($delete) && false !== $delete,
						'payload' => $delete,
                    )
                );
            }
        }
        wp_die();
    }

    /**
     * Filter post status to publish when wp_untrash_post function work
     *
     * @param  mixed $status Status
     * @return string
     */
    public function returnPublish( $status ) {
        if ( $status ) {
			return $status;
        }
        return 'publish';
    }

    /**
     * Sanitizing and processing input data
     *
     * @return void
     */
    public function sanitizingAndProcessing() {
        switch ( $this->type ) {
			case 'boolean':
				$this->value = 'true' == $this->value;
                break;
			case 'int':
				$this->value = (int) ( $this->value );
                break;
			default:
				$this->value = sanitize_text_field($this->value);
        }

        if ( isset($this->post_id) ) {
            $this->data['ID'] = $this->post_id;
            $old_post         = get_post($this->post_id);
            if ( $old_post ) {
                $this->data['post_status'] = $old_post->post_status;
                $this->data['post_type']   = $old_post->post_type;
                $this->data['post_title']  = $old_post->post_title;
            }
        }
        switch ( $this->field_type ) {
			case 'main':
				switch ( $this->name ) {
					case 'post_title':
						$this->data['post_title'] = $this->value;
					    break;
				}
                break;
			case 'meta':
				if ( isset($this->post_id) && $this->post_id ) :
					update_post_meta($this->post_id, $this->name, $this->value);
                    //phpcs:ignore Squiz.PHP.CommentedOutCode.Found
					// $this->payload = true; // forgot why need this. possibly, don't trigger save_post if only post_meta changed.
				endif;
				$this->data['meta_input'][ $this->name ] = $this->value;
                break;
        }
    }

    /**
     * Save post data
     *
     * @return void
     */
    public function savePostData() {
        if ( $this->canDoThis() ) {
            //phpcs:disable WordPress.Security.NonceVerification.Missing
            $this->post_id    = isset($_POST['ID']) ? (int) ( $_POST['ID'] ) : 0;
            $this->name       = isset($_POST['name']) ? sanitize_key($_POST['name']) : '';
            $this->value      = isset($_POST['value']) ? $_POST['value'] : '';
            $this->type       = isset($_POST['type']) ? $_POST['type'] : '';
            $this->field_type = isset($_POST['data_type']) ? sanitize_text_field($_POST['data_type']) : 'main';
            //phpcs:enable
            $this->payload = false;
            $this->data    = array();

            $this->sanitizingAndProcessing();

            $id = ! $this->payload ? wp_insert_post(apply_filters('ozdon_pre_data', $this->data), true) : $this->payload;
            //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
            do_action(static::PREFIX . 'on_option_post_saved', $id);

            echo wp_json_encode(
                array(
					'success' => ! is_wp_error($id),
					'payload' => ! is_wp_error($id) ? $id : $id->get_error_message(),
                )
            );
        }
        wp_die();
    }

    /**
     * Get Value
     *
     * @param  array   $field Field
     * @param  WP_Post $post  Post
     * @return mixed
     */
    public function getValue( $field, $post ) {
        switch ( $field['name'] ) {
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
        $field['value'] = $val ? $val : $field['value'];

        return $field['value'];
    }

    /**
     * Get Value
     *
     * @param  array    $field Field
     * @param  \WP_User $user  User
     * @return mixed
     */
    public function getUserValue( $field, $user ) {
        $field['value'] = $user->has_prop($field['name']) ? $user->get($field['name']) : '';

        return $field['value'];
    }

    /**
     * Todo make async select fields
     *
     * @return void
     */
    public function search() {
        if ( $this->canDoThis() ) {
            //phpcs:disable WordPress.Security.NonceVerification.Missing
            $type = isset($_POST['type']) ? $_POST['type'] : '';
            $word = isset($_POST['word']) ? $_POST['word'] : '';
            //phpcs:enable
            $ans   = array();
            $query = false;
            switch ( $type ) {
				case '':
                    break;
				default:
					$query = array(
						's' => sanitize_text_field($word),
					);
					$ans   = $this->WP_Query_toSelect($query);
            }
            echo wp_json_encode(
                array(
					'success' => true,
					'payload' => ! empty($ans) && ! is_wp_error($query) ? $ans : array(),
                )
            );
        }
        wp_die();
    }

    /**
     * Todo search results to select field
     *
     * @param  mixed $query Query args
     * @return array
     */
    private function WP_Query_toSelect( $query ) {
        $query = new WP_Query($query);
        $ans   = array();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $ans[] = array(
                    'label' => get_the_title(),
                    'value' => get_the_ID(),
                );
            }
        }
        return $ans;
    }

    /**
     * Allow only these styles
     *
     * @param  array $styles Styles
     * @return array
     */
    public function safe_styles( $styles ) {
        $styles[] = 'display';
        return $styles;
    }
}
