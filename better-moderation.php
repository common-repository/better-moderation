<?php
/*
Plugin Name: Better Moderation
Plugin URI: http://blog.artesea.co.uk/2011/06/better-moderation.html
Description: Whereas the built in moderation just looks for keywords wherever they appear in the comments, Better Moderation allows you to define if it should just be the username, url, email, comment, ip, useragent or all of them.
It also allows you to define if when looking for "word" whether you should match "WordPress".
Finally it lets you set reasons why words have been added to the list, allowing you to quickly realise why a comment has been added to the moderation queue.
Version: 1.5
Author: Artesea
Author URI: http://www.artesea.co.uk
*/

# RELEASE NOTES
#
# Version 1.5
# ===========
# Add individual pre-moderation on posts
#
# Version 1.4
# ===========
# Logging when comments are moved out of trash
# Added relevant settings from 'discussion' to the admin page 
# Renamed Better Moderation
#
# Version 1.3
# ===========
# Global variable for the reason so we don't need to check the comment twice
# IPs and UAs were being ignored as not in the place we were originally looking
#
# Version 1.2
# ===========
# Allows for ^ to be used at the start or end of a match to ignore words prefixed or postfixed with other letters,
# eg, ^feck would match feck, fecking, fecker, but not mutherfecker.
#
# Version 1.1
# ===========
# Spots for Comment "Whitelisting" (where a user is moderated if they have never had an accepted comment before)
# Logs when an admin trashes a comment
#
# Version 1.0
# ===========
# Adds new Argh Moderation Menu
# Adds column with reason to edit-comments.php
# Adds reason for moderated comments to the overview on the dashboard
# Adds reason to the automatic email
# Logs when an admin either moderates or approves a comment
# Hides the default moderation info from the built in moderation action
# Warns on the discussion page that alternative settings are in use

$argh_moderation_reason = '';

function argh_moderation_custom_comment_column_content($column_name, $comment_ID) {
	switch ($column_name) {
		case 'argh_moderation':
			echo get_comment_meta($comment_ID, 'moderation_reason', true);
			break;
#could have more columns cased here
	}
}

function argh_moderation_custom_comment_column_headings($defaults) {
	$defaults['argh_moderation'] = 'Moderation';
	return $defaults;
}

function argh_moderation_moderated($approved) {
   return 0;
}

function argh_moderation_should_we_moderate_comment($commentdata) {
	global $wpdb, $argh_moderation_reason;
	$reason 				= '';
	$comment_author			= $commentdata['comment_author'];
	$comment_author_email	= $commentdata['comment_author_email'];
	$comment_author_url		= $commentdata['comment_author_url'];
	$comment_content		= $commentdata['comment_content'];
	$comment_author_IP		= $commentdata['comment_as_submitted']['user_ip'];
	$comment_agent			= $commentdata['comment_as_submitted']['user_agent'];
	$comment_type			= $commentdata['comment_type'];
	$post_ID				= $commentdata['comment_post_ID'];

	// If moderation is set to manual
	if(get_option('comment_moderation') == 1) {
		$reason .= 'All comments are moderated<br />';
	}
	
	// Pre moderation enabled against the post
	if(get_post_meta($post_ID, 'argh_moderation_pre', true) == 1) {
		$reason .= 'Pre moderation enabled<br />';
	}

	$mod_keys = trim(get_option('argh_moderation_keys'));
	if(!empty($mod_keys)) {
		$words = explode("\n", $mod_keys);

		foreach((array)$words as $line) {
			// Skip empty lines
			$line = trim($line);
			if(empty($line))
				continue;

			// Break of the #reason from the matching bit				
			$bits = explode('|', $line);
			$find = trim($bits[0]);
			$in   = strtolower(trim($bits[1]));
			$res  = trim($bits[2]);
			if($in == '')  $in  = 'all';
			if($res == '') $res = 'No reason given';

			// Do some escaping magic so that '#' chars in the
			// spam words don't break things:
			$pattern = '#' . preg_quote($find, '#') . '#i';
			// Replace ^ with a regex look up to ignore those either starting or ending with other letters
			$pattern = str_replace(array('#\^', '\^#'), array('#(?<!\w)', '(?!\w)#'), $pattern);
			$find    = str_replace('^', '', $find);
			
			if(($in == 'all' || $in == 'name')      && preg_match($pattern, $comment_author))       { $reason .= "Name: $find - $res<br />"; }
			if(($in == 'all' || $in == 'email')     && preg_match($pattern, $comment_author_email)) { $reason .= "Email: $find - $res<br />"; }
			if(($in == 'all' || $in == 'url')       && preg_match($pattern, $comment_author_url))   { $reason .= "URL: $find - $res<br />"; }
			if(($in == 'all' || $in == 'text')      && preg_match($pattern, $comment_content))      { $reason .= "Text: $find - $res<br />"; }
			if(($in == 'all' || $in == 'ip')        && preg_match($pattern, $comment_author_IP))    { $reason .= "IP: $find - $res<br />"; }
			if(($in == 'all' || $in == 'useragent') && preg_match($pattern, $comment_agent))        { $reason .= "UA: $find - $res<br />"; }
		}
	}

	// Check # of external links
	if($max_links = get_option('comment_max_links')) {
		$num_links = preg_match_all('/<a [^>]*href/i', $comment, $out);
		$num_links = apply_filters('comment_max_links_url', $num_links, $url); // provide for counting of $url as a link
		if($num_links >= $max_links) {
			$reason .= "Too many links<br />";
		}
	}
	
	// Check for whitelisting (has the person ever left a comment before)
	if(get_option('comment_whitelist') == 1) {
		if($comment_type != 'trackback' && $comment_type != 'pingback' && $comment_author != '' && $comment_author_email != '' ) {
			// expected_slashed ($author, $email)
			$ok_to_comment = $wpdb->get_var("SELECT comment_approved FROM $wpdb->comments WHERE comment_author = '$comment_author' AND comment_author_email = '$comment_author_email' and comment_approved = '1' LIMIT 1");
			if ($ok_to_comment != 1) {
				$reason .= 'New commentor<br />';
			}
		}
		else {
			$reason .= 'New commentor<br />';
		}
	}
		
	$argh_moderation_reason = $reason;
	$modded = ($reason != '');
	return $modded;
}

