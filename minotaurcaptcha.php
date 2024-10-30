<?php
/*
Plugin Name: MinotaurCaptcha
Plugin URI: http://min.otaur.com
Description: Rid the internet of annoying text captchas.
Version: 1.0.2
Author: Minotaur
Author URI: http://min.otaur.com
*/

$MINCAPTCHA_API = 'min.otaur.com/api';
//$MINVERIFICATION_KEY = '';

$minotaurcaptchaOptions = get_option('minotaurcaptcha_options');

// -------------------------- //
//  Uninstall Plugin          //
// -------------------------- //

//function delete_preferences() {
    //delete_option('minotaurcaptcha_options');
//}

register_deactivation_hook(__FILE__, 'delete_preferences');

// -------------------------- //
//  MinotaurCaptcha API functions  //
// -------------------------- //

function minGetCaptcha($hostPrivateKey) {
    global $MINCAPTCHA_API, $MINVERIFICATION_KEY, $_SERVER;

    $protocol = "http://";
    
    $url = $protocol . $MINCAPTCHA_API . "/reqkey/" . $hostPrivateKey . "/" . $_SERVER["REMOTE_ADDR"];
    
    $xmlResponse = mincURLReq($url);
    if($xmlResponse->verify != null) {
    	$MINVERIFICATION_KEY = $xmlResponse->verify->publickey;
    }
    
    $iframeURL = $protocol . $MINCAPTCHA_API . "/verify/" . $MINVERIFICATION_KEY;
	$result .= "<iframe src='" . $iframeURL . "' width='260' height='350' frameborder='0' marginheight='0' marginwidth='0'></iframe>\n";

	return $result;
}

function minValidateCaptcha($hostPrivateKey) {
    global $MINCAPTCHA_API, $MINVERIFICATION_KEY;

	$host = $MINCAPTCHA_API;
	$protocol = "http://";
	$url = $protocol . $host. "/confirm/" . $hostPrivateKey . "/" . $MINVERIFICATION_KEY;
    
    $xmlResponse = mincURLReq($url);
    if($xmlResponse->verify != null){
    	$result = $xmlResponse->verify->minstatus;
    }
    
	return $result;
}

function mincURLReq($path) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$path);
	curl_setopt($ch,CURLOPT_HTTPHEADER,array('Accept: application/xml','Content-Type: application/xml'));
	curl_setopt($ch, CURLOPT_FAILONERROR,1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	$retValue = curl_exec($ch);			 
	curl_close($ch);
	return new SimpleXMLElement($retValue);
}

// ------------------- //
//  Registration form  //
// ------------------- //

// Display the MinCaptcha challenge on the registration form
function display_minotaurcaptcha_registration() {
	global $minotaurcaptchaOptions;

    $privateKey = $minotaurcaptchaOptions['minotaurcaptcha_private_key'];

    echo(minGetCaptcha($privateKey));
}

// Check the MinCaptcha challenge on the registration form
function validate_minotaurcaptcha_registration($errors) {
    global $minotaurcaptchaOptions, $MINCAPTCHA_API;
    
	$privateKey = $minotaurcaptchaOptions['minotaurcaptcha_private_key'];

    $result = minValidateCaptcha($privateKey);

	if ($result != "verified") {
		$errors->add('error_incorrect', $minotaurcaptchaOptions['minotaurcaptcha_message_incorrect']);
        return $errors;
    }

    return $errors;
}

// If registration CAPTCHA is enabled, hook it!
if ($minotaurcaptchaOptions['minotaurcaptcha_registration_enable']) {
    add_action('register_form', 'display_minotaurcaptcha_registration');
    add_filter('registration_errors', 'validate_minotaurcaptcha_registration');
}

// -------------- //
//  Comment form  //
// -------------- //

define ("MINCAPTCHA_WP_HASH",  "58OsYFhYIHLpYCFyVGiq7Os5");
$minotaurcaptcha_error = '';

function minotaurcaptcha_wp_hash($key)
{
    global $minotaurcaptchaOptions;

    if (function_exists('wp_hash'))
        return wp_hash(MINCAPTCHA_WP_HASH . $key);
    else
        return md5(MINCAPTCHA_WP_HASH . $minotaurcaptchaOptions['minotaurcaptcha_private_key'] . $key);
}

