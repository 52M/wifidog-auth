<?php

/* 
 * File version: $Id$
 *
 * Log history:
 *
 *     $Log$
 *     Revision 1.23  2005/03/29 22:13:27  fproulx
 *     2005-03-28 Fran�ois Proulx  <francois.proulx@gmail.com>
 *     	* schema_validate.php : Modified schema : dropped e-mail + account unique index, dropped email not empty constraint
 *     	* Schema is now at version 3
 *     	* Coded RADIUS authentication
 *     	* Modified templates to show a select box when more than one server is configured
 *     	* Coded RADIUS accounting and backward compatibility accounting
 *     	* Modified many statistics SQL queries to match new Users table
 *     	* modified statistics templates to match user_id and account_origin
 *     	* TODO : Fix lost_username and lost_password ( issue since we dropped the unique constraint on emails... )
 *     	* TODO : Heavy testing possibly with remote RADIUS servers
 *
 *     Revision 1.22  2005/03/28 19:49:52  benoitg
 *     2005-03-28 Benoit Gr�goire  <bock@step.polymtl.ca>
 *     	* common.php:  Add get_guid() function
 *     	* validate_schema.php: New auto-upgrade script to allow autaumatic schema upgrade.  Note that you must still update dump_initial_data_postgres.sh and use sync_sql_for_cvs.sh so new users aren't left in the cold.
 *     	* New class Authenticator (and subclasses):  Begin virtualizing the login process.
 *
 *     Revision 1.21  2005/03/17 03:57:39  masham
 *      * use __FILE__ to resolve location of local.config
 *
 *     Revision 1.20  2005/03/17 00:36:21  masham
 *      * config.php will use "local.config.php" instead, if present.  avoid cvs over-writing.
 *      * if CUSTOM_SIGNUP_URL is defined, signup.php will re-direct.  For integration with existing auth systems.
 *
 *     Revision 1.19  2005/01/26 03:46:30  benoitg
 *     2005-01-25 Benoit Gr�goire  <bock@step.polymtl.ca>
 *     	* classes/Node.php:  New file, untested code example
 *     	* wifidog/admin/admin_common.php: Remove double-defined BASEPATH
 *
 *     Revision 1.18  2005/01/19 00:05:37  aprilp
 *     Removed references to user_management pages that don't exist anymore
 *
 *     Revision 1.17  2005/01/18 14:36:50  aprilp
 *     Changed default language to fr_FR instead of fr, it wasn't working on some platforms
 *
 *     Revision 1.16  2005/01/16 21:21:55  aprilp
 *     *** empty log message ***
 *
 *     Revision 1.15  2005/01/12 15:52:36  aprilp
 *     *** empty log message ***
 *
 *     Revision 1.14  2005/01/12 00:57:42  benoitg
 *     2004-01-10 Benoit Gr�goire  <bock@step.polymtl.ca>
 *     	* wifidog/config.php:  Add list of hotspot to network rss feed list (not yet functionnal)
 *     	* wifidog/hotspot_status.php:  Allow RSS export of the list of deployed HotSpots.
 *     	* wifidog/admin/incoming_outgoing_swap.php:  Script to swap incoming and outgoing in your data.  only use this if you had gateways before 1.0.2 and wish to correct your logs before you upgrade.
 *     	* wifidog/classes/RssPressReview.inc:  Missing file from previous commit.
 *     	* wifidog/portal/index.php: Preliminary work to enable smart press review of multiple RSS feeds.
 *
 *     Revision 1.13  2004/12/03 19:42:32  benoitg
 *     2004-12-03 Benoit Gr�goire  <bock@step.polymtl.ca>
 *     	* wifidog/admin/user_stats.php,  wifidog/classes/Statistics.php:  Embryonic aggregate user stats.  Currently allows you to find out the rate at which your users subscribe.
 *     	* wifidog/config.php, wifidog/local_content/default/login.html, wifidog/include/user_management_menu.php:  Add hotspot status page to login page.
 *     	* wifidog/hotspot_status.php: Cosmetic
 *     	* wifidog/admin/hotspot_log.php: Stats now need admin privileges
 *     	* wifidog/index.php: Cosmetic.
 *
 *     Revision 1.12  2004/11/20 03:28:25  benoitg
 *     2004-11-19 Benoit Gr�goire  <bock@step.polymtl.ca>
 *     	* TODO: Add email domains to blacklist
 *     	* wifidog/config.php, wifidog/include/user_management_menu.php: Add tech support email address
 *     	* wifidog/hotspot_status.php: List of HotSpots that are open with summary of information.  Designed to be included as part of another page.
 *     	* wifidog/local_content/common/wifidog_logo_banner.gif: Add wifidog logo
 *     	* wifidog/local_content/default/hotspot_logo_banner.jpg: Shrink the logo and write unknown hotspot, however this is still really ugly
 *     	* wifidog/local_content/default/login.html, portal.html, stylesheet.css: Cosmetic fixes
 *      	* wifidog/local_content/default/login.html.fr, portal.html.fr: Delete the files, this isn't the approach we will use for translation.
 *     	* sql/wifidog-postgres-initial-data.sql, wifidog-postgres-schema.sql: Update with new node information structures.
 *
 *     Revision 1.11  2004/09/28 20:46:40  yanik_crepeau
 *     Added experimental (and commented) code for next developpments
 *     regarding the language/localization issues.
 *
 *     Revision 1.10  2004/09/28 20:44:08  yanik_crepeau
 *     Added commented hearder with Id and Log cvs keywords.
 *
 *
 */

