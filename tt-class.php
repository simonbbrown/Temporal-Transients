<?php

class TemporalTransients {

    public $tt_settings = array();
    public $tt_time;
    public $tt_version = 0.1;
    public $wp_version;

    public function __construct() {

        // We make the WordPress version available just in case someone is using a legacy version
        global $wp_version;
        $this->wp_version = $wp_version;

        // Set the default transient life time to 24 hours
        $this->tt_time = 60 * 60 * 24;

        // Set up the default settings
        $this->set_settings();

        // Apply TT specific filters!
        $this->tt_filters();

        // Do the WordPress filters!
        $this->tt_add_filters();

    }

    /**
     * Sets the default settings for the plugin
     *
     * This can be overridden via the tt_settings filter set up in tt_filters
     */
    public function set_settings() {
        $this->tt_settings = array(
            "navigation" => true
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

}