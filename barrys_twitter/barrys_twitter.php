<?php

/*
Plugin Name: Barrys Twitter
Plugin URI: http://barrycarlyon.co.uk/
Description: Twitter le fuck?
Author: Barry Carlyon
Author URI: http://barrycarlyon.co.uk/
Version: 0.0.1
*/

class barrys_twitter {
    function __construct() {
        $barrys_twitter = get_option('barrys_twitter', false);
        if (!$barrys_twitter) {
            $barrys_twitter = new barrys_twitter_settings();
        }

        if (is_admin()) {
            $this->_admin();
        }
    }

    private function _admin() {
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    function admin_menu() {
        add_menu_page('Barrys Twitter', 'Barrys Twitter', 'activate_plugins', 'barrys_twitter', array($this, 'admin_page'), plugins_url('twitter.ico', __FILE__));
    }

    function admin_page() {
        echo 'hey hey hey';
    }

}

new barrys_twitter;

class barrys_twitter_settings {
    var $consumer_key;
    var $consumer_secret;

    var $oauth_token;
    var $oauth_token_secret;
//    var $oauth_verifier;

    function save_options() {
        $data = json_encode($this);
        update_option('barrys_twitter');
    }
}