function argh_moderated($status) {
	return 0;
}

function argh_moderation_moderate_comment($commentdata) {
	$modded = argh_moderation_should_we_moderate_comment($commentdata);
	if($modded) {
		add_action('pre_comment_approved', 'argh_moderated');
	}
	return $commentdata;
}

function argh_moderation_add_reason($comment_ID, $commentdata) {
	global $argh_moderation_reason;
	if($commentdata->comment_approved == 0) {
		$reason = $argh_moderation_reason;
		update_comment_meta($comment_ID, 'moderation_reason', $reason);
	}
}

function argh_moderation_notification_email($text, $comment_ID) {
	$reason = get_comment_meta($comment_ID, 'moderation_reason', true);
	if($reason) {
		$text .= "\r\nModeration Reason:\r\n";
		$text .= str_replace("<br />", "\r\n", $reason);
	}
	return $text;
}

function argh_moderation_comment_manually_changed($comment, $status) {
	global $current_user;
	get_currentuserinfo();
	$comment_ID = $comment->comment_ID;
	$reason = get_comment_meta($comment_ID, 'moderation_reason', true);
	$reason .= $status . ' by ' . $current_user->display_name . ' on ' . gmdate('Y/m/d \a\\t g:i a', time()+(get_option('gmt_offset') * 3600)) . '<br />'; #need current admins name and timestamp here;
	update_comment_meta($comment_ID, 'moderation_reason', $reason);
}

function argh_moderation_comment_manually_approved($comment) {
	argh_moderation_comment_manually_changed($comment, 'Approved');
}

function argh_moderation_comment_manually_moderated($comment) {
	argh_moderation_comment_manually_changed($comment, 'Moderated');
}

function argh_moderation_comment_manually_trashed($comment) {
	argh_moderation_comment_manually_changed($comment, 'Trashed');
}

function argh_moderation_discussion_notice() {
	#ideally only display when options-discussion.php
	if(strpos($_SERVER['REQUEST_URI'], 'options-discussion.php')) {
		echo '<div id="message" class="error"><p><strong>Warning</strong> <a href="options-general.php?page=argh-moderation">Better Moderation</a> installed, some settings may be overridden.</p></div>';
	}
}

function argh_moderation_hide_default_keys($option) {
	if(strpos($_SERVER['REQUEST_URI'], 'options-discussion.php')) {
		return $option;
	}
	else {
		return '';
	}
}

function argh_moderation_dashboard($excerpt) {
	global $pagenow, $comment;
	if(is_admin() && $pagenow == 'index.php') {
		#$excerpt .= '<pre>' . print_r($comment, TRUE) . '</pre>';
		if($comment->comment_approved == 0) {
			$reason = get_comment_meta($comment->comment_ID, 'moderation_reason', true);
			if($reason) {
				$excerpt .= '<br /><em>' . $reason . '</em>';
			}
		}
	}
	return $excerpt;
}

function argh_moderation_admin_add_page() {
	//create new top-level menu
	add_options_page('Better Moderation', 'Better Moderation', 'manage_options', 'argh-moderation', 'argh_moderation_option_page');
	//call register settings function
	add_action('admin_init', 'argh_moderation_register_settings');
	//pre moderation options on edit post/page
	add_meta_box('arghmoderationstatusdiv', 'Better Moderation', 'argh_moderation_status_meta_box', 'post', 'normal', 'high');
	add_meta_box('arghmoderationstatusdiv', 'Better Moderation', 'argh_moderation_status_meta_box', 'page', 'normal', 'high');
}

function argh_moderation_register_settings() {
	//register our settings
	register_setting('argh-moderation-group', 'argh_moderation_keys');
	register_setting('argh-moderation-group', 'comment_max_links');
	register_setting('argh-moderation-group', 'comment_moderation');
	register_setting('argh-moderation-group', 'comment_whitelist');
}

