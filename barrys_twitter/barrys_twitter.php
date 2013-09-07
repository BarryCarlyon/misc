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
    protected $_settings;

    function __construct() {
        // use magic wordpress object pie cakeness
        $barrys_twitter = maybe_unserialize(get_option('barrys_twitter', false));
        if (!$barrys_twitter) {
            $barrys_twitter = new barrys_twitter_settings();
        }
        $this->_settings = $barrys_twitter;

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
        echo '<pre>'.print_r($this->_settings,true).'</pre>';
        if ($_POST) {
            foreach ($this->_settings as $var => $val) {
                $this->_settings->$var = isset($_POST[$var]) ? $_POST[$var] : $val;
            }
            $this->_settings->saveOptions();

            echo '
            <div id="setting-error-settings_updated" class="updated settings-error">
                <p><strong>Settings saved.</strong></p>
            </div>
            ';
        }

        echo '
<div class="wrap">
<form action="" method="post">
    <fieldset>
        <div id="icon-options-general" class="icon32"><br></div>
        <h2>Barrys Twitter Settings</h2>
        <table class="form-table">
';
        // start state

        $state = $this->_settings->getState();

        switch ($state) {
            case 1:
                echo '
            <tr valign="top">
                <th scope="row"><label for="consumer_key">Consmer Key</label></th>
                <td><input type="text" name="consumer_key" id="consumer_key" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="consumer_secret">Consmer Secret</label></th>
                <td><input type="text" name="consumer_secret" id="consumer_secret" /></td>
            </tr>
';
                break;
        }

        // end state
        echo '
        </table>
        <p class="submit">
            <input type="submit" value="Update" class="button button-primary" />
        </p>
    </fieldset>
</form>
</div>
';

    }

}

new barrys_twitter;

class barrys_twitter_settings {
    var $consumer_key;
    var $consumer_secret;

    var $oauth_token;
    var $oauth_token_secret;
//    var $oauth_verifier;

    function saveOptions() {
        $data = serialize($this);
        update_option('barrys_twitter', $data);
    }

    function getState() {
        if ($this->consumer_key && $this->consumer_secret) {
            // have basic keys
            if ($this->oauth_token && $this->oauth_token_secret) {
                // have oauth keys

                // verify then
                return 3;
            }

            // need to loop the loop
            return 2;
        }

        // need basoics
        return 1;
    }
}
