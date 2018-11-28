<?php

if (!defined('ABSPATH'))
    exit;

class HSA_Products {

    /**
     * The single instance of HSA_Products.
     * @var 	object
     * @access  private
     * @since 	1.0.0
     */
    private static $_instance = null;

    /**
     * Settings class object
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $settings = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token;

    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $script_suffix;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $post_type;

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function __construct($file = '', $version = '1.0.0') {
        $this->_version = $version;
        $this->_token = 'hsa_products';
        $this->post_type = HSA_POST_TYPE_NAME;

        // Load plugin environment variables
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        $this->script_suffix = defined('RO_SCRIPT_DEBUG') && RO_SCRIPT_DEBUG ? '' : '.min';

        register_activation_hook($this->file, array($this, 'install'));

        // Load frontend JS & CSS
        //add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 10);
        //add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 10);

        //add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);

        add_filter('hsa_future_custom_fields', array($this, 'add_hsa_future_fields'), 99, 2);
        add_filter('hsa_product_overview_custom_fields', array($this, 'add_hsa_product_overview_fields'), 99, 2);
        add_filter('hsa_funds_custom_fields', array($this, 'add_hsa_funds_fields'), 99, 2);
        add_filter('hsa_fees_custom_fields', array($this, 'add_hsa_fees_fields'), 99, 2);
        add_filter('hsa_nav_custom_fields', array($this, 'add_hsa_nav_fields'), 99, 2);
        add_filter('hsa_faqs_custom_fields', array($this, 'add_hsa_faqs_fields'), 99, 2);
        add_filter('hsa_forms_custom_fields', array($this, 'add_hsa_forms_fields'), 99, 2);
        //add_filter('hsa_get_in_touch_custom_fields', array($this, 'add_hsa_get_in_touch_fields'), 99, 2);
        add_filter('hsa_resources_custom_fields', array($this, 'add_hsa_resources_fields'), 99, 2);
        add_filter('hsa_contact_us_custom_fields', array($this, 'add_hsa_contact_us_fields'), 99, 2);
        add_action('add_meta_boxes', array($this, 'add_custom_meta_boxes'));


        add_filter('query_vars', array($this, 'add_query_vars'));
        //add_filter('rewrite_rules_array', array($this, 'add_rewrite_rules'));
        add_filter('post_type_link', array($this, 'remove_cpt_slug'), 10, 3);
        add_action('pre_get_posts', array($this, 'parse_request_trick'));
        if (is_admin()) {
            add_action('wp_ajax_get_funds_content', array($this, 'get_funds_content'));
        }

        // Load API for generic admin functions
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
            $this->admin = new HSA_Products_Admin_API();
        }

        // Handle localisation
        $this->load_plugin_textdomain();
        add_action('init', array($this, 'load_localisation'), 0);

        $this->setup_post_type();
    }

// End __construct ()


    private function setup_post_type() {

        $this->register_post_type($this->post_type, 'HSA Products', 'HSA Product', '', array('supports' => array('title', 'editor', 'thumbnail', 'revisions')));

        add_filter('single_template', array($this, 'add_hsa_products_single_template'));
        //add_filter('archive_template', array($this, 'add_hsa_products_archive_template'));
    }

    public function remove_cpt_slug($post_link, $post, $leavename) {

        if ('hsa-products' != $post->post_type || 'publish' != $post->post_status) {
            return $post_link;
        }

        $post_link = str_replace('/' . $post->post_type . '/', '/', $post_link);

        return $post_link;
    }

    public function parse_request_trick($query) {

        // Only noop the main query
        if (!$query->is_main_query())
            return;

        // Only noop our very specific rewrite rule match
        if (2 != count($query->query) || !isset($query->query['page'])) {
            return;
        }

        // 'name' will be set if post permalinks are just post_name, otherwise the page rule will match
        if (!empty($query->query['name'])) {
            $query->set('post_type', array('post', 'page', 'hsa-products'));
        }
    }

    public function custom_post_status() {
        register_post_status('future_product', array(
            'label' => _x('Future Product', 'hsa-products'),
            'public' => true,
            'show_in_admin_all_list' => false,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Future Product <span class="count">(%s)</span>', 'Future Product <span class="count">(%s)</span>')
        ));
    }

    public function append_post_status_list() {
        global $post;
        $complete = '';
        $label = '';
        if ($post->post_type == 'hsa-products') {
            if ($post->post_status == 'future_product') {
                $complete = ' selected="selected"';
                $label = '<span id="post-status-display"> Future Product</span>';
            }
            echo '
          <script>
          jQuery(document).ready(function($){
               $("select#post_status").append("<option value=\"future_product\" ' . $complete . '>Future Product</option>");
               $(".misc-pub-section label").append("' . $label . '");
                   
          });
          </script>
          ';
        }
    }

    public function add_hsa_products_single_template($single_template) {

        global $post;

        if ($this->post_type != $post->post_type) {
            return $single_template;
        }

        $single_postType_template = $this->hsa_locate_template("single-{$this->post_type}.php");

        if (file_exists($single_postType_template)) {
            return $single_postType_template;
        } else {
            return $single_template;
        }
    }

    public function add_hsa_products_archive_template($archive_template) {

        global $post;

        if ($this->post_type != $post->post_type) {
            return $single_template;
        }

        $archive_postType_template = $this->hsa_locate_template("archive-{$this->post_type}.php");
        if (file_exists($archive_postType_template)) {
            return $archive_postType_template;
        } else {
            return $archive_template;
        }
    }

    public function hsa_locate_template($template_name) {

        $template_path = locate_template($template_name);

        if (file_exists($template_path)) {
            return $template_path;
        } else {
            return $this->dir . '/templates/' . $template_name;
        }
    }

    public function add_query_vars($aVars) {
        $aVars[] = "product_page"; // represents the name of the product category as shown in the URL
        return $aVars;
    }

    public function add_rewrite_rules($aRules) {

        //var_dump($aRules);preg_match("/hsa-products\/([^\/]+)\/([^\/]+)\/?$/", $input_line, $output_array);
        //$aNewRules = array('(.?.+?)/(funds|fees|forms|faqs)/?$' => 'index.php?' . $this->post_type . '=$matches[1]&product_page=$matches[2]');
        //$aNewRules = array('(?.?+)/(funds|fees|forms|faqs)/?$' => 'index.php?' . $this->post_type . '=$matches[1]&product_page=$matches[2]');
        $aRules = $aNewRules + $aRules;

        return $aRules;
    }

    public function add_custom_meta_boxes() {
        $this->admin->add_meta_box('hsa_future', 'Status', array('hsa-products'), 'side', 'default');
        $this->admin->add_meta_box('hsa_product_overview', 'Product Overview', array('hsa-products'), 'advanced', 'default');
        $this->admin->add_meta_box('hsa_funds', 'Funds', array('hsa-products'), 'advanced', 'default');
        $this->admin->add_meta_box('hsa_fees', 'Fees', array('hsa-products'), 'advanced', 'default');
        $this->admin->add_meta_box('hsa_faqs', 'FAQs', array('hsa-products'), 'advanced', 'default');
        $this->admin->add_meta_box('hsa_forms', 'Forms', array('hsa-products'), 'advanced', 'default');
        //$this->admin->add_meta_box('hsa_get_in_touch', 'get in touch', array('hsa-products'), 'advanced', 'default');
        $this->admin->add_meta_box('hsa_resources', 'Resources', array('hsa-products'), 'advanced', 'default');
        $this->admin->add_meta_box('hsa_contact_us', 'Contact Us', array('hsa-products'), 'advanced', 'default');
        $this->admin->add_meta_box('hsa_nav', 'Sidebar', array('hsa-products'), 'advanced', 'default');
    }

    public function add_hsa_funds_fields($fields, $post_type) {


        if ('hsa-products' != $post_type) {
            return false;
        }

        add_action('media_buttons', array($this, 'add_funds_content_button'), 15);
        $fields = array(
            array(
                'metabox' => true,
                'id' => 'funds_content',
                'label' => __('Product Funds', 'hsa-products'),
                'description' => __('These are the funds for this specific product', 'hsa-products'),
                'type' => 'wp_editor'
            ),
        );
        return $fields;
    }

    public function add_funds_content_button() {
        echo '<a style="margin-left:10px;color:red;" href="#" id="fund-content-button" class="button">Add Funds Template</a>';
    }

    public function get_funds_content() {

        if (!isset($_POST['add_funds_content_nonce']) || !wp_verify_nonce($_POST['add_funds_content_nonce'], 'add_funds_content')) {
            die();
        }

        wp_send_json(
                array(
                    'success' => get_option('hsa_funds')
                )
        );
    }

    public function add_hsa_fees_fields($fields, $post_type) {
        if ('hsa-products' != $post_type) {
            return false;
        }

        remove_action('media_buttons', array($this, 'add_funds_content_button'), 15);
        $fields = array(
            array(
                'metabox' => true,
                'id' => 'fees_lead_in',
                'label' => __('Lead In Paragraph', 'hsa-products'),
                'description' => __('Enter paragraph that leads into fees page', 'hsa-products'),
                'type' => 'wp_editor'
            ),
        );
        return $fields;
    }

    public function add_hsa_faqs_fields($fields, $post_type) {
        if ('hsa-products' != $post_type) {
            return false;
        }


        $fields = array(
            array(
                'metabox' => true,
                'id' => 'faqs_lead_in',
                'label' => __('Lead In Paragraph', 'hsa-products'),
                'description' => __('Enter paragraph that leads into FAQs page', 'hsa-products'),
                'type' => 'wp_editor'
            ),
        );
        return $fields;
    }
    
    /*public function add_hsa_get_in_touch_fields($fields, $post_type) {
        if ('hsa-products' != $post_type) {
            return false;
        }


        $fields = array(
            array(
                'metabox' => true,
                'id' => 'get_in_touch_lead_in',
                'label' => __('Lead In Paragraph', 'hsa-products'),
                'description' => __('Enter paragraph that leads into get in touch page', 'hsa-products'),
                'type' => 'wp_editor'
            ),
        );
        return $fields;
    }*/
    
