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
define( 'AUTH_KEY',         'gXFav~F-VA{Zy(t+_K!;zHXbxt]miUFkjM~8zRnA1}gluOv3GnA+,)hJrf+StsK2' );
define( 'SECURE_AUTH_KEY',  'em+heo?_=@|1e]a2NmbvZ$PQ}k-@G_3?7jq{q7pcWUwhcU)5$/!lUK<J?ND2uUnN' );
define( 'LOGGED_IN_KEY',    'm>=|lQ|F#;[CUlrJuI2I*FA0?fPkFh9Fe/(1 uUe|_+5OATuy`Vw/iga@PxOd|_ ' );
define( 'NONCE_KEY',        '76u,]Z2_DB14jZjL~ueC>;.!h>* D;b(8XRmH}h1)5iLPa(BOVONMA%+cJ0cBH_H' );
define( 'AUTH_SALT',        'y,b[W44NJ$MeSKC:`Wq0<^K{L;r4f6D.8iPfQK?6)Z! q7(r,5,gO,i*@M*Z,x/+' );
define( 'SECURE_AUTH_SALT', 'Tvvz0=XrDl.Nl{T&C@;6wf5sOOImh0aIcAoK+!5{>X3K)i=%mVH+TEux_/M8n=Nc' );
define( 'LOGGED_IN_SALT',   '1:]a/cI/B0{xF~]S@~[T!Dn1|&{dz?Zu?4pZ6Wt^EbP[QI7!W7>($VS>XB4h@,Zy' );
define( 'NONCE_SALT',       'pD<^+6$F<rpW[UnWe(V?EFHuV[umtXV9% t|_-f6f?S#fI=0,B}1UG6]J#ZL|Gzv' );

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
