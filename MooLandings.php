<?php

// Includes the php file that imports the landing to wordpress
include_once 'forms/create_landing.php';
include_once 'forms/auth-request.php';
if (!class_exists('MooLandings')) {
    class MooLandings
    {
        private $moolanding_options;
        public function __construct()
        {
            //hooks
            add_action( 'init', array( $this, 'moolanding_landing_custom_post_type' ) ); //creates the custom post type
            add_action('admin_init', array($this, 'moolanding_admin_init')); //creates the fields to use the settings API
            add_action('admin_menu', array($this, 'moolandingAdmin')); // Loads the function that creates de sub-menus
            add_action('admin_notices', array($this, 'moolanding_adminWarnings')); // Loads the function that creates the warning message when there's no API Key.
            add_filter( 'single_template', array( $this, 'moolanding_landing_template' ) ); // Loads the function that overrides the theme template when loading landing pages
            add_action( 'wp', array( $this, 'moolanding_contentHook' )); // Loads the function that overrides the WordPress content with the landing page HTML
            add_action( 'admin_menu', array( $this, 'moolanding_disable_new_posts' )); // hides the ADD NEW button in the Landing page list page
            add_filter( 'manage_moosend_landing_posts_columns', array( $this, 'moolanding_set_landing_columns' ) ); // Creates the custom columns for the landing pages list page
            add_action( 'manage_moosend_landing_posts_custom_column' , array( $this, 'moolanding_custom_landing_column' ), 10, 2 ); // Adds content to the custom columns
        }
        // Adds the menu pages as sub-menus of the custom post type.
        public function moolandingAdmin()
        {
            //NEW LANDING - ADD ADMIN PAGE
            add_submenu_page('edit.php?post_type=moosend_landing', 'Import', 'Import to WordPress', 'manage_options', 'moosend-landing-importer', [$this, 'moolanding_importer_page'] );
            //SETTINGS PAGE
            add_submenu_page('edit.php?post_type=moosend_landing', 'Settings', 'Settings', 'manage_options', 'moosend-landings-settings', [$this, 'moolanding_admin_page'] );
            // Connect Page
            $apiKey = get_option('moosend_landing_api_key');
	        $apiKey == false ? $connect_profile = 'Connect' : $connect_profile = 'Profile';
            add_submenu_page('edit.php?post_type=moosend_landing', $connect_profile, $connect_profile, 'manage_options', 'moosend-authentication', [$this, 'moolanding_auth_page'] );
        }
        // HTML of the landing page importer admin page
        public function moolanding_auth_page(){
            include_once('views/auth-page.php');
        }
        //Renders the authentication page.
        public function moolanding_admin_page()
        {
            // Set class property
            $this->options = get_option( 'moolanding_options' );
            ?>
            <div class="wrap">
                <h2>Moosend Landing Pages Settings</h2>
                <form method="post" action="options.php">
                    <?php
                    // This prints out all hidden setting fields
                    settings_fields( 'moolanding_main_options_group' );
                    do_settings_sections( 'moolanding_settings_admin_page' );
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
        // Creates the API Key setting page fields and texts
        function moolanding_admin_init(){
            register_setting(
                'moolanding_main_options_group', // Option group
                'moolanding_options', // Option name
                array( $this, 'sanitize' ) // Sanitize
            );
            add_settings_section(
                    'moolanding_url_section', // ID
                    'Landing Pages URL Settings', // Title
                    array( $this, 'moolanding_print_url_section_info' ), // Callback
                    'moolanding_settings_admin_page' // Page
            );
            add_settings_section(
                    'moolanding_permalink_tutorial_section', // ID
                    'How to implement the changes:', // Title
                    array( $this, 'moolanding_permalink_tutorial_section_info' ), // Callback
                    'moolanding_settings_admin_page' // Page
            );
            add_settings_field(
                    'moolanding_url',
                    'The URL pattern for your landing pages',
                    array( $this, 'moolanding_url_field_callback' ),
                    'moolanding_settings_admin_page',
                    'moolanding_url_section'
            );
        }
        public function sanitize( $input )
        {
            $new_input = array();

            if (isset($input['moolanding_url']))
                $new_input['moolanding_url'] = sanitize_text_field($input['moolanding_url']);
            return $new_input;
        }
        // SECTION INFORMATION CALLBACKS
        public function moolanding_print_url_section_info()
        {
            print 'Here you can configure the settings for the URLs of your imported landing pages';
        }
        public function moolanding_permalink_tutorial_section_info()
        {
            print 'After you save here, you need to go to: 1. Settings -> 2. Permalinks -> 3. Without doing anything else, just click the button Save Changes and that is it';
        }
        //FIELDS CALLBACKS
        public function moolanding_url_field_callback()
        {
            printf(
                '<input size="30" type="text" id="moolanding_url" placeholder="Examples: landing or promotions" name="moolanding_options[moolanding_url]" value="%s" />',
                isset( $this->options['moolanding_url'] ) ? esc_attr( $this->options['moolanding_url']) : ''
            );
        }

        // Here we decide if the content is going to be replaced from the one of the landing or not, so we check if the custom post type matches
        public function moolanding_contentHook(){
            if ('moosend_landing' === get_post_type() AND is_singular()) {
                add_filter('the_content', array( $this, "moolanding_landingContent" ));
            }
        }
        // Here we overwrite the template of the theme, if it's the landing custom post type then it will use our template
        public function moolanding_landing_template($template) {
            global $post;

            if ( 'moosend_landing' === $post->post_type ) {
                return plugin_dir_path( __FILE__ ) . 'views/single-landing.php';
            }

            return $template;
        }
        // Here we get the content of the imported landing page, then render the body of the same
        function moolanding_landingContent($content)
        {
            global $post;

            if ('moosend_landing' === $post->post_type) {
                $apiKey = get_option('moosend_landing_api_key');
                $landingId = get_post_meta( get_the_ID(), 'MoosendWebsiteId', true );
                $moosend_gateway = 'https://gateway.services.moosend.com/websites/entities/' . $landingId . '/with-extras?format=json';
                // Send the request
                $response = wp_remote_get( $moosend_gateway, array(
                    'headers' => array('Content-Type' => 'application/json', 'x-apikey' => $apiKey)));
                $jsonBody = json_decode($response['body'], true);
                //Get URL
                $landing_body = $jsonBody['Entity']['PublishContext'];
                $landing_status = get_headers($landing_body);
                if(strpos($landing_status[0], '200') == false){
                    //header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
                    wp_redirect( home_url(), 301 );
                }else{
                    // Get the HTML of the URL
                    $content = file_get_contents($landing_body);
                    return $content;
                }
            }
        }
        // HTML of the landing page importer admin page
        public function moolanding_importer_page(){
            include_once('views/importer-page.php');
        }

        // Add the custom columns to the landing post type:
        function moolanding_set_landing_columns($columns) {
            unset($columns['date']);
            $columns['page_views'] = __( 'Total Page Views', 'moosend_landing' );
            $columns['conversions'] = __( 'Conversions', 'moosend_landing' );
            $columns['conversion_rate'] = __( 'Conversion Rate', 'moosend_landing' );
            $columns['edit_landing'] = __( 'Edit', 'moosend_landing' );

            return $columns;
        }

        // Add the data to the custom columns for the landing post type:
        function moolanding_custom_landing_column( $column, $post_id ) {
            $comeBack = esc_url(admin_url('edit.php?post_type=moosend_landing'));
            $apiKey = get_post_meta( get_the_ID(), 'landingApiKey', true );
            $teamMemberId = get_option('moosend_landing_team_member_id');
            $currentApiKey = get_option('moosend_landing_api_key');
            $usersite = get_option('moosend_landing_user_site');
            $landingId = get_post_meta(get_the_ID(), 'MoosendWebsiteId', true);
            $moosend_gateway = 'https://gateway.services.moosend.com/websites/entities/' . $landingId . '/with-extras?format=json';
            // Send the request
            $response = wp_remote_get( $moosend_gateway, array(
                'headers' => array('Content-Type' => 'application/json', 'x-apikey' => $apiKey)));
            $jsonResponse = json_decode($response['body'], true);
            $page_views = $jsonResponse['Entity']['TotalPageViews'];
            $conversions = $jsonResponse['Entity']['TotalConversions'];
            $conversion_rate = round($jsonResponse['Entity']['ConversionPercentage']*100) . '%';
            $button_link = 'https://' . $usersite . '.moosend.com/design/?hasBlueprints=true&editorType=landingPage&entityId=' . urlencode($landingId) . '&apiKey=' . urlencode($apiKey) . '&redirectUrl=' . urlencode($comeBack) . '&teamMemberId=' . urlencode($teamMemberId);
            
            if($currentApiKey == $apiKey){
                switch ( $column ) {

                    case 'edit_landing' :
                        echo '<button ><a href="' . esc_html($button_link) . '" target="_blank"> Edit in Moosend </a></button>';
                        break;

                    case 'page_views' :
                        echo(esc_attr(number_format($page_views)));
                        break;

                    case 'conversions' :
                        echo(esc_attr(number_format($conversions)));
                        break;

                    case 'conversion_rate' :
                        echo(esc_attr($conversion_rate));
                        break;
                }
            }else{
                switch ( $column ) {

                    case 'edit_landing' :
                        echo '<p> Can not edit from this account </p>';
                        break;

                    case 'page_views' :
                        echo(esc_attr(number_format($page_views)));
                        break;

                    case 'conversions' :
                        echo(esc_attr(number_format($conversions)));
                        break;

                    case 'conversion_rate' :
                        echo(esc_attr($conversion_rate));
                        break;
            }

        }}
        // Hide edit buttons on the landing page table page
        public function moolanding_disable_new_posts() {
            // Hide sidebar link
            global $submenu;
            unset($submenu['edit.php?post_type=moosend_landing'][10]);
            // Hide link on listing page
            if (isset($_GET['post_type']) && sanitize_text_field($_GET['post_type']) == 'moosend_landing') {
                echo '<style type="text/css">.page-title-action{ display:none; }</style>';
            }
            $apiKey = get_option('moosend_landing_api_key');
            $userId = get_option('moosend_landing_user_id');
            if (isset($_GET['post_type']) && sanitize_text_field($_GET['post_type']) == 'moosend_landing' && !isset($_GET['page'])) {
                if (!get_option('moosend_landing_user_site') && empty(get_option('moosend_landing_user_site'))) {
                    $moosend_auth_gateway_subdomain = 'https://gateway.services.moosend.com/site/user-id/' . $userId;
                    $response_subdomain = wp_remote_get($moosend_auth_gateway_subdomain, array(
                        'headers' => array('Accept' => 'application/json', 'x-apikey' => $apiKey)));
                    $jsonResponse_subdomain = json_decode($response_subdomain['body'], true);
                    if(isset($jsonResponse_subdomain['Name'])) {
                        $subdomain = $jsonResponse_subdomain['Name'];
                        add_option('moosend_landing_user_site', $subdomain);
                    }
                }
            }
            if ($apiKey == false) {
                echo '<style type="text/css">#menu-posts-moosend_landing > ul > li:nth-child(3) > a{ display:none;} #menu-posts-moosend_landing > ul > li.wp-first-item > a { display: none;} #menu-posts-moosend_landing > ul > li:nth-child(4) > a{ display:none;} </style>';
            }

        }
        // Register the Landing page custom post type
        public function moolanding_landing_custom_post_type() {
            $landing_path = 'l';
            $moolanding_options = get_option('moolanding_options');
            if(isset($moolanding_options['moolanding_url'])){
                $landing_path = urlencode($moolanding_options['moolanding_url']);
            }

            $args = array (
                'label' => esc_html__( 'Landing Pages', 'moosend_landing' ),
                'labels' => array(
                    'menu_name' => esc_html__( 'Moosend', 'moosend_landing' ),
                    'name_admin_bar' => esc_html__( 'Landing Page', 'moosend_landing' ),
                    'add_new' => esc_html__( 'Add new', 'moosend_landing' ),
                    'add_new_item' => esc_html__( 'Add new landing page', 'moosend_landing' ),
                    'new_item' => esc_html__( 'New landing page', 'moosend_landing' ),
                    'edit_item' => esc_html__( 'Edit landing page', 'moosend_landing' ),
                    'view_item' => esc_html__( 'View landing page', 'moosend_landing' ),
                    'update_item' => esc_html__( 'Update landing page', 'moosend_landing' ),
                    'all_items' => esc_html__( 'Landing Pages', 'moosend_landing' ),
                    'search_items' => esc_html__( 'Search Landing pages', 'moosend_landing' ),
                    'parent_item_colon' => esc_html__( 'Parent landing page', 'moosend_landing' ),
                    'not_found' => esc_html__( 'No Landing pages found', 'moosend_landing' ),
                    'not_found_in_trash' => esc_html__( 'No Landing page found in Trash', 'moosend_landing' ),
                    'name' => esc_html__( 'Landing Pages', 'moosend_landing' ),
                    'singular_name' => esc_html__( 'Landing Page', 'moosend_landing' ),
                ),
                'public' => true,
                'description' => 'Moosend landing pages',
                'exclude_from_search' => false,
                'publicly_queryable' => true,
                'show_ui' => true,
                'show_in_nav_menus' => true,
                'show_in_menu' => true,
                'show_in_admin_bar' => true,
                'show_in_rest' => true,
                'menu_position' => 20,
                'menu_icon' => plugins_url('assets/img/moo-favicon.png', __FILE__),
                'capability_type' => 'page',
                'hierarchical' => false,
                'has_archive' => false,
                'query_var' => true,
                'can_export' => true,
                'rewrite_no_front' => false,
                'supports' => array(
                    'title'
                ),
                'rewrite' => array('slug' => $landing_path,'with_front' => false),
            );

            register_post_type( 'moosend_landing', $args );
        }

        public function moolanding_adminWarnings()
        {
            $apiKey = get_option('moosend_landing_api_key');
            $hasApiKey = !empty($apiKey);
            $isInAuthPage = strpos(sanitize_text_field($_SERVER['REQUEST_URI']), 'page=moosend-authentication');

            if (!$hasApiKey & $isInAuthPage == false ):
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong>
                            <a href="<?php echo esc_url_raw(admin_url('edit.php?post_type=moosend_landing&page=moosend-authentication')); ?>"><?php _e('In order to make it work, Moosend Landing Pages requires an API Key. Click here to login.', 'moolanding'); ?></a>
                        </strong>
                    </p>
                </div>
            <?php
            endif;
            $isSubmit = isset($_POST['submit']) ? true : false;

            if (empty($api_key) && $isSubmit && sanitize_text_field($_SERVER['QUERY_STRING']) == 'post_type=moosend_landing&page=moosend-authentication'):
                ?>
                <div class="notice notice-error">
                    <p>
                        <?php _e('Website ID cannot be blank.', 'moosend'); ?>
                    </p>
                </div>
            <?php
            endif;

            if (!empty($api_key) && $isSubmit && sanitize_text_field($_SERVER['QUERY_STRING']) == 'post_type=moosend_landing&page=moosend-authentication'):
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php _e('Website ID updated successfully', 'moosend'); ?>
                    </p>
                </div>
            <?php
            endif;
        }
    }
}
