<?php
define( 'WP_CACHE', true ); // By Speed Optimizer by SiteGround

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dblxnwfnjh2meb' );

/** Database username */
define( 'DB_USER', 'uqo7qhvxdyc1b' );

/** Database password */
define( 'DB_PASSWORD', 'fmeqbgcgqlkk' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('log_errors', 1);
@ini_set('display_errors', 0);





/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '!1K}>M]WS*Lg+ivK%O@I9TW-C:fmIs@NPf&u8QYVSpLSn;8/#8qG)ta1hV<_}:L*' );
define( 'SECURE_AUTH_KEY',   'FeqeO_-o^)7A1 -g2Y&awscHzo^fjsy{Cjg-qZ$s>XbB?_=IO}9KeS4[XF]|o{Y-' );
define( 'LOGGED_IN_KEY',     '5k4^!-L-xa]#_8<|EwB2u.!bMmKip~)zhwXUT,NxImeww#xD=w? fa89d03?k0(=' );
define( 'NONCE_KEY',         'bl(]DgG$6BZz(RqXtgNf4T3 Rl-b|x!2^2?9G4J9/rE>bh<dsHg_G?r)Ea:dI*-`' );
define( 'AUTH_SALT',         'T+L:%f.t#jgL`k-wi+m=UFoyos).`wgzn@;4/Mx<81f+I7)[K0m+ROD4LPErHJz0' );
define( 'SECURE_AUTH_SALT',  '!``m,.eQP;3lZx)t99)pM~O2qr|:f5DqI^h4p?gn}8A<Ja?V+iI|~_5{O*c{:!B/' );
define( 'LOGGED_IN_SALT',    'CgMJP[|*B-_3MRJvOM?nU).WcdOc`u([_2zT[0q$0|6wA+RuZp`_f@Jy(:*gO6K4' );
define( 'NONCE_SALT',        'iORNlC^--a]4P(URb`_=/,?P/N!uYXf5H6scoRM,hL:Ibd_CaTje(F&IUE3Aj)(M' );
define( 'WP_CACHE_KEY_SALT', 't?.U]K+*4x|t Nq>+)NJVcYytG015U*0uKI t|1w?y/.jB;}v..wZ?64I1PAj@{n' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'lum_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
@include_once('/var/lib/sec/wp-settings-pre.php'); // Added by SiteGround WordPress management system
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
