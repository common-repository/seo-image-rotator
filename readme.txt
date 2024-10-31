=== Plugin Name ===
Contributors:E-Koncept
Donate link: http://www.e-koncept.co.nz/seo-image-rotator/
Tags: multi slideshow, images, jquery cycle, carousel, wp-cycle
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 0.4.5
License: GPLv2 or later

== Description ==

The SEO Image Rotator is based on the "WP-Cycle + Captions" plugin allows you to upload images from your computer, which can then be used to generate multiple jQuery Cycle slideshows.

Each image can be given a URL, an ALT attribute, a Caption and a title (for SEO benefit). The slideshow is set to pause when the user hovers over the slideshow images, giving them ample time to click the link.

Additions include:

- Ability to create multiple instances of wp-cycle within a single wordpress/plugin installation. This can be done by adding an id value in the shortcode like "[seo_image_rotator id="2"] where the id is equal to the ID of the rotator you want to include on a given post or page

- Ability to add a title to each image for search engine optimisation benefit, which adds the title="example" attribute to the image tag.

- Ability to disable the caption of an individual rotator instance.

== Installation ==

1. Upload the entire `seo-image-rotator` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin, and upload/edit/delete images via the "SEO Image Rotator" menu
1. Place `<?php echo do_shortcode('[seo_image_rotator id=XX]'); ?>` in your theme where you want the slideshow to appear (XX equals the ID of the rotator you want to add)
1. Alternatively, you can use the shortcode [seo_image_rotator id=XX] in a post or page to display the slideshow  (XX equals the ID of the rotator you want to add).

== Troubleshooting ==

= Internet Explorer Display issue =
Images may appear blank or hidden. Check your themes style sheet for IMG { max-width:100%; } and comment or remove it.

== Frequently Asked Questions ==

= Is this plugin based on WP-Cycle = 

Yes this plugin is based on [WP-Cycle](http://wordpress.org/extend/plugins/wp-cycle/ "WP-Cycle") by [Nathan Rice](http://www.nathanrice.net "Nathan Rice").

= My images won't upload. What should I do? =

The plugin uses built in WordPress functions to handle image uploading. Therefore, you need to have [correct permissions](http://codex.wordpress.org/Changing_File_Permissions "Changing File Permissions") set for your uploads directory.

Also, a file that is not an image, or an image that does not meet the minimum height/width requirements, will not upload. Images larger than the dimensions set in the Settings of this plugin will be scaled down to fit, but images smaller than the dimensions set in the Settings will NOT be scaled up. The upload will fail and you will be asked to try again with another image.

Finally, you need to verify that your upload directory is properly set. Some hosts screw this up, so you'll need to check. Go to "Settings" -> "Miscellaneous" and find the input box labeled "Store uploads in this folder". Unless you are absolutely sure this needs to be something else, this value should be exactly this (without the quotes) "wp-content/uploads". If it says "/wp-content/uploads" then the plugin will not function correctly. No matter what, the value of this field should never start with a slash "/". It expects a path relative to the root of the WordPress installation.

= I'm getting an error message that I don't understand. What should I do? =

Please [contact me](http://www.e-koncept.co.nz/contact-us/ "Contact Me"). This plugin is now relatively stable, so if you are experiencing problems that you would like me to diagnose and fix, please contact me.

As much as I would like to, in most cases, I cannot provide free support.

= How can I style the slideshow further? =
In the settings of the plugin, you're able to set a custom DIV ID for the slideshow. Use that DIV ID to style the slideshow however you want using CSS.

= In what order are the images shown during the slideshow? =

Chronologically, from the time of upload. For instance, the first image you upload will be the first image in the slideshow. The last image will be the last, etc.

= Can I reorder the images? =

Not at the moment.

= Can I rotate anything other than images with this plugin? =

No. This is an image slideshow. Enjoy it for what it is.

== Changelog ==