    public function add_hsa_resources_fields($fields, $post_type) {
        if ('hsa-products' != $post_type) {
            return false;
        }


        $fields = array(
            array(
                'metabox' => true,
                'id' => 'resources_lead_in',
                'label' => __('Lead In Paragraph', 'hsa-products'),
                'description' => __('Enter paragraph that leads into Resources page', 'hsa-products'),
                'type' => 'wp_editor'
            ),
        );
        return $fields;
    }
    
    public function add_hsa_contact_us_fields($fields, $post_type) {
        if ('hsa-products' != $post_type) {
            return false;
        }


        $fields = array(
            array(
                'metabox' => true,
                'id' => 'contact_us_lead_in',
                'label' => __('Lead In Paragraph', 'hsa-products'),
                'description' => __('Enter paragraph that leads into Contact Us page', 'hsa-products'),
                'type' => 'wp_editor'
            ),
        );
        return $fields;
    }
    
    
    
    
    public function add_hsa_nav_fields($fields, $post_type) {
        if ('hsa-products' != $post_type) {
            return false;
        }


        $fields = array(
            array(
                'metabox' => true,
                'id' => 'loginurl_nav',
                'label' => __('Login URL Address (include http/https)', 'hsa-products'),
                'description' => __('', 'hsa-products'),
                'placeholder' => __('Login', 'hsa-products'),
                'type' => 'text'
            ),
            array(
                'metabox' => true,
                'id' => 'first_nav',
                'label' => __('First nav item', 'hsa-products'),
                'description' => __('', 'hsa-products'),
                'placeholder' => __('Benefits', 'hsa-products'),
                'type' => 'text'
            ),
            array(
                'metabox' => true,
                'id' => 'second_nav',
                'label' => __('Second nav item', 'hsa-products'),
                'description' => __('', 'hsa-products'),
                'placeholder' => __('Funds', 'hsa-products'),
                'type' => 'text'
            ),
            array(
                'metabox' => true,
                'id' => 'third_nav',
                'label' => __('Third nav item', 'hsa-products'),
                'description' => __('', 'hsa-products'),
                'placeholder' => __('Forms', 'hsa-products'),
                'type' => 'text'
            ),
            array(
                'metabox' => true,
                'id' => 'fourth_nav',
                'label' => __('Fourth nav item', 'hsa-products'),
                'description' => __('', 'hsa-products'),
                'placeholder' => __('Fees', 'hsa-products'),
                'type' => 'text'
            ),
            array(
                'metabox' => true,
                'id' => 'fifth_nav',
                'label' => __('Fifth nav item', 'hsa-products'),
                'description' => __('', 'hsa-products'),
                'placeholder' => __('FAQs', 'hsa-products'),
                'type' => 'text'
            ),
            array(
                'metabox' => true,
                'id' => 'sixth_nav',
                'label' => __('Sixth nav item', 'hsa-products'),
                'description' => __('', 'hsa-products'),
                'placeholder' => __('Resources', 'hsa-products'),
                'type' => 'text'
            ),
            array(
                'metabox' => true,
                'id' => 'seventh_nav',
                'label' => __('Seventh nav item', 'hsa-products'),
                'description' => __('', 'hsa-products'),
                'placeholder' => __('Contact Us', 'hsa-products'),
                'type' => 'text'
            )
        );
        return $fields;
    }

/*,
            
            array(
                'metabox' => true,
                'id' => 'eighth_nav',
                'label' => __('Eighth nav item', 'hsa-products'),
                'description' => __('', 'hsa-products'),
                'placeholder' => __('get in touch', 'hsa-products'),
                'type' => 'text'
            )
*/
    public function add_hsa_forms_fields($fields, $post_type) {
        if ('hsa-products' != $post_type) {
            return false;
        }


        $fields = array(
            array(
                'metabox' => true,
                'id' => 'forms_lead_in',
                'label' => __('Lead In Paragraph', 'hsa-products'),
                'description' => __('Enter paragraph that leads into forms page', 'hsa-products'),
                'type' => 'wp_editor'
            ),
        );
        return $fields;
    }

