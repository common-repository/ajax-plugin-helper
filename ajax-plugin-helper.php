<?php
/**
 * An Ajax Plugin Helper for the WordPress admin plugin page.  Adds Ajax activate, deactivate, delete and upgrade.
 *
 * Allows a user to activate, deactive, delete and upgrade plugins from the admin plugins page without leaving the
 * plugins page.
 *
 * @author Matt Martz <matt@sivel.net>
 * @version 1.2
 * @package shadowbox-js
 */
/*
Plugin Name: Ajax Plugin Helper
Plugin URI: http://sivel.net/wordpress/ajax-plugin-helper/
Description: An Ajax Plugin Helper for the WordPress admin plugin page.  Adds Ajax activate, deactivate, delete and upgrade.
Author: Matt Martz
Author URI: http://sivel.net
Version: 1.2a

		Copyright (c) 2009 Matt Martz (http://sivel.net)
		Ajax Plugin Helper is released under the GNU General Public License (GPL)
		http://www.gnu.org/licenses/gpl-2.0.txt
*/

class AjaxPluginHelper {

	/**
	 * String holding the full file system path of the main plugin file for
	 * use in the other included files 
	 *
	 * @since 1.1
	 * @var string
	 */
	var $plugin_file;

	/**
	 * String holding the plugin basename of the main plugin file for
	 * use in the other included files 
	 *
	 * @since 1.1
	 * @var string
	 */
	var $plugin_file_basename;

	/**
	 * String holding the plugin basename of the directory containing 
	 * the main plugin file for use in the other included files 
	 *
	 * @since 1.1
	 * @var string
	 */
	var $plugin_dir_basename;

	/**
	 * PHP4 style constructor.
	 *
	 * Calls the below PHP5 style constructor.
	 *
	 * @since 1.0
	 * @return none
	 */
	function AjaxPluginHelper() {
		$this->__construct();
	}

	/**
	 * PHP5 style contructor
	 *
	 * Hooks into all of the necessary WordPress actions and filters needed
	 * for this plugin to function
	 *
	 * @since 1.0
	 * @return none
	 */
	function __construct() {
		$this->plugin_file = __FILE__;
		$this->plugin_file_basename = plugin_basename(__FILE__);
		$this->plugin_dir_basename = dirname(__FILE__);
	}
}

/**
 * Hook into init so we don't perform any operations too soon, and check
 * that we are in the admin so that we are only ever even loading the 
 * majority of this file in the admin
 *
 * @since 1.1
 */
add_action('init', 'ajax_plugin_helper_init');
function ajax_plugin_helper_init() {
	if ( is_admin() ) {
		global $pagenow;
		if ( defined('DOING_AJAX') && DOING_AJAX === true ) {
			include('inc/ajax.php');
			$AjaxPluginHelperAjax = new AjaxPluginHelperAjax();
		} else {
			include('inc/admin.php');
			$AjaxPluginHelperAdmin = new AjaxPluginHelperAdmin();
			if ( isset($pagenow) && $pagenow == 'plugins.php' ) {
				include('inc/jscss.php');
				$ajaxPluginHelperJsCss = new AjaxPluginHelperJsCss();
			}
		}
	}
}
