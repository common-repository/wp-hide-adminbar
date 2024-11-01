<?php
/**
 * Plugin Name: WP Hide Admin Bar
 * Plugin URI:  http://wordpress.org/plugins/wp-hide-adminbar
 * Description: Hide admin bar based on selected user roles and user capabilities.
 * Tags:        hide admin bar, wp hide admin bar, admin bar hide, hide admin-bar based on user roles, hide admin-bar based on user capabilities
 * Version:     1.0.2
 * Author:      P. Roy
 * Author URI:  https://www.proy.info
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-hide-adminbar
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

class Wp_Hide_Adminbar {

    protected $plugin_name;
    protected $version;
    public $nonceName = 'wphideadminbar_options';
    public $hiddenItemsOptionName = 'wphab_settings';

    public function __construct() {

        $this->plugin_name = 'wp-hide-adminbar';
        $this->version = '1.0.0';
        //$this->load_dependencies();
        //$this->load_options();

        add_action( 'admin_init', array( $this, 'admin_init' ) );

        add_action( 'admin_menu', array( $this, 'addMenuPages' ) );
        //add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 1 );

        add_filter( 'show_admin_bar' , array( $this, 'func_Hide_AdminBar') );

    }

    public function admin_init() {
        if ( is_admin() ) {

            ob_start();// this is require to resolve redirect issue
            add_action( 'admin_head', array( $this, 'ckbCheckToggle' ) );
        }

    }

    public function ckbCheckToggle() {
        ?>
        <script>
            (function($) {

                $(function() {

                    $("#ckbCheckAll").click(function () {
                        $(".checkBoxClass").prop('checked', $(this).prop('checked'));
                    });

                    $(".checkBoxClass").change(function(){
                        if (!$(this).prop("checked")){
                            $("#ckbCheckAll").prop("checked",false);
                        }else{
                            var allcheckd = true;
                            $('.checkBoxClass').each(function() {
                                if (!$(this).prop("checked")){
                                    allcheckd = false;
                                }
                            });
                            if(allcheckd === true) $("#ckbCheckAll").prop("checked",true);
                        }
                    });

                });
            })(jQuery);
        </script>
        <?php
    }

    public function addMenuPages()  {

        add_options_page(
            __('Hide Admin Bar', $this->plugin_name),
            __('Hide Admin Bar', $this->plugin_name),
            'manage_options',
            $this->plugin_name . '_options',
            array(
                $this,
                'settingsPage'
            )
        );

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'plugin_settings_link'), 10, 2 );

    }

    public function plugin_settings_link($links, $file) {
        $settings_link = '<a href="options-general.php?page='.$this->plugin_name . '_options">' . __('Settings', $this->plugin_name) . '</a>';
        array_unshift($links, $settings_link); // before other links
        return $links;
    }


    public function settingsPage() {

        $this->saveSettings();
        $wphadb_settings = get_option($this->hiddenItemsOptionName);

        //print_r($wphadb_settings);
        ?>
        <style>

            .wrap td, .wrap th { text-align: left; }
            .form-table-wphab{ padding: 10px; margin-bottom: 20px; }
            .form-table-wphab th { padding: 5px; border-bottom: 1px solid #DFDFDF; }
            .form-table-wphab td  { padding: 5px; border-bottom: 1px solid #DFDFDF; }
            .form-table-wphab tr:last-child td  { border-bottom: 0;}
            ul.wpuserRoles { column-count: 3; padding: 5px 0; list-style: none; }

        </style>
        <div class="wrap">
            <h2><?php echo __('WP Hide Admin Bar Settings');?></h2>
            <p>Hide admin bar based on selected user roles and user capabilities.</p>
            <form action="<?php echo esc_attr(admin_url('options-general.php?page=wp-hide-adminbar_options')); ?>" method="post">
                <?php wp_nonce_field($this->nonceName, $this->nonceName, true, true); ?>
                <table class="form-table-wphab">
                <tbody>
                    <tr>
                        <th scope="row"><label for="disableforall"><?php echo __('Hide Admin Bar');?></label></th>
                        <td class="rolesList">
                            <?php
                                $disableForAll = ( isset($wphadb_settings["wphab_disableforall"]) ) ? $wphadb_settings["wphab_disableforall"] : "";
                                echo '<label class="allLabel"><input name="disableForAll" id="ckbCheckAll" '.( $disableForAll == 'yes' ? "checked" : "").' value="yes" type="checkbox" class="">All Roles</label>';
                            ?>
                            </td><td></td>
                    </tr>
                    <?php //if( $disableForAll == "no" || empty($disableForAll) ) { ?>
                    <tr>
                        <th scope="row"><label for="userroles"><?php echo __('Select User Roles');?></label><p style="margin:0px; font-weight: normal;">Hide admin bar for selected user roles</p></th>
                        <td>
                            <ul class="wpuserRoles">
                                <?php
                                    global $wp_roles;
                                    $exRoles = ( isset($wphadb_settings["wphab_userRoles"]) ) ? $wphadb_settings["wphab_userRoles"] : "";
                                    $checked = '';
                                    $roles = $wp_roles->get_names();
                                    if( is_array( $roles ) ) {
                                        foreach( $roles as $key => $value ):
                                            if( is_array($exRoles) )
                                                $checked = ( in_array($key, $exRoles) ) ? "checked" : "";
                                            echo '<li><label class="roleLabel"><input name="userRoles[]" '.$checked.' type="checkbox" value="'.$key.'" class="regular-checkbox checkBoxClass">'.$value.'</label></li>';
                                        endforeach;
                                    }
                                ?>
                            </ul>
                            </td><td></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="usercapabilities"><?php echo __('User Capabilities Blacklist'); ?><p style="margin:0px; font-weight: normal;">(use Comma-Separated) <br /><br />Hide admin bar for selected user capabilities</p></label></th>
                        <td class="rolesList">
                            <?php
                                $caps = (isset($wphadb_settings["wphab_capabilities"])) ? $wphadb_settings["wphab_capabilities"] : "";
                                echo '<label class="roleLabel"><textarea name="userCapabilities" id="capabilties" class="regular-text" rows="7">'.$caps.'</textarea></label>';
                            ?>
                        </td>
                        <td width="300">
                            Example:<br>switch_themes, edit_themes, activate_plugins, edit_plugins, edit_users, manage_options, moderate_comments, manage_categories, edit_posts, edit_others_posts, edit_published_posts, publish_posts, edit_pages, read, edit_others_pages, edit_published_pages, publish_pages, edit_private_posts, edit_private_pages, read_private_pages, install_plugins, list_users
                            </td>
                    </tr>


                    <?php //} ?>

                </tbody>
                </table>
                <input type="submit" class="button button-primary" value="<?php esc_html_e('SAVE CHANGES', $this->pluginName); ?>"/>
                <hr>
                <?php echo esc_html_e('This Plugin Developed by ',$pluginName);?><a href="https://www.proy.info" target="_blank">P. Roy</a>
            </form>
        </div>
        <?php

    }

    private function saveSettings() {
        global $menu;

        if (!isset($_POST[$this->nonceName])) {
            return false;
        }

        $verify = check_admin_referer($this->nonceName, $this->nonceName);

        $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        //print_r($_POST); exit;
        //$userRoles =      array_map( 'esc_attr', $_POST['userRoles'] );

        $itemsToSave = array();
        $itemsToSave['wphab_disableforall'] =  $_POST['disableForAll'];
        $itemsToSave['wphab_userRoles'] =      $_POST['userRoles'];
        $itemsToSave['wphab_capabilities'] =   $_POST['userCapabilities'];
        //$itemsToSave['wphab_auto_hide_time'] = $_POST['auto_hide_time'];
        //$itemsToSave['wphab_auto_hide_flag'] = $_POST['autoHideFlag'];

        //update the option and set as autoloading option
        update_option($this->hiddenItemsOptionName, $itemsToSave, true);

        // we'll redirect to same page when saved to see results.
        // redirection will be done with js, due to headers error when done with wp_redirect
        //$adminPageUrl = admin_url('options-general.php?page='.$this->pluginName.'_options&saved='.$savedSuccess);
        //wp_safe_redirect( $adminPageUrl ); exit;
        //ob_end_clean();
        wp_safe_redirect( add_query_arg('updated', 'true', wp_get_referer() ) );
    }

    public function func_Hide_AdminBar(){
        $wphadb_settings = get_option($this->hiddenItemsOptionName);

        if($wphadb_settings["wphab_disableforall"] == 'yes') return false;

        //print_r($wphadb_settings); exit;
        $user = wp_get_current_user();
        if(count($user->roles) == 0) return false;
        if(isset($wphadb_settings["wphab_userRoles"]) && count($wphadb_settings["wphab_userRoles"]) > 0){
            foreach ( $wphadb_settings["wphab_userRoles"]  as $hide_for_role ) {
                if ( in_array( $hide_for_role, (array) $user->roles ) ) {
                    return false;
                }
            }
        }

        $wphab_capabilities = array_map('trim', explode(',', $wphadb_settings["wphab_capabilities"]));

        //print_r($wphab_capabilities); exit;

        if(count($wphab_capabilities) > 0){
            foreach ( $wphab_capabilities  as $hide_for_caps ) {
                if (current_user_can($hide_for_caps)) {
                    return false;
                }
            }
        }

        return true;
    }




}

new Wp_Hide_Adminbar();
