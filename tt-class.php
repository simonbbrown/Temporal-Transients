<?php

class TemporalTransients {

    public $tt_settings = array();
    public $tt_time;
    public $tt_version = 0.1;
    public $wp_version;

    /**
     * Start it going!
     * Pass in true if its the first load so we are not doubling up and running
     * WordPress filters more than required.
     *
     * @param bool $load
     */
    public function __construct($load = false) {

        // We make the WordPress version available just in case someone is using a legacy version
        global $wp_version;
        $this->wp_version = $wp_version;

        // Set the default transient life time to 24 hours
        $this->tt_time = 60 * 60 * 24;

        // Set up the default settings
        $this->set_settings();

        // Apply TT specific filters!
        $this->tt_filters();

        if ($load) {
            // Do the WordPress filters!
            $this->tt_add_filters();
        }

        if(is_admin()) {
            add_action( 'admin_init', array($this, 'tt_admin_init'));
            add_action( 'admin_footer', array($this, 'tt_admin_ajax_purge_transients_javascript_css') );
            add_action( 'wp_ajax_tt_purge_transients', array($this, 'tt_admin_ajax_purge_transients_action_callback') );
        }

    }

    public function tt_admin_init() {
        add_settings_section( 'tt_purge_cache_section', 'Temporal Transients - Purge All Transients', array($this,'tt_admin_purge_transients_section'), 'general' );
        add_settings_field( 'tt_purge_cache_field', 'Purge Transients', array($this,'tt_admin_purge_transients_field'), 'general', 'tt_purge_cache_section');
    }

    /**
     * Sets the default settings for the plugin
     *
     * This can be overridden via the tt_settings filter set up in tt_filters
     */
    public function set_settings() {
        $this->tt_settings = array(
            "navigation" => true,
            "the_content" => true
        );
    }

    public function install() {

    }

    /**
     * Custom Filters to allow plugins and themes to manipulate the TT plugin settings
     */
    public function tt_filters() {

        /**
         * Filter whether to change the Transient Default time from 24h
         *
         * @since 0.1
         *
         * @param int $this->tt_time An int containing the new default Transient Time
         */
        $this->tt_time = apply_filters( 'tt_life_time', $this->tt_time );

        /**
         * Filter the plugin settings
         * Allows users to turn on or off a variety of transients
         *
         * @since 0.1
         *
         * @param array $this->tt_settings An array used in tt_add_filters to determine if a transient should be created
         */
        $this->tt_settings = apply_filters( 'tt_settings', $this->tt_settings );


    }

    /**
     * Execute the WordPress filters.
     *
     * This is the grunt of the Plugin where Transients are Stored, Returned and Distroyed based on the rules set out
     */
    public function tt_add_filters() {

        // Make sure that our users are running a compatible version of WordPress
        if (version_compare($this->wp_version, '3.9', '>=')) {

            // Navigation filters
            if (!empty($this->tt_settings['navigation'])) {
                add_filter( 'pre_wp_nav_menu', array($this, 'tt_pre_wp_nav_menu'), 10, 2);
                add_filter( 'wp_nav_menu', array($this,'tt_wp_nav_menu'), 10, 2);
                add_action( 'wp_update_nav_menu', array($this,'tt_wp_update_nav_menu'), 10, 1);
                add_filter( 'pre_set_theme_mod_nav_menu_locations', array($this, 'tt_pre_set_theme_mod_nav_menu_locations'),10, 2);
            }

            // Content filters
            if (!empty($this->tt_settings['the_content'])) {
                add_action( 'save_post', array($this, 'tt_save_post'));
            }

        }

    }

    /**
     * Retrieve the Menu if it has a existing Transient and is still valid
     *
     * @param $output
     * @param $args
     * @return mixed
     */
    public function tt_pre_wp_nav_menu($output, $args) {

        $transient = get_transient('tt_nav_'.$args->theme_location);

        // Get transient returns false if nothing was found, we need to only return if it has a value that is not false
        if ($transient) {
            return $transient;
        }

        // We need to return null if the transient is false otherwise no menu will be rendered.
        return null;

    }

    /**
     * Grabs the generated menu and stores it
     *
     * @param $nav_menu
     * @param $args
     * @return mixed
     */
    public function tt_wp_nav_menu($nav_menu, $args) {

        set_transient('tt_nav_'.$args->theme_location, $nav_menu, $this->tt_time);

        return $nav_menu;

    }

    /**
     * Make sure we clear out the old transient any time we update a particular menu
     *
     * @param $menu_id
     */
    public function tt_wp_update_nav_menu($menu_id) {

        $menus = get_nav_menu_locations();

        foreach ($menus as $location => $menu) {
            if ($menu == $menu_id) {
                delete_transient('tt_nav_'.$location);
            }
        }

    }


    /**
     * When someone updates the Menu Locations than we need to remove the transient for said menu also
     *
     * @param $value
     * @param $old_value
     * @return mixed
     */
    public function tt_pre_set_theme_mod_nav_menu_locations($value, $old_value) {

        // Just in case something happens like they somehow remove all their menus
        if ($old_value == false) {

            foreach($value as $location => $menu_id ) {
                delete_transient('tt_nav_'.$location);
            }

        } else {

            // And this will do everything we want when the menu locations are updated
            foreach ($old_value as $location => $menu_id) {
                if ($menu_id != $value[$location]) {
                    delete_transient('tt_nav_' . $location);
                }
            }

        }

        return $value;

    }

