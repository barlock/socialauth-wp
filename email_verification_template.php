<?php

$message = "";

$message.= "Hi User,\n\n";

$message.= "Welcome to ". get_site_url()."\n\n";

$message.= "\n\n";

$message.= "Please click following link to verify your e-mail, this is one time process";

$message.= "\n\n";

$message.= plugin_dir_url(__FILE__) . "/verifyEmail.php?validationHash=" . $emailVerificationHash . "&random_code=" . $user_id;
