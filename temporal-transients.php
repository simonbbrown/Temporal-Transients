<?php
/**
 * Plugin Name: Temporal Transients
 * Plugin URI: http://www.simonbrown.com.au
 * Description: A Simple WordPress Plugin to help speed up your WordPress website by setting Transients for some of the more greedy functions
 * Version: 0.1
 * Author: Simon Brown
 * Author URI: http://www.simonbrown.com.au
 * License: GPLv2 or later
 * Copyright: Simon Brown
 */

include( plugin_dir_path( __FILE__ ) . 'tt-class.php');
$TemporalTransients = new TemporalTransients(true);

register_activation_hook( __FILE__, array($TemporalTransients, 'install') );

function tt_the_content($more_link_text = null, $strip_teaser = false) {

    $TemporalTransients = new TemporalTransients();
    $TemporalTransients->tt_the_content($more_link_text, $strip_teaser);

}