    /**
     * An Override for the_content
     *
     * Requires users to edit their themes
     *
     */
    public function tt_the_content( $more_link_text = null, $strip_teaser = false) {

        $id = get_the_ID();

        if ($this->tt_settings['the_content'] === true) {

            // We hash the passed in variable as well as the post id in order to make sure that we are
            // always getting the right information back from the transient API
            $hash = wp_hash($more_link_text . $strip_teaser . $id);

            $transient = get_transient('tt_content_' . $hash);

            // If we get a transient back then echo that out and don't bother generating anything more
            if ($transient) {
                echo $transient;
                return;
            }

        }

        $content = get_the_content( $more_link_text, $strip_teaser );

        /**
         * Filter the post content.
         *
         * @since 0.71
         *
         * @param string $content Content of the current post.
         */
        $content = apply_filters( 'the_content', $content );
        $content = str_replace( ']]>', ']]&gt;', $content );

        if ($this->tt_settings['the_content'] === true) {

            $content_directory = get_transient( 'tt_content_directory' );

            if (!$content_directory) {
                $content_directory = array();
            }

            // Save this content for use later on
            if ( set_transient('tt_content_' . $hash, $content, $this->tt_time) ) {
                // Store a record of the hash and the page id for deletion when page is being updated.
                // Store it for 1 year because I'm paranoid!
                $content_directory[$hash] = $id;
                set_transient('tt_content_directory', $content_directory, 60*60*24*365);
            };

        }

        echo $content;
    }

    /**
     * Deletes a transient for a post when tt_the_content has been used to call it
     *
     * @param $post_id
     * @param $post
     * @param $update
     */
    public function tt_save_post($post_id) {

        $content_directory = get_transient('tt_content_directory');

        foreach ($content_directory as $hash => $id) {
            if ($id == $post_id) {
                delete_transient('tt_content_'.$hash);
                unset($content_directory[$hash]);
            }
        }

        set_transient('tt_content_directory', $content_directory, 60*60*24*365);

    }

    /**
     * Add in our button to execute our action to purge transients via Ajax
     * Added to the General Options page
     *
     */
    public function tt_admin_purge_transients_field() {
        echo '<button id="tt_delete_transients_ajax" class="button button-primary">Purge!</button>';
    }

    /**
     * Add in our Section where out Ajax button will live
     * Added to the General Options page
     *
     */
    public function tt_admin_purge_transients_section() {
        echo '<p>Purge all stored Temporal Transients<br/> Use this if your content is not being refreshed after updating your content.</p>';
    }

    /**
     * Adds in required JS and CSS to the admin footer
     *
     */
    public function tt_admin_ajax_purge_transients_javascript_css() {
        $nonce = wp_create_nonce( "tt_purge_transients" );
        ?>

        <style>
            @-ms-keyframes spin {
                from { -ms-transform: rotate(0deg); }
                to { -ms-transform: rotate(360deg); }
            }
            @-moz-keyframes spin {
                from { -moz-transform: rotate(0deg); }
                to { -moz-transform: rotate(360deg); }
            }
            @-webkit-keyframes spin {
                from { -webkit-transform: rotate(0deg); }
                to { -webkit-transform: rotate(360deg); }
            }
            @keyframes spin {
                from {
                    transform:rotate(0deg);
                }
                to {
                    transform:rotate(360deg);
                }
            }
            #tt_delete_transients_ajax span.spin {
                padding: 3px 0;
                
                -webkit-animation-name: spin;
                -webkit-animation-duration: 4000ms;
                -webkit-animation-iteration-count: infinite;
                -webkit-animation-timing-function: linear;
                -moz-animation-name: spin;
                -moz-animation-duration: 4000ms;
                -moz-animation-iteration-count: infinite;
                -moz-animation-timing-function: linear;
                -ms-animation-name: spin;
                -ms-animation-duration: 4000ms;
                -ms-animation-iteration-count: infinite;
                -ms-animation-timing-function: linear;

                animation-name: spin;
                animation-duration: 4000ms;
                animation-iteration-count: infinite;
                animation-timing-function: linear;
            }
        </style>

        <script type="text/javascript" >
            jQuery(document).ready(function($) {

                var data = {
                    'action': 'tt_purge_transients',
                    'nonce': '<?php echo $nonce; ?>'
                };

                $('#tt_delete_transients_ajax').on('click', function(a) {
                    a.preventDefault();
                    var e = this;

                    $(e).html('Purging <span class="dashicons dashicons-update spin"></span>');

                    $.post(ajaxurl, data, function(response) {

                        console.log(response);

                        if(response >= 0) {
                            $(e).html('Purged - Purge again');
                        } else {
                            $(e).replaceWith('<div class="error"><p>An Error Occured, Please refresh the page and try again</p></div>');
                        }

                    });

                });

            });
        </script>

        <?php
    }

    /**
     * Our action for Purging Temporal Transients items
     * Uses WordPress nonce to ensure security
     *
     * returns -1 if the nonce does not clear
     * returns number of rows deleted when query is run
     *
     */
    public function tt_admin_ajax_purge_transients_action_callback() {

        check_ajax_referer('tt_purge_transients', 'nonce');

        global $wpdb;

        $query = "DELETE FROM $wpdb->options WHERE `option_name` LIKE '_transient_tt%'";
        $result = $wpdb->query($query);

        echo $result;

        wp_die();

    }
}