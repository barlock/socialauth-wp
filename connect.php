<?php

if( isset( $_GET["provider"] ) ){
    try{
        // load hybridauth
        require_once( dirname(__FILE__) . "/hybridauth/Hybrid/Auth.php" );
        // load wp-load.php
        $wp_load = dirname(dirname(dirname(dirname(__FILE__)))).'/wp-load.php';
        require_once($wp_load);

        include_once 'common.php';

        // selected provider name
        $provider = @ trim( strip_tags( $_GET["provider"] ) );

        // build required configuration for this provider
        $SocialAuth_WP_providers = get_option('SocialAuth_WP_providers');
        if(is_array($SocialAuth_WP_providers) && count($SocialAuth_WP_providers))
        {
            $config = array();
            if(isset($SocialAuth_WP_providers[$provider])) {
                $config["base_url"]  = plugin_dir_url(__FILE__) . 'hybridauth/';
                $config["providers"] = array();
                //this si same as orig config, no need to make config again
                $config["providers"][$provider] = $SocialAuth_WP_providers[$provider];
            } else {
                echo "Current Provider is unknown to system.";
                return;
            }
        }
        else
        {
            echo "SocialAuth-WP plugin is not configured properly. Contact Site administrator.";
            return;
        }

        // create an instance for Hybridauth
        $hybridauth = new Hybrid_Auth( $config );

        $adapter = null;
        $user_data = array();
        $user_id = null;
        $isNewUser= false;

        $emailVerificationHash = uniqid();
        $validateEmail = get_option('SocialAuth_WP_validate_newUser_email');

        //OpenId is a special case
        if($provider == 'OpenID')
        {
            $adapter = $hybridauth->authenticate( $provider, array("openid_identifier" => $SocialAuth_WP_providers[$provider]["keys"]["url"]));
        }
        else
        {
            $adapter = $hybridauth->authenticate( $provider);
        }

        $ha_user_profile = $adapter->getUserProfile();
        if(isset( $ha_user_profile ) && !empty($ha_user_profile) ){
            //get_user_by_meta is a user-defined function in social-auth-wp.php
            $user_id = get_user_by_meta( $provider, $ha_user_profile->identifier);
        }

        if (!empty($user_id)) {
            $user_data  = get_userdata( $user_id );
            $currentEmailVerificationHash = get_user_meta( $user_id, 'email_verification_hash', true );
            if(!empty($currentEmailVerificationHash))
            {
                $emailVerificationHash = $currentEmailVerificationHash;
            }
        } elseif ( $user_id = email_exists( $ha_user_profile->email ) ) { // User not found by provider identifier, check by email
            $user_data  = get_userdata( $user_id );
            $currentEmailVerificationHash = get_user_meta( $user_id, 'email_verification_hash', true );
            if(!empty($currentEmailVerificationHash))
            {
                $emailVerificationHash = $currentEmailVerificationHash;
            }
        } else { // Create new user and associate provider identity

            //Lets' check if new user creation is allowed or not?
            $users_can_register = get_option('users_can_register');
            if(empty($users_can_register) || $users_can_register == 0)
                throw new Exception("You do not have an account created on this site.  Please contact the Site Administrator.", 701);

            $displayNameArray = explode(" ", $ha_user_profile->displayName);

            //for when profile data do not contains first/last name
            $firstname =  $ha_user_profile->identifier;
            $lastname = $ha_user_profile->identifier;

            //get first/last name from display name
            if(isset($displayNameArray[0]) && count($displayNameArray[0])) {
                $firstname = $displayNameArray[0];
            }
            if(isset($displayNameArray[1]) && count($displayNameArray[1])) {
                $lastname = $displayNameArray[1];
            }

            //Setting userName to firstName if firstName available, profile identifier otherwise
            $user_login = $ha_user_profile->identifier;
            if(!empty($ha_user_profile->firstName))
            {
                $checkUserName = $ha_user_profile->firstName;
                while(username_exists( $checkUserName ))
                {
                    $checkUserName = $ha_user_profile->firstName . rand();
                }
                $user_login = $checkUserName;
            }

            $user_data = array(
                'user_login' => ($ha_user_profile->email)? $ha_user_profile->email : $user_login,
                'first_name' => ($ha_user_profile->firstName)? $ha_user_profile->firstName : $firstname,
                'last_name' => ($ha_user_profile->lastName)? $ha_user_profile->lastName : $lastname,
                'user_url' =>  ($ha_user_profile->profileURL)? $ha_user_profile->profileURL : "",
                'user_pass' => wp_generate_password()
            );

            //Some provider do not share user's email
            if(!empty($ha_user_profile->email))
            {
                $user_data['user_email'] = $ha_user_profile->email;
            }

            $SocialAuth_WP_user_role = get_option('SocialAuth_WP_user_role');
            if(!empty($SocialAuth_WP_user_role))
            {
                $user_data['role'] = $SocialAuth_WP_user_role;
            }

            // Create a new user
            $isNewUser = true;
            $user_id = wp_insert_user( $user_data );

            if($user_id)
            {
                if($user_data['user_email']) //users with email are marked validated
                    update_user_meta( $user_id, 'email_verification_hash', 'validated');
                else
                    update_user_meta( $user_id, 'email_verification_hash', $emailVerificationHash); //set un-validated email
            }
        }

        //Set some properties on every login
        if ( $user_id && validateInteger( $user_id , true) ) {
            //this will help next time in finding user by identifier
            update_user_meta( $user_id, $provider, $ha_user_profile->identifier );

            //This tells your provider you are currently logged in with
            update_user_meta( $user_id, 'ha_login_provider', $provider );
            update_user_meta( $user_id, 'profile_image_url', $ha_user_profile->photoURL);
        }

        //GetUserEmail, for new user as well as old users
        $userEmail = null;
        $isUserEmailValidated = get_user_meta( $user_id, 'email_verification_hash', true );//used below to see if user has validated email or not
        if(isset($ha_user_profile->email) && !empty($ha_user_profile->email))
        {
            $userEmail = $ha_user_profile->email;
        }
//             if(is_array($user_data))
//             {
//             	if(isset($user_data['user_email']))
//             		$userEmail= $user_data['user_email']; 
//             }
//             else
//             {
//             	if($user_data->user_email && !empty($user_data->user_email))
//             		$userEmail= $user_data->user_email;
//             }

        //Ask user for email and send verification email, this is when login provider not sharing user's email
        if(!empty($validateEmail) && $validateEmail == 'validate' && empty($userEmail) && $isUserEmailValidated != 'validated')
        {
            endAuthProcessAndRedirectToHomePage(plugin_dir_url(__FILE__) . 'verifyEmail.php?askForEmail=1&random_code='. $user_id);
            die;
        }

        wp_set_auth_cookie( $user_id );
        do_action( 'wp_login', $user_id );

        $SocialAuth_WP_user_home = get_option('SocialAuth_WP_user_home_page');
        $user_home_page = !empty($SocialAuth_WP_user_home)? $SocialAuth_WP_user_home : get_site_url();
        endAuthProcessAndRedirectToHomePage($user_home_page);
    }
    catch( Exception $e ){
        $message = "Some strange error occured, Please try again Later...";
        switch( $e->getCode() ){
            case 0 : $message = "Some strange error occured."; break;
            case 1 : $message = "It seems Hybridauth is not configuration properly."; break;
            case 2 : $message = "It seems some details are missing in provider configuration."; break;
            case 3 : $message = "It seems login provider is Unknown or Disabled."; break;
            case 4 : $message = "It seems you forgot to mention provider application credentials."; break;
            case 5 : $message = "Authentication has failed. See the reason below."; break;
            case 701 : $message = "Authentication has failed. See the reason below."; break;
        }
        ?>
        <link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url(__FILE__); ?>assets/css/style.css" />
        <div class="SocialAuth_WP SocialAuth_WP_error">
            <p class='highlighted'>There was some unexpected error, when trying to login with <?php echo $provider; ?></p>
            <p class='highlighted'>Following are the error details : </p>
            <p><?php echo $message; ?></p>
            <p><?php echo 'Error reason: ' .$e->getMessage(); ?></p>
            <?php
            $authDialogPosition = get_option('SocialAuth_WP_authDialog_location');
            if(!empty($authDialogPosition) && $authDialogPosition == 'page') { ?>
                <p class="">&laquo; <a href="<?php echo wp_get_referer(); ?>" >Back to Login Page</a></p>
            <?php
            }else{?>
                <p class=""><a href="#" onclick="javascript:window.close();" >Close this window</a></p>
            <?php }
            ?>
        </div>
        <?php
        die();
    }
}
else
{
    ?>
    <p>There was some unexpected error, when trying to login with <?php echo $provider; ?></p>
<?php
}
