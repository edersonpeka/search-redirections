<?php
/*
Plugin Name: Search Redirections
Plugin URI: https://peka.wordpress.com
Description: Create redirect rules for any given search terms.
Author: Ederson Peka
Version: 0.1.1
Author URI: https://ederson.peka.nom.br
Text Domain: search-redirections
*/

if ( !class_exists( 'search_redirections' ) ) :

class search_redirections {

    // Init
    function init() {
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
    }

    // Creates options using Settings API
    function options_init() {
        // One settings section...
     	add_settings_section(
		    'search_redirections_section',
		    __( 'Search Redirections', 'search-redirections' ),
		    array( __CLASS__, 'settings_description' ),
		    'search_redirections_options'
	    );
	    // ...with only one settings field
	    add_settings_field(
		    'search_redirections_rules',
		    __( 'Rules', 'search-redirections' ),
		    array( __CLASS__, 'rules_field' ),
		    'search_redirections_options',
		    'search_redirections_section',
		    array( 'slug' => 'search_redirections_rules', 'label_for' => 'search_redirections_rules' )
	    );
        register_setting( 'search_redirections_options', 'search_redirections_rules' );

        // Create "settings" link for this plugin on plugins list
        add_filter( 'plugin_action_links', array( __CLASS__, 'settings_link' ), 10, 2 );
    }
    function settings_description() {
        // void
    }
    
    // Returns array of saved rules (terms and destination)
    function get_rules() {
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

    // Build form fields for rules (TODO: javascript interface)
    function rules_field() {
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

    function add_options_page() {
        $_page = add_options_page( __( 'Search Redirections', 'search-redirections' ), __( 'Search Redirections', 'search-redirections' ), 'edit_theme_options', 'search_redirections_options', array( __CLASS__, 'render_options_page' ) );
        add_action( 'admin_print_styles-' . $_page, array( __CLASS__, 'admin_styles' ) );
        add_action( 'admin_print_scripts-' . $_page, array( __CLASS__, 'admin_scripts' ) );
    }

    function render_options_page() {
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
    function modify_search_term( $request_vars ) {
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
                        die();
                    endif;
                endif;
            endforeach;
        endif;
        // Restore environment locale
        if ( function_exists( 'iconv' ) ) setlocale( LC_CTYPE, $orig_locale );
        return $request_vars;
    }
    
    // Prepare string for comparison: lowercase, no spaces, etc.
    function reduce_term( $term ) {
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
    function settings_link( $links, $file ) {
        $this_plugin = plugin_basename(__FILE__);
        if ( $file == $this_plugin ) {
            $settings_link = '<a href="' . admin_url( 'options-general.php?page=search_redirections_options' ) . '">' . __( 'Settings', 'search-redirections' ) . '</a>';
            array_unshift( $links, $settings_link );
        }
        return $links;
    }

    function admin_init() {
        $p_dir = WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) ) . '/';
        wp_register_style( 'search-redirections-admin-css', $p_dir . 'css/admin.css' );
        wp_register_script( 'search-redirections-admin-script', $p_dir . 'js/admin.js' );
    }
    function admin_styles() {
        wp_enqueue_style( 'search-redirections-admin-css' );
    }
    function admin_scripts() {
        wp_localize_script( 'search-redirections-admin-script', 'search_redirections', array(
            'remove_group' => __( 'Remove group', 'search-redirections' ),
            'add_rule' => __( 'Add rule', 'search-redirections' ),
            'remove_rule' => __( 'Remove rule', 'search-redirections' ),
            'confirm_removal' => __( 'Are you sure?', 'search-redirections' ),
            'confirm_unload' => __( 'Your unsaved data will be lost. Are you sure?', 'search-redirections' ),
        ) );
        wp_enqueue_script( 'search-redirections-admin-script', false, array( 'jquery' ) );
    }
}

// Initialize
add_action( 'init', array( 'search_redirections', 'init' ) );

endif;

?>
