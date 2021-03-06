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

        include(__DIR__ . '/twitteroauth/twitteroauth.php');

        if (is_admin()) {
            $this->_admin();
        } else if (isset($_GET['barrys_twitter'])) {
            // cronism
            $this->_update_json();
        }
    }

    private function _admin() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_barrys_twitter_tweet', array($this, 'send_tweet'));
        wp_enqueue_script('twitter_widgets', 'https://platform.twitter.com/widgets.js');
    }

    function admin_menu() {
        add_menu_page('Barrys Twitter', 'Barrys Twitter', 'edit_pages', 'barrys_twitter', array($this, 'admin_page'), plugins_url('twitter.ico', __FILE__));
    }

    function admin_page() {
        $action = isset($_REQUEST['action']) ? strtolower($_REQUEST['action']) : false;

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

        // master reset
        if ($action == 'reset') {
            $this->_settings->reset();
        } else if ($action == 'resetfull') {
            $this->_settings->reset(true);
        }

        // check for get return keys
        if ($action == 'callback') {
            // twitter callback
            $token = $_GET['oauth_token'];
            $verify = $_GET['oauth_verifier'];

            $this->_settings->temp = false;

            if ($this->_settings->oauth_token != $token) {
                echo '
            <div id="setting-error-settings_updated" class="updated settings-error">
                <p><strong>Token Mismatch</strong></p>
            </div>
                ';
            } else {
                // build from temp
                $connection = new TwitterOAuth(
                    $this->_settings->consumer_key,
                    $this->_settings->consumer_secret,
                    $this->_settings->oauth_token,
                    $this->_settings->oauth_token_secret
                );

                // exchange tokens
                $token_credentials = $connection->getAccessToken($_REQUEST['oauth_verifier']);

                // build
                $connection = new TwitterOAuth(
                    $this->_settings->consumer_key,
                    $this->_settings->consumer_secret,
                    $token_credentials['oauth_token'],
                    $token_credentials['oauth_token_secret']
                );
                $account = $connection->get('account/verify_credentials');

                if (isset($account->errors)) {
                        echo '
            <div id="setting-error-settings_updated" class="updated settings-error">
                <p>
                    <strong>Connection Failed</strong>
                    <br />
                    ' . $account->errors[0]->code . ' -- ' . $account->errors[0]->message . '
                </p>
            </div>
                        ';

/*
                    $this->_settings->oauth_token = '';
                    $this->_settings->oauth_token_secret = '';
                    $this->_settings->temp = false;
                    $this->_settings->saveOptions();
                    */
                    if ($account->errors[0]->code != 88) {
                        $this->_settings->reset();
                    }
                } else {
                    // all good
                    $this->_settings->oauth_token = $token_credentials['oauth_token'];
                    $this->_settings->oauth_token_secret = $token_credentials['oauth_token_secret'];
                    $this->_settings->temp = false;
                    $this->_settings->saveOptions();

                    echo '
            <div id="setting-error-settings_updated" class="updated settings-error">
                <p><strong>Connection OK - Hello ' . ($account->name ? $account->name : $account->screen_name) . '</strong></p>
            </div>
                    ';
                    $this->_update_json(true);
                }
            }
        }

?>
<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery('#tweet_send').click(function(e) {
        e.preventDefault();

        var data = {
            action: 'barrys_twitter_tweet',
            tweet: jQuery('#tweet').val()
        }

        jQuery('#tweet').val('').attr('disabled', 'disabled');
        jQuery('#tweet_send').val('Sending').attr('disabled', 'disabled');

        jQuery.post(ajaxurl, data, function(resp) {
            jQuery(resp.html).prependTo('#tweet_response');
            jQuery('#tweet_send').val('Tweet').removeAttr('disabled');
            jQuery('#tweet').removeAttr('disabled');

            twttr.widgets.load();
        }, 'json');
    });
});
</script>
<?php

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
            case 3:
                echo '
            <tr valign="top">
                <td></td>
                <td>
                    All is Good<br />Hello ' . $this->_settings->username . '
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Send a Tweet</th>
                <td><textarea rows="6" class="large-text" name="tweet" id="tweet"></textarea></td>
            </tr>
            <tr valign="top">
                <td></td>
                <td>
                    <input type="button" class="button button-primary" id="tweet_send" value="Tweet" />
                    <br />
                    <div id="tweet_response"></div>
                </td>
            </tr>
';
                // force an update
                echo '
            <tr valign="top">
                <td>Fetch Status</td>
                <td>
                    ' . $this->_update_json(true) . '
                </td>
            </tr>
';
                break;
            case 2:
                // we got keys build and redirect
                $connection = new TwitterOAuth($this->_settings->consumer_key, $this->_settings->consumer_secret);
                $temporary_credentials = $connection->getRequestToken(admin_url('admin.php?page=barrys_twitter&action=callback'));
                $redirect_url = $connection->getAuthorizeURL($temporary_credentials);

                $this->_settings->oauth_token = $temporary_credentials['oauth_token'];
                $this->_settings->oauth_token_secret = $temporary_credentials['oauth_token_secret'];
                $this->_settings->temp = true;
                $this->_settings->saveOptions();

                echo '
            <tr valign="top">
                <td></td>
                <td>
                    <a href="' . $redirect_url . '">Authenticate with Twitter</a>
                </td>
            </tr>
