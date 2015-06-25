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
$TemporalTransients = new TemporalTransients();

register_activation_hook( __FILE__, array($TemporalTransients, 'install') );