// Display the MinCaptcha challenge on the comment form
function display_minotaurcaptcha_comment() {
   global $minotaurcaptchaOptions;

    if (!$minotaurcaptchaOptions['minotaurcaptcha_comment_enable'])
        return;

    if (is_user_logged_in() && $minotaurcaptchaOptions['minotaurcaptcha_comment_hide']) {
       if (current_user_can($minotaurcaptchaOptions['minotaurcaptcha_comment_hide_permission_level'])) {
            return;
       }
    }

    if ($_GET['rerror'] == 'minotaurcaptcha_message_blank')
        echo "<p>" . str_replace("\'", "'", $minotaurcaptchaOptions['minotaurcaptcha_message_blank']) . "</p>";

    if ($_GET['rerror'] == 'minotaurcaptcha_message_incorrect')
        echo "<p>" . str_replace("\'", "'", $minotaurcaptchaOptions['minotaurcaptcha_message_incorrect']) . "</p>";

    $privateKey = $minotaurcaptchaOptions['minotaurcaptcha_private_key'];

    echo(minGetCaptcha($privateKey));

    if ($minotaurcaptchaOptions['minotaurcaptcha_comment_rearrange']) {
        echo("<script type='text/javascript'>");
        echo("var oComment = document.getElementById('comment');");
        echo("var oParent = oComment.parentNode;");
        echo("var oCaptcha = document.getElementById('adscaptcha_widget');");
        echo("oParent.appendChild(oCaptcha, oComment);");
        echo("</script>");
    }
}

function validate_minotaurcaptcha_comment($comment_data) {
    global $user_ID, $minotaurcaptchaOptions, $minotaurcaptcha_error;

    if (!$minotaurcaptchaOptions['minotaurcaptcha_comment_enable'])
        return $comment_data;

    if (is_user_logged_in() && $minotaurcaptchaOptions['minotaurcaptcha_comment_hide']) {
       if (current_user_can($minotaurcaptchaOptions['minotaurcaptcha_comment_hide_permission_level'])) {
            return $comment_data;
       }
    }

	if ($comment_data['comment_type'] == '') {
		$privateKey = $minotaurcaptchaOptions['minotaurcaptcha_private_key'];

		if (minValidateCaptcha($privateKey) == "verified") {
			return $comment_data;
        } else {
            $minotaurcaptcha_error = 'minotaurcaptcha_message_incorrect';
            add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
            return $comment_data;
        }
    }

    return $comment_data;
}

function minotaurcaptcha_comment_post_redirect($location, $comment) {
    global $minotaurcaptcha_error;

    if($minotaurcaptcha_error != '') {
        $location = substr($location, 0,strrpos($location, '#')) .
            ((strrpos($location, "?") === false) ? "?" : "&") .
            'rcommentid=' . $comment->comment_ID .
            '&rerror=' . $minotaurcaptcha_error .
            '&rchash=' . minotaurcaptcha_wp_hash ($comment->comment_ID).
            '#commentform';
    }
    return $location;
}

function minotaurcaptcha_wp_saved_comment() {
   if (!is_single() && !is_page())
      return;

   if ($_GET['rcommentid'] && $_GET['rchash'] == minotaurcaptcha_wp_hash ($_GET['rcommentid'])) {
      $comment = get_comment($_GET['rcommentid']);
      $com = preg_replace('/([\\/\(\)\+\;\'\"])/e','\'%\'.dechex(ord(\'$1\'))', $comment->comment_content);
      $com = preg_replace('/\\r\\n/m', '\\\n', $com);
      wp_delete_comment($comment->comment_ID);
   }
}

// If comment CAPTCHA is enabled, hook it!
if ($minotaurcaptchaOptions['minotaurcaptcha_comment_enable']) {
    add_action('comment_form', 'display_minotaurcaptcha_comment');
    add_filter('wp_head', 'minotaurcaptcha_wp_saved_comment', 0);
    add_filter('preprocess_comment', 'validate_minotaurcaptcha_comment', 0);
    add_filter('comment_post_redirect', 'minotaurcaptcha_comment_post_redirect', 0, 2);
}

// ---------------- //
//  Administration  //
// ---------------- //

function minotaurcaptcha_permissions_dropdown($select_name, $checked_value="") {
	$permissions = array (
	 	'All registered users' => 'read',
	 	'Edit posts' => 'edit_posts',
	 	'Publish Posts' => 'publish_posts',
	 	'Moderate Comments' => 'moderate_comments',
	 	'Administer site' => 'level_10'
	 	);
	echo '<select name="' . $select_name . '" id="' . $select_name . '">';
	foreach ($permissions as $text => $value) :
		if ($value == $checked_value) $checked = ' selected="selected" ';
		echo '<option value="' . $value . '"' . $checked . ">$text</option>";
		$checked = NULL;
	endforeach;
	echo "</select>";
 }

// Add a link to the configuration options in the WordPress options menu
function add_minotaurcaptcha_settings_page() {
	add_options_page('Minotaur', 'Minotaur', 8, __FILE__, 'minotaurcaptcha_settings_page');
}

