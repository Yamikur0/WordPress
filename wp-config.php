<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'g9]aD7S3z.0qgTpGkinWF;|&LylS=^~oB8#[KFZzK>~jNd7Zo|]6nux/j`Z7/L9|' );
define( 'SECURE_AUTH_KEY',  'P_b~3yqhW`I#B=f2j53#<ZcCI2A;jv}NPG(?,WaE/Zue|Wgn)jAVWW^{@]$HL|T^' );
define( 'LOGGED_IN_KEY',    '7CFp~)O*%Ho^Yr[5S4M& 8~~hMe I]Ju&QCi+o$Z%#_p)WP?y}.5.)`60G:itR)2' );
define( 'NONCE_KEY',        '3=NbW|1pL`3GdUMh3:R`[]6rT}QNp4O/}/(~<nq)x{ECw_v:aoqYD<i_!+INk$ 0' );
define( 'AUTH_SALT',        'Z>sHv!f[L&;1Og8i:+ q:snJ2?i 8s}mtb8;o3%FZ:cf:;J0?q]{Mf4pOSQ4d@A0' );
define( 'SECURE_AUTH_SALT', '~1oZ#q!d)BRQ9, KW_]7&z(f]iy1%`fM(JS6>U%tKTZIj@;(CJ}In9AuJ)yk,,Lm' );
define( 'LOGGED_IN_SALT',   'GsFz,uzJ.-lBq^Sl3pl-KOwIhk,,44V`~Bx/yA57&PMDk*|EPICsPMsk?j2g^zCL' );
define( 'NONCE_SALT',       'fX lfoh!w@HuXe7%[4*LCEJ]0RxG&>(YRTR4/!o-|Yv}P4Z^<H5x8L2:MX2Kt,pZ' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
