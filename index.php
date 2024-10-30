<?php

/**
 * Plugin Name: Moosend Landing Pages
 * Plugin URI: https://moosend.com/landing-pages/
 * Description: Import your Moosend Landing pages into WordPress with just one click.
 * Version: 1.1.6
 * Author: Moosend
 * Author URI: https://moosend.com
 * Requires at least: 4.1
 *
 * Text Domain: moosend_landing
 *
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function run_moosend_landings() {
    require_once plugin_dir_path( __FILE__ ) . 'MooLandings.php';


    $plugin = new MooLandings();
}
run_moosend_landings();