    public function add_hsa_product_overview_fields($fields, $post_type) {
        if ('hsa-products' != $post_type) {
            return false;
        }


        $fields = array(
            array(
                'metabox' => true,
                'id' => 'product_overview_content',
                'label' => __('Product Overview Content', 'hsa-products'),
                'description' => __('Content that shows in between logo and call-to-action button on product overview page.', 'hsa-products'),
                'type' => 'wp_editor'
            ),
        );
        return $fields;
    }

    public function add_hsa_future_fields($fields, $post_type) {
        if ('hsa-products' != $post_type) {
            return false;
        }


        $fields = array(
            array(
                'metabox' => true,
                'id' => 'current_product',
                'label' => '',
                'description' => __('Current product? ', 'hsa-products'),
                'type' => 'checkbox'
            ),
            array(
                'metabox' => true,
                'id' => 'featured_product',
                'label' => '',
                'description' => __('Featured product? ', 'hsa-products'),
                'type' => 'checkbox'
            ),
            array(
                'metabox' => true,
                'id' => 'full_nav',
                'label' => '',
                'description' => __('Full Navigation Bar? ', 'hsa-products'),
                'type' => 'checkbox'
            ),
        );
        return $fields;
    }

    /**
     * Wrapper function to register a new post type
     * @param  string $post_type   Post type name
     * @param  string $plural      Post type item plural name
     * @param  string $single      Post type item single name
     * @param  string $description Description of post type
     * @return object              Post type class object
     */
    public function register_post_type($post_type = '', $plural = '', $single = '', $description = '', $options = array()) {

        if (!$post_type || !$plural || !$single)
            return;

        $post_type = new HSA_Products_Post_Type($post_type, $plural, $single, $description, $options);

        return $post_type;
    }

