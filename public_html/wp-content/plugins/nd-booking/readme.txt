=== Hotel Booking ===
Contributors: nicdark
Tags: booking, hotel, travel, book
Requires at least: 4.5
Tested up to: 6.6
Stable tag: 3.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hotel booking, perfect solution for manage Hotel reservations. For Hotel and Travel activities.

== Description ==

= Welcome to Hotel Booking WP plugin =
This plugin is an useful system to manage all your booking.

== Installation ==

1. Install and activate the plugin.

== Changelog ==

= 3.6 =
* fixed Local File Inclusion on search shortcode

= 3.5 =
* added new css rules

= 3.4 =
* added new layout 3 for search component elementor

= 3.3 =
* improved sanitation on POST requests

= 3.2 =
* Improved plugin security ( added realpath(), Data Sanitization/Escaping variables )
* Added wpdb::prepare() function on db query

= 3.1 =
* added layout 2 on room post grid ( elementor )
* added images size option on room post grid ( elementor )
* fixed math.round night number in single room
* fixed maj text on calendar

= 3.0 =
* elementor compatibility 3.6

= 2.9 =
* added order elementor component
* added steps elementor component
* added static shortcode room
* added static shortcode branch

= 2.8 =
* added 3rd color in plugin colors
* added themes label in admin plugin options
* created mtbs woo on integration room options for link the room to woo product
* added nd_booking_qnt_room_bookable() function for add availability alert on search page room preview
* added mandatory additional service ( example : cleaning fee )
* added elementor search and rooms post grid component
* added ical download reservations

= 2.7 =
* added alert messages addons
* added info price icon addons on room preview in the search page
* added branch selector addons in the search page

= 2.6 =
* updated metabox function for save all datas in db ( deleted all empty meta datas )
* changed exceptions date format
* changed single room layout

= 2.5 =
* added nonce on ajax calls

= 2.4.2 =
* sanitize POST and REQUEST calls
* added wp_remote_get() function for external requests ( stripe,paypal )

= 2.4.1 =
* sanitize, validate, and escape all datas on POST and GET requests
* improved plugins_url()
* added nonce on ajax calls
* removed import/export feature

= 2.4 =
* added some IDs on elements

= 2.3.9 =
* added compatibility with latest wp version

= 2.3.8 =
* added the possibility to change the slug for custom post type 1
* improved the export option feature

= 2.3.7 =
* changed some 404 static link

= 2.3.6 =
* fixed post_id null variable on paypal payment

= 2.3.5 =
* add new layout 5 on rooms component
* add new layout 2 on search component
* added new classes on style.css
* implemented ajax services filter on search results shortcode
* updated .pot file

= 2.3.4 =
* added the possibility to blog the reservation on certain days
* added minimum booking days option

= 2.3.3 =
* updated languages .pot file
* added Stripe payment methods addon
* added VAT and City TAX management
* added the possibility to add orders via admin panel

= 2.3.2 =
* updated languages .pot file
* added the possibility to link translated room with its default room when using WPML
* fixed date format when user book a room in the current day

= 2.3.1 =
* fixed special characters in order notification emails
* added wp_mail() function to send email notifications

= 2.3.0 =
* show all rooms in calendar view dashboard feature
* added new translations strings on email template
* added new translations strings on thank you and order page
* fixed week price problem on single room page
* extended the custom link feature for single room page

= 2.2.9 =
* direct booking access from the single room page

= 2.2.8 =
* updated .pot language file
* updated date picker language with date_i18n function
* added register and login alert on booking step

= 2.2.7 =
* added calendar view for better orders management 

= 2.2.6 =
* added coupon management
* added price guests options on plugin settings

= 2.2.5 =
* added new quantity options on room settings metabox

= 2.2.4 =
* added the possibility to set in plugin settings the price range values on the search archive page.

= 2.2.3 =
* added translations for date picker
* added action parameter on search visual composer component for WPML integration
* updated nd-booking.pot file with new translations

= 2.2.2 =
* added email notification templates
* solved integer night number on search shortcode

= 2.2.1 =
* update nd_booking_get_room_link() function

= 2.2 =
* improve external booking integration implementation

= 2.1 =
* added layout-2 for steps and orders components ( for better color management )
* added ID in some elements
* added some new css rules

= 2.0 =
* create new cpt
* create new metabox

= 1.0 =
* Initial version