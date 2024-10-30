=== minotaur ===
Contributors: socksticker
Author URI: http://min.otaur.com
Tags: image captcha, mobile CAPTCHA,  PHP CAPTCHA, Free CAPTCHA, CAPTCHA Code, stop spam, comments, register, bot, CAPTCHA, security, spam prevention, antispam, comments, recaptcha, registration, anti-spam, anti-bot, user friendly
Requires at least: 3.6.1
Tested up to: 3.7.1
Stable tag: 1.0.2

minotaur - Rid the internet of text CAPTCHAs!

== Description ==

minotaur allows you to add [CAPTCHA](http://min.otaur.com/) boxes for spam prevention, user friendly natural image recognition

[Sign up](http://min.otaur.com/) now in order to get your private key.

Features:
--------
 * NO Text Images, natural human image recognition
 * Fully configured from the admin panel
 * Setting to hide the CAPTCHA from logged in users and/or admins
 * Setting to show the CAPTCHA on the registration form and/or comment form

Requirements/Restrictions:
-------------------------
 * Works with Wordpress 3.6+
 * PHP 4.3+
 * Your theme must have a `<?php do_action('comment_form', $post->ID); ?>` tag inside your comments.php form. Most themes do.
If not, in your comments.php file, put <?php do_action('comment_form', $post-&gt;ID); ?> before <input name="submit"..>.

== Installation ==

1. Download the plugin and extract the folder contained within.

2. Upload or copy the folder to your /wp-content/plugins directory.

3. Visit [http://min.otaur.com/](http://min.otaur.com/) and sign up for a FREE minotaur key

4. Activate the plugin through the 'Plugins' menu in WordPress

5. Go to the 'Settings' menu in WordPress and set your minotaur host private key

== Changelog ==

= 1.0 =
* Release date: 22 Feb 2014
* Release!

== Frequently Asked Questions ==

= What is the key I need to provide? =

After you sign up to [minotaur](http://min.otaur.com/mgmt/addHost) and register your website.
Once directed to [allHosts](http://min.otaur.com/mgmt/allHosts), you will see your new domain and its private key.

Make sure to copy & paste the exact key value to your plugin's settings.

= The CAPTCHA is not being shown =

By default, registered users will not see the CAPTCHA.
Log out and try again.

If you don't want to hide the CAPTCHA from registered users (or other permission groups), simply uncheck the
'Hide CAPTCHA...' option or change the desired permission group on the minotaur plugin's settings.

= Sometimes the CAPTCHA is displayed AFTER the submit button on the comment form =

Best practice is to edit your current theme comments.php file and locate this line:
<?php do_action('comment_form', $post->ID); ?>
Move this line to BEFORE the comment textarea and the problem should be fixed.

Alernately, you can check the 'Rearrange CAPTCHA's position on the comment form automatically' option on the minotaur plugin's settings, and javascript will attempt to rearrange it for you.
This option is less recomended.