function argh_moderation_option_page() {
?>
<div class="wrap">
<h2>Better Moderation</h2>

<form method="post" action="options.php">
    <?php settings_fields('argh-moderation-group'); ?>
    <?php do_settings_sections('argh-moderation-group'); ?>
    <p>You need to enter the rules below, they should be on a new line for each rule and in the format
    <code>Text to match|Matching|Reason</code> eg.<br />
    <code>Steve Smith|Name|Spammer<br />
    joe@abc.com|Email|Off-topic<br />
    feck|All|Swear Word</code></p>
    <p>Values for Matching are: <code>name, email, url, text, ip, useragent, all</code></p>
    <p>If you just wish to match Word and not WordPress use the ^ character at the start or end to
    signfiy not preceded/followed by a letter eg.<br />
    <code>^feck|Text|Bad Language</code><br />
    would match feck, fecking, fecker, but not mutherfecker.</p>
    <table class="form-table">
        <tr valign="top">
        	<th scope="row"><?php _e('Moderation Values') ?></th>
        	<td><textarea name="argh_moderation_keys" rows="10" cols="50" id="argh_moderation_keys" class="large-text code"><?php echo get_option('argh_moderation_keys'); ?></textarea></td>
        </tr>
        <tr valign="top">
        	<th scope="row"><?php _e('Moderation Settings') ?></th>
        	<td>
        		<fieldset><label for="comment_max_links"><?php printf(__('Hold a comment in the queue if it contains %s or more links. (A common characteristic of comment spam is a large number of hyperlinks.)'), '<input name="comment_max_links" type="text" id="comment_max_links" value="' . esc_attr(get_option('comment_max_links')) . '" class="small-text" />' ) ?></label><br />
        		<label for="comment_moderation"><input name="comment_moderation" type="checkbox" id="comment_moderation" value="1" <?php checked('1', get_option('comment_moderation')); ?> /> <?php _e('An administrator must always approve the comment') ?> </label><br />
				<label for="comment_whitelist"><input type="checkbox" name="comment_whitelist" id="comment_whitelist" value="1" <?php checked('1', get_option('comment_whitelist')); ?> /> <?php _e('Comment author must have a previously approved comment') ?></label></fieldset>
        	</td>
        </tr>
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php
}

function argh_moderation_status_meta_box($post) {
	$post_ID = $post->ID;
	$set = get_post_meta($post_ID, 'argh_moderation_pre', true);
?>
	<p>
		<input name="argh_moderation" type="hidden" value="update" />
		<label for="argh_moderation_pre_moderate" class="selectit">
			<input type="checkbox" name="argh_moderation_pre_moderate" id="argh_moderation_pre_moderate" <?php if($set == 1) echo ' checked="checked"'; ?>/>
			Pre Moderate Comments
		</label>
	</p>
<?php
}

/**
 * Get custom POST vars on edit/create post pages and update options accordingly
 */
// My method would be to use post-meta to record if this post needs moderation, not one setting with a big array
function argh_moderation_meta_save() {
	if(!empty($_POST['argh_moderation']) && $_POST['argh_moderation'] == 'update' ) {
		$post_ID = $_POST['post_ID'];
		if(!empty($_POST['argh_moderation_pre_moderate']) && $_POST['argh_moderation_pre_moderate'] == 'on') {
			update_post_meta($post_ID, 'argh_moderation_pre', 1);
		}
		else {
			delete_post_meta($post_ID, 'argh_moderation_pre');
		}
	}
}

/**
 * Tell WordPress what to do.  Action hooks.
 */
add_action('save_post',                      'argh_moderation_meta_save');
add_action('manage_comments_custom_column',  'argh_moderation_custom_comment_column_content', 10, 2);
add_filter('manage_edit-comments_columns',   'argh_moderation_custom_comment_column_headings');
add_action('preprocess_comment',             'argh_moderation_moderate_comment', 1);
add_action('wp_insert_comment',              'argh_moderation_add_reason', 10, 2);
#add_filter('comment_notification_text',     'argh_moderation_notification_email', 10, 2);
add_filter('comment_moderation_text',        'argh_moderation_notification_email', 10, 2); #need both?
add_action('comment_unapproved_to_approved', 'argh_moderation_comment_manually_approved');
add_action('comment_approved_to_unapproved', 'argh_moderation_comment_manually_moderated');
add_action('comment_unapproved_to_trash',    'argh_moderation_comment_manually_trashed');
add_action('comment_approved_to_trash',      'argh_moderation_comment_manually_trashed');
add_action('comment_trash_to_approved',      'argh_moderation_comment_manually_approved');
add_action('comment_trash_to_unapproved',    'argh_moderation_comment_manually_moderated');
add_action('admin_notices',                  'argh_moderation_discussion_notice');
add_filter('option_moderation_keys',         'argh_moderation_hide_default_keys');
add_filter('comment_excerpt',                'argh_moderation_dashboard');
add_action('admin_menu',                     'argh_moderation_admin_add_page');
?>