if(file_exists(dirname(__FILE__)."/local.config.php")) {
	// use a local copy of the configuration if found instead of the distro's.
	require dirname(__FILE__)."/local.config.php";
} else {

/* Used by AbstractDb */
define('CONF_DATABASE_HOST',   'localhost');
define('CONF_DATABASE_NAME',   'wifidog');
define('CONF_DATABASE_USER',   'wifidog');
define('CONF_DATABASE_PASSWORD',   'wifidogtest');

/*************************** Common setup option.  Adjust to suit your environment *******************************/

/* The SYSTEM_PATH, must be set to the url path needed to reach the wifidog directory.  Normally '/' or '/wifidog/', depending on where configure your document root.  Gateway configuration must match this as well */
define('SYSTEM_PATH', '/');
/**< Set this to true if your server has SSL available, otherwise, passwords will be transmitted in clear text over the air */
define('SSL_AVAILABLE', true); 
define('HOTSPOT_NETWORK_NAME', '�le sans fil');
define('HOTSPOT_NETWORK_URL', 'http://www.ilesansfil.org/');
define('TECH_SUPPORT_EMAIL', 'tech@ilesansfil.org');
define('UNKNOWN_HOSTPOT_NAME', 'Unknown HotSpot');

define('VALIDATION_EMAIL_FROM_ADDRESS', 'validation@ilesansfil.org');
define('VALIDATION_EMAIL_SUBJECT', HOTSPOT_NETWORK_NAME.' new user validation');
define('VALIDATION_GRACE_TIME', 20); /**< Number of minutes after new account creation during which internet access is available to validate your account.  Once elapsed, you have to validate from home... */
define('LOST_PASSWORD_EMAIL_SUBJECT', HOTSPOT_NETWORK_NAME.' new password request');
define('LOST_USERNAME_EMAIL_SUBJECT', HOTSPOT_NETWORK_NAME.' lost username request');
/* RSS support.  If set to true, MAGPIERSS must be installed in MAGPIE_REL_PATH */
define('RSS_SUPPORT', true); 
/* Normally, the database cleanup routines will be called everytime a portal page is displayed.  If you set this to true, you must set a cron job on the server which will execute the script cron/cleanup.php. */
define('CONF_USE_CRON_FOR_DB_CLEANUP', false);


/* Use a custom signup system instead of the built in signup page. */
//define("CUSTOM_SIGNUP_URL","https://www.bcwireless.net/hotspot/signup.php");

/** The next two items are constants, do not edit */
define('DBMS_MYSQL','AbstractDbMySql.php');
define('DBMS_POSTGRES','AbstractDbPostgres.php');

/** Defines which Database management software you want to use */
define('CONF_DBMS',DBMS_POSTGRES);

/***** You should normally not have to edit anything below this ******/
define('WIFIDOG_NAME', 'WiFiDog Authentication server');
define('WIFIDOG_VERSION', 'CVS');

define('MAGPIE_REL_PATH',  'lib/magpie/');
define('SMARTY_REL_PATH',  'lib/smarty/');
//define('NETWORK_RSS_URL', 'http://wifinetnews.com/index.rdf');
define('NETWORK_RSS_URL', 'http://patricktanguay.com/isf/atom.xml, http://auth.ilesansfil.org/hotspot_status.php?format=RSS');
define('UNKNOWN_HOTSPOT_RSS_URL', '');

define('LOCAL_CONTENT_REL_PATH', 'local_content/');//Path to the directory containing the different node specific directories.  Relative to BASE_URL_PATH

/* Authentication sources section */
 define('LOCAL_USER_ACCOUNT_ORIGIN', 'LOCAL_USER');
require_once BASEPATH.'classes/AuthenticatorLocalUser.php';
define('IDRC_ACCOUNT_ORIGIN', 'IDRC_RADIUS_USER');
require_once BASEPATH.'classes/AuthenticatorRadius.php';

/* The array index for the source must match the account_origin in the user table */
 $AUTH_SOURCE_ARRAY[LOCAL_USER_ACCOUNT_ORIGIN]=array(
						     'name'=>HOTSPOT_NETWORK_NAME,
						     'authenticator'=>new AuthenticatorLocalUser(LOCAL_USER_ACCOUNT_ORIGIN));
$AUTH_SOURCE_ARRAY[IDRC_ACCOUNT_ORIGIN]=array(
						     'name'=>"IDRC RADIUS",
						     'authenticator'=>new AuthenticatorRadius(IDRC_ACCOUNT_ORIGIN, "localhost", 1812, 1813, "secret_key"));


/*These are the file names of the different templates that can be put in the CONTENT_PATH/(node_id)/ folders */
define('STYLESHEET_NAME', 'stylesheet.css');
define('LOGIN_PAGE_NAME', 'login.html');
define('PORTAL_PAGE_NAME', 'portal.html');
define('PAGE_HEADER_NAME', 'header.html');
define('PAGE_FOOTER_NAME', 'footer.html');
define('HOTSPOT_STATUS_PAGE', 'hotspot_status.php');
define('HOTSPOT_LOGO_NAME', 'hotspot_logo.jpg');
define('HOTSPOT_LOGO_BANNER_NAME', 'hotspot_logo_banner.jpg');

/* Path for files in LOCAL_CONTENT_REL_PATH/common/ */
define('NETWORK_LOGO_NAME', 'network_logo.png');
define('NETWORK_LOGO_BANNER_NAME', 'network_logo_banner.png');
define('WIFIDOG_LOGO_NAME', 'wifidog_logo_banner.png');
define('WIFIDOG_LOGO_BANNER_NAME', 'wifidog_logo_banner.png');

define('DEFAULT_NODE_ID', 'default');
define('DEFAULT_LANG', 'fr_FR');

/*** FOR TESTING ONLY *** UNCOMMENT THE NEXT LINES
 *** WHEN READY TO TEST **************************
 *** This code define two (2) global variables:
 *** HTTP_HEADER_LANG will return the language of the
 *** user's browser stripped from its country code. For
 *** instance, if the HTTP_ACCEPT_LANGUAGE header is 
 *** something like 'en-ca, en-us, en, fr-ca, fr', the
 *** first two characters is picked and used as language
 *** selector.
 *** The other global variable is the FILE_NAME_SUFFIX.
 *** for english it is empty, for any other language it is
 *** the two character code used to set the language. When
 *** choosing the template (let's say login.html), we simply
 *** have to replace in the code and add 
 *** "login.html".$FILE_NAME_SUFFIX to get the right 
 *** template (assuming that login.html.fr exists for
 *** French).
if (if (substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2) == "en") {
    define('HTTP_HEADER_LANG', 'en');
    define('FILE_NAME_SUFFIX', '');   
} else {
    define('HTTP_HEADER_LANG', substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2));
    define('FILE_NAME_SUFFIX', substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2));
}

*/ 
}
?>
