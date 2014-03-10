<?php
$wp_load = dirname(dirname(dirname(dirname(__FILE__)))).'/wp-load.php'; 
require_once($wp_load);
include_once 'common.php';
$SocialAuth_WP_user_home = get_option('SocialAuth_WP_user_home_page');
$url = !empty($SocialAuth_WP_user_home)? $SocialAuth_WP_user_home : get_site_url();

if(isset($_REQUEST['emailToSendVerificationEmail'])&& isset($_REQUEST['random_code']) && isset($_REQUEST['askForEmail']))
{
	
	$email = $_REQUEST['emailToSendVerificationEmail'];
	$user_id = $_REQUEST['random_code'];
	
	$user_data  = get_userdata( $user_id );
	
	if(!empty($user_data) && !empty($email) && (empty($user_data->user_email) || $user_data->user_email == $email))
	{
		wp_update_user( array ('ID' => $user_id, 'user_email' => $email) ) ;
		
		$emailVerificationHash = get_user_meta( $user_id, 'email_verification_hash', true );
		sendEmailVerificationEmail($email, $user_id, $emailVerificationHash);
	?>
	
	<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <title>SocialAuth WP email verification</title>
    <link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url(__FILE__); ?>assets/css/style.css" />
	</head>
	<body>
	<div class="SocialAuth_WP">
	    <p>A Verification email has been sent to your email address.</p>
	    <a class="button" href="<?php echo $url; ?>" ><span>Continue</span></a> 
	</div>
	</body>
	<?php die;?>
	
	<?php }
	else
	{?>
	
	<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <title>SocialAuth WP email verification</title>
    <link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url(__FILE__); ?>assets/css/style.css" />
	</head>
	<body>
	<div class="SocialAuth_WP">
	    <p>Oops !!! This is not a valid request, which we can serve for you.</p>
	    <a class="button" href="<?php echo $url; ?>" ><span>Continue</span></a> 
	</div>
	</body>
		
	<?php }
	
}
elseif(isset($_REQUEST['validationHash'])&& isset($_REQUEST['random_code']))
{
	$emailValidationHash = $_REQUEST['validationHash'];
	$user_id = $_REQUEST['random_code'];
	$isUserEmailValidated = get_user_meta( $user_id, 'email_verification_hash', true );
	if($isUserEmailValidated == $emailValidationHash)
	{
		update_user_meta( $user_id, 'email_verification_hash', 'validated');
		
		//Login user automatically after email verification
		wp_set_auth_cookie( $user_id );
		do_action( 'wp_login', $user_id );
?>
	    <head>
	    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
	    <title>SocialAuth WP email verification</title>
	    <link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url(__FILE__); ?>assets/css/style.css" />
		</head>
		<body>
		<div class="SocialAuth_WP">
		    <p>Congratulations !!! Email verification is now complete.</p> 
		    <a class="button" href="<?php echo $url; ?>" ><span>Continue</span></a> 
		</div>
		</body> 
		
<?php
	}
	else
	{
?>
	<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <title>SocialAuth WP email verification</title>
    <link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url(__FILE__); ?>assets/css/style.css" />
	</head>
	<body>
	<div class="SocialAuth_WP">
	    <p>Oops !!! Email verification failed. Please try again later.</p> 
	    <a class="button" href="<?php echo $url; ?>" ><span>Continue</span></a> 
	</div>
	</body> 
<?php
	}
}
elseif(isset($_REQUEST['askForEmail'])&& isset($_REQUEST['random_code']))
{
?>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <title>SocialAuth WP email verification</title>
    <link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url(__FILE__); ?>assets/css/style.css" />
</head>
<body>
<div class="SocialAuth_WP">
    <p>Oops !!! It seems you will need to verify your email with us before you can get in. This is one time process and site administrator has enbaled this to avoid spamming of accounts.</p>
    <form method="post" action="">
	    <input type="text" name="emailToSendVerificationEmail" />
	    <p>A validaiton e-mail will be sent to this email address. Please validate your email first and come back here to login again.</p>
	    <input type="submit" value="Continue" name="Continue" />
    </form> 
</div>
</body>
<?php 
}
else {
?>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <title>SocialAuth WP email verification</title>
    <link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url(__FILE__); ?>assets/css/style.css" />
</head>
<body>
<div class="SocialAuth_WP">
    <p>Oops !!! This is not a valid request, which we can serve for you.</p>
    <a class="button" href="<?php echo $url; ?>" ><span>Continue</span></a> 
</div>
</body>
<?php
}
?>    
