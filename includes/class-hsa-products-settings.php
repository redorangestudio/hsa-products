<?php

if (!defined('ABSPATH'))
    exit;

class HSA_Products_Settings {

    /**
     * The single instance of HSA_Products_Settings.
     * @var 	object
     * @access  private
     * @since 	1.0.0
     */
    private static $_instance = null;

    /**
     * The main plugin object.
     * @var 	object
     * @access  public
     * @since 	1.0.0
     */
    public $parent = null;

    /**
     * Prefix for plugin settings.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $base = '';

    /**
     * Available settings for plugin.
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public $settings = array();

    public function __construct($parent) {
        $this->parent = $parent;

        $this->base = 'hsa_';

        // Initialise settings
        add_action('init', array($this, 'init_settings'), 11);

        // Register plugin settings
        add_action('admin_init', array($this, 'register_settings'));

        // Add settings page to menu
        add_action('admin_menu', array($this, 'add_menu_item'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename($this->parent->file), array($this, 'add_settings_link'));
    }

    /**
     * Initialise settings
     * @return void
     */
    public function init_settings() {
        $this->settings = $this->settings_fields();
    }

    /**
     * Add settings page to admin menu
     * @return void
     */
    public function add_menu_item() {

        $page = add_submenu_page('edit.php?post_type=hsa-products', __('Settings', 'hsa-products'), __('Settings', 'hsa-products'), 'manage_options', $this->parent->_token . '_settings', array($this, 'settings_page'));
        add_action('admin_print_styles-' . $page, array($this, 'settings_assets'));
    }

    /**
     * Load settings JS & CSS
     * @return void
     */
    public function settings_assets() {

        // We're including the farbtastic script & styles here because they're needed for the colour picker
        // If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below
        wp_enqueue_style('farbtastic');
        wp_enqueue_script('farbtastic');

        // We're including the WP media scripts here because they're needed for the image upload field
        // If you're not including an image upload then you can leave this function call out
        wp_enqueue_media();

        wp_register_script($this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array('farbtastic', 'jquery'), '1.0.0');
        wp_enqueue_script($this->parent->_token . '-settings-js');
    }

    /**
     * Add settings link to plugin list table
     * @param  array $links Existing links
     * @return array 		Modified links
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="edit.php?post_type=hsa-products&page=' . $this->parent->_token . '_settings">' . __('Settings', 'hsa-products') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }

    /**
     * Build settings fields
     * @return array Fields to be displayed on settings page
     */
    private function settings_fields() {

        
        $settings['forms'] = array(
            'title' => __('Forms', 'hsa-products'),
            'fields' => array(
                 array(
                    'id' => 'forms',
                    'label' => __('Global Forms Content', 'hsa-products'),
                    'type' => 'wp_editor',
                )
            )
        );
        $settings['fees'] = array(
            'title' => __('Fees', 'hsa-products'),
            'fields' => array(
                array(
                    'id' => 'fees',
                    'label' => __('Global Fee Content', 'hsa-products'),
                    'type' => 'wp_editor',
                )
            )
        );
        $settings['faqs'] = array(
            'title' => __('FAQs', 'hsa-products'),
            'fields' => array(
                array(
                    'id' => 'faqs',
                    'label' => __('Global FAQ Content', 'hsa-products'),
                    'type' => 'wp_editor',
                )
            )
        );
        $settings['funds'] = array(
            'title' => __('Funds', 'hsa-products'),
            'fields' => array(
                array(
                    'id' => 'funds',
                    'label' => __('Funds Template', 'hsa-products'),
                    'type' => 'wp_editor',
                )
            )
        );
        $settings['resources'] = array(
            'title' => __('Resources', 'hsa-products'),
            'fields' => array(
                array(
                    'id' => 'resources',
                    'label' => __('Global Resources Content', 'hsa-products'),
                    'type' => 'wp_editor',
                )
            )
        );
        $settings['contact_us'] = array(
            'title' => __('Contact Us', 'hsa-products'),
            'fields' => array(
                array(
                    'id' => 'contact_us',
                    'label' => __('Global Contact Us Content', 'hsa-products'),
                    'type' => 'wp_editor',
                )
            )
        );

        /*$settings['get_in_touch'] = array(
            'title' => __('get in touch', 'hsa-products'),
            'fields' => array(
                array(
                    'id' => 'get_in_touch',
                    'label' => __('Global get in touch Content', 'hsa-products'),
                    'type' => 'wp_editor',
                )
            )
        );*/

        $settings = apply_filters($this->parent->_token . '_settings_fields', $settings);

        return $settings;
    }

