<?php
/*
Plugin Name: Search Redirections
Plugin URI: https://ederson.ferreira.tec.br
Description: Create redirect rules for any given search terms.
Author: Ederson Peka
Version: 0.1.3
Author URI: https://profiles.wordpress.org/edersonpeka/
Text Domain: search-redirections
*/

if ( !class_exists( 'search_redirections' ) ) :

class search_redirections {

    // Init
    public static function init() {
        // Internationalization
        load_plugin_textdomain( 'search-redirections', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        // Hooking into admin's menus
        add_action( 'admin_menu', array( __CLASS__, 'add_options_page' ) );
        // Hooking into admin's screens
        add_action( 'admin_init', array( __CLASS__, 'options_init' ) );
        // Hooking into search requests
        add_filter( 'request', array( __CLASS__, 'modify_search_term' ) );
        // Hooking into admin's screens
        add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
        // Hooking into wp (after get posts)
        add_action( 'wp', array( __CLASS__, 'after_get_posts' ) );
        // Halt the main query in the case of an empty search 
        add_filter( 'posts_search', array( __CLASS__, 'halt_query' ), 10, 2 );
    }

    // Creates options using Settings API
    public static function options_init() {
        // One settings section...
     	add_settings_section(
		    'search_redirections_section',
		    __( 'Search Redirections', 'search-redirections' ),
		    array( __CLASS__, 'settings_description' ),
		    'search_redirections_options'
	    );
	    // ...with only two settings fields
	    add_settings_field(
		    'search_redirections_rules',
		    __( 'Rules', 'search-redirections' ),
		    array( __CLASS__, 'rules_field' ),
		    'search_redirections_options',
		    'search_redirections_section',
		    array( 'slug' => 'search_redirections_rules', 'label_for' => 'search_redirections_rules' )
        );
        register_setting( 'search_redirections_options', 'search_redirections_rules' );
        add_settings_field(
		    'search_redirections_empty_search_dest',
		    __( 'Redirect empty search results', 'search-redirections' ),
		    array( __CLASS__, 'empty_field' ),
		    'search_redirections_options',
		    'search_redirections_section',
		    array( 'slug' => 'search_redirections_empty_search_dest', 'label_for' => 'empty_search_dest' )
	    );
        register_setting( 'search_redirections_options', 'search_redirections_empty_search_dest' );

        // Create "settings" link for this plugin on plugins list
        add_filter( 'plugin_action_links', array( __CLASS__, 'settings_link' ), 10, 2 );
    }
    public static function settings_description() {
        // void
    }
    
    // Returns array of saved rules (terms and destination)
    public static function get_rules() {
        // Get saved rules
        $rules = get_option( 'search_redirections_rules' );
        // If empty, initializes array
        if ( !( is_array( $rules ) ) ) $rules = array();
        $aux = array();
        // For each rule...
        foreach ( $rules as $rule ) :
            // if rule's structure is as expected...
            if ( is_array( $rule ) && array_key_exists( 'term', $rule ) && array_key_exists( 'dest', $rule ) && ! ( empty( $rule['term'] ) || empty( $rule['dest'] ) ) ) :
                if ( ( !array_key_exists( 'group', $rule ) ) || empty( $rule['group'] ) ) :
                    $rule['group'] = __( 'General', 'search-redirections' );
                endif;
                // populates array
                $aux[] = $rule;
            endif;
        endforeach;
        return $aux;
    }

    // Build form fields for rules
    public static function rules_field() {
        // Saved rules
        $rules = call_user_func( array( __CLASS__, 'get_rules' ) );
        $group_names = array_unique( array_map( function ( $rule ) {
            return $rule['group'];
        }, $rules ) );
        $groups = array();
        foreach ( $group_names as $group_name ) :
            $groups[ $group_name ] = array_filter( $rules, function ( $rule ) use ( $group_name ) {
                return $rule['group'] == $group_name;
            } );
        endforeach;
        ?>
        <table class="search-redirections-groups">
            <thead>
                <tr>
                    <th scope="col"><?php _e( 'Group:', 'search-redirections' ); ?></th>
                    <th scope="col"><?php _e( 'Search term:', 'search-redirections' ); ?></th>
                    <th scope="col"><?php _e( 'Redirect to:', 'search-redirections' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $r = 0;
                foreach ( $groups as $group => $rules ) :
                    $first_rule = array_shift( $rules ); ?>
                    <tr class="-sr-group">
                        <td>
                            <input type="text" name="search_redirections_rules[<?php echo $r; ?>][group]" value="<?php echo esc_attr( $group ); ?>" class="-sr-group-name-input" />
                        </td>
                        <td class="-sr-term">
                            <input type="text" name="search_redirections_rules[<?php echo $r; ?>][term]" value="<?php echo esc_attr( $first_rule['term'] ); ?>" />
                        </td>
                        <td>
                            <input type="text" name="search_redirections_rules[<?php echo $r; ?>][dest]" value="<?php echo esc_attr( $first_rule['dest'] ); ?>" size="35" />
                        </td>
                        <td></td>
                    </tr>
                    <?php $r++; foreach ( $rules as $rule ) : ?>
                        <tr class="-sr-rules">
                            <td>
                                <input type="text" name="search_redirections_rules[<?php echo $r; ?>][group]" value="<?php echo esc_attr( $group ); ?>" class="-sr-group-name-input" />
                            </td>
                            <td class="-sr-term">
                                <input type="text" name="search_redirections_rules[<?php echo $r; ?>][term]" value="<?php echo esc_attr( $rule['term'] ); ?>" />
                            </td>
                            <td>
                                <input type="text" name="search_redirections_rules[<?php echo $r; ?>][dest]" value="<?php echo esc_attr( $rule['dest'] ); ?>" size="35" />
                            </td>
                            <td>
                                <a href="#" class="button button-link-delete -sr-remove-rule" title="<?php echo esc_attr( __( 'Remove rule', 'search-redirections' ) ); ?>"> 
                                    <span class="dashicons dashicons-minus"></span>
                                </a>
                            </td>
                        </tr>
                    <?php $r++; endforeach; ?>
                    <tr class="search-redirection-rule-buttons">
                        <td>
                            <a href="#" class="button button-link-delete -sr-remove-group"> 
                                <span class="dashicons dashicons-minus"></span>
                                <span><?php _e( 'Remove group', 'search-redirections' ); ?></span>
                            </a>
                        </td>
                        <td colspan="2">
                            <a href="#" class="button button-secondary -sr-add-rule"> 
                                <span class="dashicons dashicons-plus"></span>
                                <span><?php _e( 'Add rule', 'search-redirections' ); ?></span>
                            </a>
                        </td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">
                        <a href="#" class="button button-secondary -sr-add-group"> 
                            <span class="dashicons dashicons-plus"></span>
                            <span><?php _e( 'Add group', 'search-redirections' ); ?></span>
                        </a>
                    </td>
                </tr>
            </tfoot>
        </table>
        <?php
    }

    // Build form field for Redirect empty search results rule
    public static function empty_field() {
        // Saved rules
        $empty_search_dest = get_option( 'search_redirections_empty_search_dest' );
        ?>
        <h4>
            <label for="empty_search_dest">
                <?php _e( 'Redirect empty search results', 'search-redirections' ); ?>
            </label>
        </h4>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="empty_search_dest">
                            <?php _e( 'Redirect empty search results', 'search-redirections' ); ?>
                        </label>
                    </th>
                    <td>
                        <input name="search_redirections_empty_search_dest" type="text" id="empty_search_dest" value="<?php echo esc_attr( $empty_search_dest ); ?>" class="regular-text" />
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public static function add_options_page() {
        $_page = add_options_page( __( 'Search Redirections', 'search-redirections' ), __( 'Search Redirections', 'search-redirections' ), 'edit_theme_options', 'search_redirections_options', array( __CLASS__, 'render_options_page' ) );
        add_action( 'admin_print_styles-' . $_page, array( __CLASS__, 'admin_styles' ) );
        add_action( 'admin_print_scripts-' . $_page, array( __CLASS__, 'admin_scripts' ) );
    }

    public static function render_options_page() {
        ?>
        <div class="wrap">

        <div id="icon-options-general" class="icon32"><br /></div>

        <form method="post" action="options.php" class="search-redirections-options">
        <?php
        // Render form fields
        settings_fields( 'search_redirections_options' );
        do_settings_sections( 'search_redirections_options' );
        submit_button();
        ?>
        </form>

        </div>
        <?php
    }

    // On search request
    public static function modify_search_term( $request_vars ) {
        // Backup environment locale
        if ( function_exists( 'iconv' ) ) $orig_locale = setlocale( LC_CTYPE, 0 );
        // Redir rules
        $rules = call_user_func( array( __CLASS__, 'get_rules' ) );
        // Search term
        if ( !empty( $request_vars['s'] ) ) :
            // Lowercase, no spaces, etc.
            $searched = call_user_func( array( __CLASS__, 'reduce_term' ), $request_vars['s'] );
            // Iterate over rules
            foreach ( $rules as $rule ) :
                // Lowercase, no spaces, etc.
                $reduced_rule = call_user_func( array( __CLASS__, 'reduce_term' ), $rule['term'] );
                // If search term matches rule...
                if ( $reduced_rule == $searched ) :
                    // ...and a destination URL is found...
                    if ( $rule['dest'] ) :
                        // ...redirects!
                        header( 'Location: ' . $rule['dest'] );
                        // (And that's all, folks)
                        exit();
                    endif;
                endif;
            endforeach;
        endif;
        // Restore environment locale
        if ( function_exists( 'iconv' ) ) setlocale( LC_CTYPE, $orig_locale );
        return $request_vars;
    }
    
    // Prepare string for comparison: lowercase, no spaces, etc.
    public static function reduce_term( $term ) {
        // Lowercase and no spaces
        $reduced = str_replace( ' ', '', mb_strtolower( $term ) );
        // If "iconv" is present...
        if ( function_exists( 'iconv' ) ) :
            // ...this worked for me (TODO: research this)
            setlocale( LC_CTYPE, array( 'pt_BR.utf8', 'en_US.utf8' ) );
            // Convert special chars
            $reduced = iconv( 'UTF-8', 'US-ASCII//TRANSLIT//IGNORE', $reduced );
        endif;
        return $reduced;
    }

    // Add Settings link to plugins - code from GD Star Ratings
    // (as seen in http://www.whypad.com/posts/wordpress-add-settings-link-to-plugins-page/785/ )
    public static function settings_link( $links, $file ) {
        $this_plugin = plugin_basename(__FILE__);
        if ( $file == $this_plugin ) {
            $settings_link = '<a href="' . admin_url( 'options-general.php?page=search_redirections_options' ) . '">' . __( 'Settings', 'search-redirections' ) . '</a>';
            array_unshift( $links, $settings_link );
        }
        return $links;
    }

    public static function admin_init() {
        $p_dir = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/';
        $p_url = WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) ) . '/';
        wp_register_style(
            'search-redirections-admin-css',
            $p_url . 'css/admin.css',
            array(),
            filemtime( $p_dir . 'css/admin.css' )
        );
        wp_register_script(
            'search-redirections-admin-script',
            $p_url . 'js/admin.js',
            array( 'jquery' ),
            filemtime( $p_dir . 'js/admin.js' )
        );
    }
    public static function admin_styles() {
        wp_enqueue_style( 'search-redirections-admin-css' );
    }
    public static function admin_scripts() {
        wp_localize_script( 'search-redirections-admin-script', 'search_redirections', array(
            'remove_group' => __( 'Remove group', 'search-redirections' ),
            'add_rule' => __( 'Add rule', 'search-redirections' ),
            'remove_rule' => __( 'Remove rule', 'search-redirections' ),
            'confirm_removal' => __( 'Are you sure?', 'search-redirections' ),
            'confirm_unload' => __( 'Your unsaved data will be lost. Are you sure?', 'search-redirections' ),
        ) );
        wp_enqueue_script( 'search-redirections-admin-script', false, array( 'jquery' ) );
    }

    public static function after_get_posts() {
        // Saved rules
        $empty_search_dest = get_option( 'search_redirections_empty_search_dest' );
        if ( $empty_search_dest ) {
            global $wp_query;
            if ( $wp_query->is_search() && $wp_query->is_main_query() ) {
                if ( !$wp_query->post_count ) {
                    header( 'Location: ' . $empty_search_dest );
                    exit();
                }
            }
        }
    }

    public static function halt_query( $search,  $q ) {
        if( ! is_admin() && empty( $search ) && $q->is_search() && $q->is_main_query() )
            $search .= ' AND 0 = 1 ';
        return $search;
    }
}

// Initialize
add_action( 'init', array( 'search_redirections', 'init' ) );

endif;

?>
