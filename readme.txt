=== Plugin Name ===
Contributors: artesea
Donate link: http://blog.artesea.co.uk/donate
Tags: comment, moderation
Requires at least: 3.1
Tested up to: 3.1.2
Stable tag: 1.4

Provides more control over comment moderation

== Description ==

Whereas the built in moderation just looks for keywords wherever they appear in the comments, Better Moderation allows you to define if it should just be the username, url, email, comment, ip, useragent or all of them.
It also allows you to define if when looking for "word" whether you should match "WordPress".
Finally it lets you set reasons why words have been added to the list, allowing you to quickly realise why a comment has been added to the moderation queue.

== Installation ==

Recommend you use the built in plugin uploader which comes with WordPress, but if you prefer the manual way

1. Upload `argh_moderation.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= I have a question =

Please leave feedback on the blog post.

== Screenshots ==

1. screenshot-1.png the Better Moderation settings page.
1. screenshot-2.png the extra column in the Comments page showing the reason.

== Changelog ==

= 1.4 =
* Logging when comments are moved out of trash
* Added relevant settings from 'discussion' to the admin page
* Renamed Better Moderation

= 1.3 =
* Global variable for the reason so we don't need to check the comment twice
* IPs and UAs were being ignored as not in the place we were originally looking

= 1.2 =
* Allows for ^ to be used at the start or end of a match to ignore words prefixed or postfixed with other letters,
eg, `^feck` would match feck, fecking, fecker, but not mutherfecker.

= 1.1 =
* Spots for Comment "Whitelisting" (where a user is moderated if they have never had an accepted comment before)
* Logs when an admin trashes a comment

= 1.0 =
* Adds new Argh Moderation Menu
* Adds column with reason to edit-comments.php
* Adds reason for moderated comments to the overview on the dashboard
* Adds reason to the automatic email
* Logs when an admin either moderates or approves a comment
* Hides the default moderation info from the built in moderation action
* Warns on the discussion page that alternative settings are in use

== Upgrade Notice ==

= 1.3 =
* IP and Useragent weren't correctly being examined.