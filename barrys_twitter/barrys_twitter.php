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
    function __constuct() {
        $barrys_twitter = get_option('barrys_twitter', false);
        if (!$barrys_twitter) {
            $barrys_twitter = new barrys_twitter_settings();
        }
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