    public function get_nav_menus() {
        $menus = get_terms('nav_menu', array('hide_empty' => false));
        $_menu = array('0' => 'Select Menu');
        foreach ($menus as $menu) {
            $_menu[$menu->term_id] = $menu->name;
        }
        return $_menu;
    }

    public function get_pages() {
        $pages = get_posts(array('post_type' => 'page', 'posts_per_page' => -1, 'order' => 'ASC', 'orderby' => 'name'));
        $_pages = array('0' => 'Select Page');
        foreach ($pages as $page) {
            $title = '';
            $ancestors = get_post_ancestors($page->ID);
            if (is_array($ancestors) && !empty($ancestors)) {
                //first one first
                foreach ($ancestors as $ancestor) {
                    $title = get_the_title($ancestor) . '->' .$title;
                }
                $_pages[$page->ID] = $title . $page->post_title;
            } else {

                $_pages[$page->ID] = $page->post_title;
            }
        }
        return $_pages;
    }

    /**
     * Register plugin settings
     * @return void
     */
    public function register_settings() {
        if (is_array($this->settings)) {

            // Check posted/selected tab
            $current_section = '';
            if (isset($_POST['tab']) && $_POST['tab']) {
                $current_section = $_POST['tab'];
            } else {
                if (isset($_GET['tab']) && $_GET['tab']) {
                    $current_section = $_GET['tab'];
                }
            }

            foreach ($this->settings as $section => $data) {

                if ($current_section && $current_section != $section)
                    continue;

                // Add section to page
                add_settings_section($section, $data['title'], array($this, 'settings_section'), $this->parent->_token . '_settings');

                foreach ($data['fields'] as $field) {

                    // Validation callback for field
                    $validation = '';
                    if (isset($field['callback'])) {
                        $validation = $field['callback'];
                    }

                    // Register field
                    $option_name = $this->base . $field['id'];
                    register_setting($this->parent->_token . '_settings', $option_name, $validation);

                    // Add field to page
                    add_settings_field($field['id'], $field['label'], array($this->parent->admin, 'display_field'), $this->parent->_token . '_settings', $section, array('field' => $field, 'prefix' => $this->base));
                }

                if (!$current_section)
                    break;
            }
        }
    }

    public function settings_section($section) {
        $html = '<p> ' . $this->settings[$section['id']]['description'] . '</p>' . "\n";
        echo $html;
    }

    /**
     * Load settings page content
     * @return void
     */
    public function settings_page() {

        // Build page HTML
        $html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
        $html .= '<h2>' . __('HSA Products', 'hsa-products') . '</h2>' . "\n";

        $tab = '';
        if (isset($_GET['tab']) && $_GET['tab']) {
            $tab .= $_GET['tab'];
        }

        // Show page tabs
        if (is_array($this->settings) && 1 < count($this->settings)) {

            $html .= '<h2 class="nav-tab-wrapper">' . "\n";

            $c = 0;
            foreach ($this->settings as $section => $data) {

                // Set tab class
                $class = 'nav-tab';
                if (!isset($_GET['tab'])) {
                    if (0 == $c) {
                        $class .= ' nav-tab-active';
                    }
                } else {
                    if (isset($_GET['tab']) && $section == $_GET['tab']) {
                        $class .= ' nav-tab-active';
                    }
                }

                // Set tab link
                $tab_link = add_query_arg(array('tab' => $section));
                if (isset($_GET['settings-updated'])) {
                    $tab_link = remove_query_arg('settings-updated', $tab_link);
                }

                // Output tab
                $html .= '<a href="' . $tab_link . '" class="' . esc_attr($class) . '">' . esc_html($data['title']) . '</a>' . "\n";

                ++$c;
            }

            $html .= '</h2>' . "\n";
        }

        $html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

        // Get settings fields
        ob_start();
        settings_fields($this->parent->_token . '_settings');
        do_settings_sections($this->parent->_token . '_settings');
        $html .= ob_get_clean();

        $html .= '<p class="submit">' . "\n";
        $html .= '<input type="hidden" name="tab" value="' . esc_attr($tab) . '" />' . "\n";
        $html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr(__('Save Settings', 'hsa-products')) . '" />' . "\n";
        $html .= '</p>' . "\n";
        $html .= '</form>' . "\n";
        $html .= '</div>' . "\n";

        echo $html;
    }

    /**
     * Main HSA_Products_Settings Instance
     *
     * Ensures only one instance of HSA_Products_Settings is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see HSA_Products()
     * @return Main HSA_Products_Settings instance
     */
    public static function instance($parent) {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($parent);
        }
        return self::$_instance;
    }

// End instance()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->parent->_version);
    }

// End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->parent->_version);
    }

// End __wakeup()
}