';

                break;
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
            ';
            if ($state != 3) {
                echo '<input type="submit" value="Update" class="button button-primary" />';
            }
            if ($state == 2) {
                echo '<input type="submit" name="action" value="Reset" class="button button-secondary" />';
            }
            echo '
            <input type="submit" name="action" value="ResetFull" class="button button-secondary" />
        </p>
    </fieldset>
</form>
</div>
';

    }

    // ajax
    function send_tweet() {
        $tweet = stripslashes($_POST['tweet']);

        if ($this->_settings->getState() == 3) {
            $connection = $this->_settings->connection;

            $status = $connection->post('statuses/update', array('status' => $tweet));
            if (isset($status->errors)) {
                foreach ($status->errors as $error) {
                    echo $error->code . ' -- ' . $error->message . '<br />';
                }
            } else {
//                echo $connection->get('statuses/oembed', array('id' => $status->id, 'omit_script' => 'true'))->html;
                $result = $connection->get('statuses/oembed', array('id' => $status->id, 'omit_script' => 'true'));
                echo json_encode($result);
            }
        } else {
            echo 'Unable to send - I am not configured properly';
        }

        die();
    }
    // end

    // shell/cron
    function _update_json($admin = false) {
        if ($this->_settings->getState() == 3) {
            $json_target = wp_upload_dir();
            $target = $json_target['basedir'];

            if (is_file($target . '/twitter.json')) {
                $mtime = filemtime($target . '/twitter.json');
                if ($mtime > (time() - 60)) {
                    $r = 'Too Soon';
                    if ($admin) {
                        return $r;
                    }
                    echo $r;
                    exit;
                }
            }

            // suprress the display error
            $fp = @fopen($target . '/twitter.json', 'w');
            if ($fp) {
                $tweet = $this->_settings->account;
                if (isset($tweet->errors)) {
                    $r = print_r($tweet->errors, true);
                } else {
                    $tweet = json_encode($tweet);
                    fwrite($fp, $tweet);
                    fclose($fp);
                    $r = 'OK';
                }
            } else {
                $r = 'Cannot write to ' . $target . '/twitter.json';
            }

//            if (isset($json_target['error']) && $json_target['error']) {
//                echo $json_target['error'];
//            } else {
//                echo '<pre>';
//                print_r($json_target);
//            }
        } else {
            $r = 'Not configured';
        }
        if ($admin) {
            return $r;
        }
        echo $r;
        exit;
    }
}

new barrys_twitter;

class barrys_twitter_settings {
    var $consumer_key;
    var $consumer_secret;

    var $oauth_token;
    var $oauth_token_secret;
    var $temp;

    var $request_token_url = 'https://api.twitter.com/oauth/request_token';
    var $authorize_url = 'https://api.twitter.com/oauth/authorize';
    var $access_token_url = 'https://api.twitter.com/oauth/access_token';

    function reset($full = false) {
        if ($full) {
            $this->consumer_key = '';
            $this->consumer_secret = '';
        }
        $this->oauth_token = '';
        $this->oauth_token_secret = '';
        $this->temp = false;
        $this->saveOptions();
    }

    function saveOptions() {
        if (isset($this->connection)) {
            unset($this->connection);
        }
        if (isset($this->id)) {
            unset($this->id);
        }
        if (isset($this->username)) {
            unset($this->username);
        }
        if (isset($this->account)) {
            unset($this->account);
        }
        $data = serialize($this);
        update_option('barrys_twitter', $data);
    }

    function getState() {
        if ($this->consumer_key && $this->consumer_secret) {
            // have basic keys
            if ($this->oauth_token && $this->oauth_token_secret) {
                if ($this->temp) {
                    return 2;
                }

                // have oauth keys
                $this->connection = new TwitterOAuth(
                    $this->consumer_key,
                    $this->consumer_secret,
                    $this->oauth_token,
                    $this->oauth_token_secret
                );
//                $account = $this->connection->get('account/verify_credentials');
//                $this->account = $account;

//                $rate = $this->connection->get('application/rate_limit_status');
//                echo '<pre>'.print_r($rate,true).'</pre>';

                if (isset($account->errors)) {
                    if (is_admin()) {
                        echo '
            <div id="setting-error-settings_updated" class="updated settings-error">
                <p>
                    <strong>Connection Failed at the Test Stage</strong>
                    <br />
                    ' . $account->errors[0]->code . ' -- ' . $account->errors[0]->message . '
                </p>
            </div>
                        ';
                    }
                    // le fail
                    return 4;
                }

                // all good
//                $this->id = $account->id;
//                $this->username = ($account->name ? $account->name : $account->screen_name);

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
