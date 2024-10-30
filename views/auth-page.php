<?php
/**
 * The form to be loaded on the plugin's admin page
 */
if( current_user_can( 'edit_users' ) ) {
    $apiKey = get_option('moosend_landing_api_key');
    $moosendEmail = get_option('moosend_landing_email');
    $moosend_landing_auth_error = get_option('moosend_landing_auth_error');

    // Build the Form
    ?>
    <style>
        /* Table CSS */
        p, h1{
            text-align: center;
        }
        .wrap{
            margin: 0 auto;
            width: 30%;
            padding: 50px 0 0 0;
            outline: 0;
            font-size: 100%;
            vertical-align: baseline;
            background: transparent;
            text-decoration: none;
            font-family: 'Montserrat',sans-serif;
            list-style: none;
            line-height: normal;
            -webkit-font-smoothing: antialiased;
            -moz-box-sizing: border-box;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }
        .wrapper, .text-center{
            text-align: center !important;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h2{
            font-size: 30px;
            color: #394257;
            margin-bottom: 50px;
        }
        a{
            color: #007bff;
            text-decoration: none;
            background-color: transparent;
        }
        .btn{
            width: 100%;
            border: none;
            text-align: center;
            padding: 15px 35px;
            border-radius: 30px;
            padding: 13px 35px;
            color: #fff;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            background-color: #3ac4d7;
            -webkit-transition: all .4s;
            -moz-transition: all .4s;
            -o-transition: all .4s;
            transition: all .4s;

        }
        .btn{
            margin-top: 50px;
        }
        .form-field{
            margin-bottom: 25px;
        }
        .login-form .form-field label{
            color: #394257;
            font-family: 'Hind',sans-serif;
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
            display: block;
        }
        .login-form .form-field input{
            height: 48px;
            background-color: white;
            color: #394257;
            border: 1px solid #3ac4d7;
            border-radius: 30px;
            padding: 16px 26px;
            font-size: 16px;
            font-family: 'Hind',sans-serif;
            width: 100%;
            margin-bottom: 4px;
        }
        .login-form .form-field .error-msg{
            font-family: 'Hind',sans-serif;
            font-size: 14px;
            color: #ff6154;
            display: inline;
        }
        .btn.orange{
            background-color: #ff6154;
            cursor:pointer;
        }
        p.register {
            font-size: 18px;
            margin-top: 32px;
        }
        .register .create {
            color: #3ac4d7;
            font-family: 'Hind',sans-serif;
            font-size: 18px;
        }
        .forgot{
            color: #3ac4d7;
            float: right;
        }
        h3{
            margin-bottom: 50px;
        }
        #landingAuthError{
            color:red;
            margin: 20px 0px;
        }
        /* END TITLE CSS */
    </style>
    <div class="wrap">
        <div class="form-container">
            <div class="wrapper text-center">
                    <img src="<?php echo(plugins_url('assets/img/moosend_logo_full.svg', __DIR__));?>" width="160" alt="" class="logo">
                <?php if($apiKey == false & $moosend_landing_auth_error == 'No') : ?>
                    <h2>Login to your Account</h2>
                <?php elseif($apiKey != false & $moosend_landing_auth_error == 'No') : ?>
                    <h2>Your are logged in with:<?php echo($moosendEmail); ?> </h2>
                    <h3>Click below only if you want to change your account</h3>
                <?php endif; ?>
                <?php if($moosend_landing_auth_error == 'Yes') : ?>
                    <h1 id="landingAuthError">It seems that there was an error. Please, check your credentials and try again</h1>
                <?php endif; ?>
            </div>
            <?php if($apiKey != false) : ?>
                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" id="moosend-landings-auth-form" class="login-form" novalidate="novalidate">
                    <input name='action' type="hidden" value='auth-form'>
                    <input name='logout' type="hidden" value='false'>
                    <button type="submit" class="btn orange u-register">Log Out</button>
                </form>
            <?php else : ?>
                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" id="moosend-landings-auth-form" class="login-form" novalidate="novalidate">
                    <div class="form-field required" aria-required="true">
                        <label for="moosend_landing_email">Email or Username</label>
                        <input type="text" id="moosend_landing_email" name="moosend_landing_email">
                    </div>
                    <div class="form-field required" aria-required="true">

                        <label for="moosend_landing_password">Password</label>
                        <input type="password" id="moosend_landing_password" name="moosend_landing_password">
                        <a class="forgot" href='https://identity.moosend.com/forgot-password?redirectUrl=<?php echo esc_url(get_admin_url());?>edit.php?post_type=moosend_landing&page=moosend-landings-settings' >Forgot password?</a>
                    </div>
                    <input name='action' type="hidden" value='auth-form'>
                    <button type="submit" class="btn orange u-register">Login</button>
                </form>
                <p class="register">
                    Haven’t registered on Moosend?
                    <a class="create" href="https://identity.moosend.com/register?redirectUrl=<?php echo esc_url(get_admin_url());?>edit.php?post_type=moosend_landing&page=moosend-landings-settings">Create a free account!</a>
                </p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
else {
    ?>
    <p> <?php __("You are not authorized to perform this operation.", $this->plugin_name) ?> </p>
    <?php
}