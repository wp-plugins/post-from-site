=== Plugin Name ===
Contributors: ryelle
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YB5AWJMBLCCVC&lc=US&item_name=redradar%2enet&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: quick post, frontend, insert post, post
Requires at least: 2.7
Tested up to: 2.7
Stable tag: trunk

This plugin allows you to post straight from your front-end (i.e. website) - perfect for a quick update! Also useful if you have multiple users and don’t need them to see the admin side of things. 

== Description ==

This new wordpress plugin allows you to post straight from your front-end (i.e. website) - perfect for a quick update! Also useful if you have multiple users and don’t need them to see the admin side of things. It creates a link on your website which, when clicked, will bring up a simple text-box. You can enter a post title, content, and add categories (more features in the works, such as media support).  

On the admin side, there is a settings page where you can edit the plugin to your preferences. You can customize the link text, post-box background color, title/text color, and even add your own CSS to tailor pfs to your site.

As for permissions, you can limit the categories pfs can post to and allow/disallow uploading of images (currently under work, will be fixed in next version). If you’d rather have pfs’s posts approved before they are visible, you can set the post status to ‘pending’ or ‘draft’. Same with the comment status, it can default to ‘open’ (allowing comments) or ‘closed’ (not allowing comments).

== Installation ==

1. Unzip `pfs.zip`
1. Upload all files to the `/wp-content/plugins/pfs` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php if (function_exists('post_from_site')) {post_from_site();} ?>` in your templates where you want to see the link.

== Frequently Asked Questions ==

No questions yet - [ask one](http://www.redradar.net/wp/?p=95)?

== Screenshots ==

1. Post-from-site's setting page in admin
2. Post-from-site in action - *note: you can customize the CSS yourself so it doesn't need to look like the above.*