    /**
     * Wrapper function to register a new taxonomy
     * @param  string $taxonomy   Taxonomy name
     * @param  string $plural     Taxonomy single name
     * @param  string $single     Taxonomy plural name
     * @param  array  $post_types Post types to which this taxonomy applies
     * @return object             Taxonomy class object
     */
    public function register_taxonomy($taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array()) {

        if (!$taxonomy || !$plural || !$single)
            return;

        $taxonomy = new HSA_Products_Taxonomy($taxonomy, $plural, $single, $post_types, $taxonomy_args);

        return $taxonomy;
    }

    /**
     * Load frontend CSS.
     * @access  public
     * @since   1.0.0
     * @return void
     */
    public function enqueue_styles() {

        wp_register_style($this->_token . '-frontend', esc_url($this->assets_url) . 'css/frontend.css', array(), $this->_version);
        wp_enqueue_style($this->_token . '-frontend');
    }

// End enqueue_styles ()

    /**
     * Load frontend Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function enqueue_scripts() {
        wp_register_script($this->_token . '-frontend', esc_url($this->assets_url) . 'js/frontend' . $this->script_suffix . '.js', array('jquery'), $this->_version);
        wp_enqueue_script($this->_token . '-frontend');
    }

// End enqueue_scripts ()

    /**
     * Load admin CSS.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_styles($hook = '') {
        wp_register_style($this->_token . '-admin', esc_url($this->assets_url) . 'css/admin.css', array(), $this->_version);
        wp_enqueue_style($this->_token . '-admin');
    }

// End admin_enqueue_styles ()

    /**
     * Load admin Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_scripts($hook = '') {
        wp_register_script($this->_token . '-admin', esc_url($this->assets_url) . 'js/admin' . $this->script_suffix . '.js', array('jquery'), $this->_version);

        wp_localize_script($this->_token . '-admin', 'hsa_admin', array(
            'add_funds_content_nonce' => wp_create_nonce('add_funds_content')
        ));

        wp_enqueue_script($this->_token . '-admin');
    }

// End admin_enqueue_scripts ()

    /**
     * Load plugin localisation
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_localisation() {
        load_plugin_textdomain('hsa-products', false, dirname(plugin_basename($this->file)) . '/lang/');
    }

// End load_localisation ()

    /**
     * Load plugin textdomain
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_plugin_textdomain() {
        $domain = 'hsa-products';

        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, false, dirname(plugin_basename($this->file)) . '/lang/');
    }

// End load_plugin_textdomain ()

    /**
     * Main HSA_Products Instance
     *
     * Ensures only one instance of HSA_Products is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see HSA_Products()
     * @return Main HSA_Products instance
     */
    public static function instance($file = '', $version = '1.0.0') {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }

// End instance ()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

// End __clone ()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

// End __wakeup ()

    /**
     * Installation. Runs on activation.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function install() {
        $this->_log_version_number();
    }

// End install ()

    /**
     * Log the plugin version number.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    private function _log_version_number() {
        update_option($this->_token . '_version', $this->_version);
    }

// End _log_version_number ()
}