// Display MinCaptcha settings page
function minotaurcaptcha_settings_page() {
	$minotaurcaptchaOptionsArray = array(
		'minotaurcaptcha_private_key' => '',
        'minotaurcaptcha_registration_enable' => true,
        'minotaurcaptcha_comment_enable' => true,
        'minotaurcaptcha_comment_hide' => true,
        'minotaurcaptcha_comment_hide_permission_level' => 'read',
        'minotaurcaptcha_comment_rearrange' => false,
        'minotaurcaptcha_message_blank' => '<strong>ERROR</strong>: Please complete the CAPTCHA.',
        'minotaurcaptcha_message_incorrect' => '<strong>ERROR</strong>: The CAPTCHA was incorrect.');

	add_option('minotaurcaptcha_options', $minotaurcaptchaOptionsArray);

	if (isset($_POST[ 'submit' ])) {
		$minotaurcaptchaOptionsArray['minotaurcaptcha_private_key'] = trim($_POST['minotaurcaptcha_private_key']);
        $minotaurcaptchaOptionsArray['minotaurcaptcha_registration_enable'] = trim($_POST['minotaurcaptcha_registration_enable']);
        $minotaurcaptchaOptionsArray['minotaurcaptcha_comment_enable'] = trim($_POST['minotaurcaptcha_comment_enable']);
        $minotaurcaptchaOptionsArray['minotaurcaptcha_comment_hide'] = trim($_POST['minotaurcaptcha_comment_hide']);
        $minotaurcaptchaOptionsArray['minotaurcaptcha_comment_hide_permission_level'] = trim($_POST['minotaurcaptcha_comment_hide_permission_level']);
        $minotaurcaptchaOptionsArray['minotaurcaptcha_comment_rearrange'] = trim($_POST['minotaurcaptcha_comment_rearrange']);
        $minotaurcaptchaOptionsArray['minotaurcaptcha_message_blank'] = trim(str_replace("\'", "'", $_POST['minotaurcaptcha_message_blank']));
        $minotaurcaptchaOptionsArray['minotaurcaptcha_message_incorrect'] = trim(str_replace("\'", "'", $_POST['minotaurcaptcha_message_incorrect']));

		update_option('minotaurcaptcha_options', $minotaurcaptchaOptionsArray);
	}

	$minotaurcaptchaOptions = get_option('minotaurcaptcha_options');
?>
<div class="wrap">

    <script type="text/javascript">
		function toggleHelp(id) {
			var e = document.getElementById(id);
			if(e.style.display == 'block')
				e.style.display = 'none';
			else
				e.style.display = 'block';
        }
    </script>

    <h2>Minotaur Options</h2>

    <!--
    <p>
        <a href="http://wordpress.org/extend/plugins/minotaurcaptcha/" target="_blank">Rate this</a> |
        <a href="http://wordpress.org/extend/plugins/minotaurcaptcha/faq/" target="_blank">FAQ</a>
    </p>
    -->


    <h3>About Minotaur</h3>
    <p>
    Minotaur is a free image based CAPTCHA. No more text puzzles to annoy your viewers. Simple and user friendly.<br/>
    For more details, visit the <a href="http://min.otaur.com/">Minotaur website</a>.
    </p>

    <form method="post" action="<?php echo $_SERVER[ 'PHP_SELF' ] . '?page=' . plugin_basename(__FILE__); ?>&updated=true">
	    <?php wp_nonce_field( 'update-options' ); ?>

        <h3>Options</h3>
        <table class="optiontable" cellspacing="10">
            <tr valign="top">
                <td width="200">Keys</td>
                <td>
                	Minotaur requires an API key for each host it is used on. To get a key, sign up for an <a href="http://min.otaur.com/mgmt/sing_up">account</a>.<br/>
                	With an account, you can add domains for each host you want to use Minotaur on. The hostname should be the FULL domain name (sub.domain.tld).<br/>
                    <table>
                        <tr>
                            <td>Private key: </td><td><input type="text" id="minotaurcaptcha_private_key" name="minotaurcaptcha_private_key" size="50" maxlength="100" autocomplete="off" value="<?php echo $minotaurcaptchaOptions['minotaurcaptcha_private_key']; ?>" /></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr valign="top">
                <td>Registration Options</td>
                <td>
                    <input type="checkbox" name="minotaurcaptcha_registration_enable" id="minotaurcaptcha_registration_enable" value="1" <?php if($minotaurcaptchaOptions['minotaurcaptcha_registration_enable'] == true){echo 'checked="checked"';} ?> />
                    <label name="minotaurcaptcha_registration_enable" for="minotaurcaptcha_registration_enable">Enable CAPTCHA on the registration form.</label>
                </td>
            </tr>
            <tr valign="top">
                <td>Comments Options</td>
                <td>
                    <input type="checkbox" name="minotaurcaptcha_comment_enable" id="minotaurcaptcha_comment_enable" value="1" <?php if($minotaurcaptchaOptions['minotaurcaptcha_comment_enable'] == true){echo 'checked="checked"';} ?> />
                    <label name="minotaurcaptcha_comment_enable" for="minotaurcaptcha_comment_enable">Enable CAPTCHA on the comment form.</label><br/>
                    
                    <input type="checkbox" name="minotaurcaptcha_comment_hide" id="minotaurcaptcha_comment_hide" value="1" <?php if($minotaurcaptchaOptions['minotaurcaptcha_comment_hide'] == true){echo 'checked="checked"';} ?> />
                    <label name="minotaurcaptcha_comment_hide" for="minotaurcaptcha_comment_hide">Hide CAPTCHA for <b>registered</b> users who can: </label><?php minotaurcaptcha_permissions_dropdown('minotaurcaptcha_comment_hide_permission_level',$minotaurcaptchaOptions['minotaurcaptcha_comment_hide_permission_level']); ?>
                    <a style="cursor:pointer;font-size:80%;" title="Click for help!" onclick="toggleHelp('minotaurcaptcha_comment_hide_help');">Help</a><br/>
                    <div style="margin:5px 0 0 20px;font-size:85%;display:none;" id="minotaurcaptcha_comment_hide_help">
                        The CAPTCHA might be annoying to highly active users.<br/>
                        You can decide whether to hide the CAPTCHA from users according to their permission level.
                    </div>
                    <input type="checkbox" name="minotaurcaptcha_comment_rearrange" id="minotaurcaptcha_comment_rearrange" value="1" <?php if($minotaurcaptchaOptions['minotaurcaptcha_comment_rearrange'] == true){echo 'checked="checked"';} ?> />
                    <label name="minotaurcaptcha_comment_rearrange" for="minotaurcaptcha_comment_rearrange">Rearrange CAPTCHA's position on the comment form automatically?</label>
                    <a style="cursor:pointer;font-size:80%;" title="Click for help!" onclick="toggleHelp('minotaurcaptcha_comment_rearrange_help');">Help</a>
                    <div style="margin:5px 0 0 20px;font-size:85%;display:none;" id="minotaurcaptcha_comment_rearrange_help">
                        Your CAPTCHA displays AFTER or ABOVE the submit button on the comment form?<br/>
                        If so, edit your current theme comments.php file and locate this line:<br/>
                        <font color="Blue">&lt;?</font><font color="Red">php</font> do_action('comment_form', $post->ID); <font color="Blue">?&gt;</font><br/>
                        Move this line to BEFORE the comment textarea, uncheck the option box above, and the problem should be fixed.<br/>
                        Alernately, you can just check this box and javascript will <b>attempt</b> to rearrange it for you. <b>This option is less recomended.</b>
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <td>Error Messages</td>
                <td>
                    The following are the messages to display when the user does not enter a CAPTCHA response or enters the incorrect CAPTCHA response.<br/>
                    <table>
                        <tr>
                            <td>No response:</td><td><input type="text" name="minotaurcaptcha_message_blank" size="80" autocomplete="off" value="<?php echo $minotaurcaptchaOptions['minotaurcaptcha_message_blank']; ?>" /></td>
                        </tr>
                        <tr>
                            <td>Incorrect answer:</td><td><input type="text" name="minotaurcaptcha_message_incorrect" size="80" autocomplete="off" value="<?php echo $minotaurcaptchaOptions['minotaurcaptcha_message_incorrect']; ?>" /></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

	    <p class="submit">
	        <input type="submit" name="submit" value="<?php _e('Update Options &raquo;'); ?>" />
	    </p>
	    </form>
	</div>
<?php
}

// Hook the add_config_page function into WordPress
add_action( 'admin_menu', 'add_minotaurcaptcha_settings_page' );

// Display a warning if the public and private keys are missing
if ( !$minotaurcaptchaOptions['minotaurcaptcha_private_key'] && !isset($_POST['submit']) ) {
	function minotaurcaptcha_warning() {
		$path = plugin_basename(__FILE__);
		echo "<div id='error_incorrect' class='updated fade-ff0000'><p><strong>Minotaur is not active</strong>. You must <a href='options-general.php?page=" . $path . "'>enter your Minotaur API key</a> for it to work.</p></div>";
	}
	add_action('admin_notices', 'minotaurcaptcha_warning');
	return;
}
?>
