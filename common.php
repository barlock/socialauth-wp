<?php
//locating HybridAuth
if(!file_exists( dirname(__FILE__) . '/hybridauth/Hybrid/Auth.php')) {
    die( sprintf( __( "Sorry, but you can not install Plugin 'SocialAuth-WP'. It seems you missed to add 'hybrid auth' library with this plugin.") ));
}
define('SOCIALAUTH_WP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SOCIALAUTH_WP_HYBRIDAUTH_DIR_PATH', SOCIALAUTH_WP_PLUGIN_PATH . '/hybridauth/');

$HA_PROVIDER_CONFIG = array();
$HA_CONFIG = get_ha_config();

if(isset($HA_CONFIG) && is_array($HA_CONFIG)) {
    $HA_PROVIDER_CONFIG = $HA_CONFIG;
} else {
    $HA_PROVIDER_CONFIG = array();
}

function get_ha_config(){
    return include_once SOCIALAUTH_WP_HYBRIDAUTH_DIR_PATH . '/config.php';
}

function compare_displayOrder($a, $b)
{
    if(!isset($a['display_order']) || !isset($b['display_order']))
        return 0;
    if ($a['display_order'] == $b['display_order']) {
        return 0;
    }
    return ($a['display_order'] < $b['display_order']) ? -1 : 1;
}

function sendEmailVerificationEmail($to, $user_id, $emailVerificationHash, $subject= 'Email verification for new account', $message = null)
{
	$headers = "";
	$attachments = "";
	
	include_once dirname(__FILE__) . "/email_verification_template.php";
	wp_mail( $to, $subject, $message, $headers, $attachments );
}

function endAuthProcessAndRedirectToHomePage($user_home_page)
{
	$authDialogPosition = get_option('SocialAuth_WP_authDialog_location');
    if(!empty($authDialogPosition) && $authDialogPosition == 'page')
    {
        //Get to see if a redirect_uri is set or not?
        $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        parse_str($query, $params);
        if(isset($params['redirect_to']))
        {    $user_home_page = urldecode($params['redirect_to']);
        }

        header('Location: ' . $user_home_page); die;
    }else {
        echo '<script type="text/javascript">

            function gup( name ){
name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
var regexS = "[\\?&]"+name+"=([^&#]*)";
var regex = new RegExp( regexS );
var results = regex.exec( opener.location.href );
 if( results == null )    return results;
else    return decodeURIComponent(results[1]);}

    var frank_param = gup("redirect_to");
            if(frank_param == null)
                //Check if user is any other page than login, redirect back to same page.

                opener.location.href = "' . $user_home_page .'";
            else
                opener.location.href = frank_param;
                close();
                </script>';
    }
}


function validateInteger($string, $required = false)
{
	$validationRegEx = "\d";

	if($required)
	{
		$validationRegEx .= "+";
	}
	else
	{
		$validationRegEx .= "*";
	}
	return preg_match('/^' .  $validationRegEx .'$/',$string)? true: false;
}