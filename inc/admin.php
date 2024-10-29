<?php
class AjaxPluginHelperAdmin extends AjaxPluginHelper {

	/**
	 * PHP4 style constructor.
	 *
	 * Calls the below PHP5 style constructor.
	 *
	 * @since 1.0
	 * @return none
	 */
	function AjaxPluginHelperAdmin() {
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
		AjaxPluginHelper::__construct();
		add_action('admin_menu', array(&$this, 'add_options_page')) ;
		register_activation_hook($this->plugin_file, array(&$this, 'activation'));
		add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
	}

	/**
	 * Action hook callback for activation
	 *
	 * Initializes the plugin for first time use and notifies users of 
	 * any issues they may encounter.
	 *
	 * @since 1.0
	 * @return none
	 */
	function activation() {
		if ( !$this->can_modify_fs() ) {
			set_site_transient('ajax-plugin-helper-fs', true, 1);
		}
	}

	/**
	 * Check if there are any messages to be displayed to the user
	 *
	 * @since 1.0
	 * @return none
	 */
	function plugins_loaded() {
		if ( ! current_user_can('update_plugins') )
			return;
		if ( get_site_transient('ajax-plugin-helper-fs') == true ) {
			add_action('admin_notices', array(&$this, 'modify_fs_notice'));
		}
		load_plugin_textdomain('ajax-plugin-helper', false, $this->plugin_dir_basename . '/localization');
	}

	/**
	 * Action hook callback for displaying message letting
	 * user know that ajax upgrade and delete functions 
	 * are not available due to not being able to perform 
	 * file system tasks without prompting for FTP/SSH/SFTP
	 * credentials
	 *
	 * @sinec 1.0
	 * @return none
	 */
	function modify_fs_notice() {
?>
	<div class="error"><?php _e('Ajax Plugin Helper has determined that it cannot provide access to the Ajax Upgrade and Ajax Delete functionality because your install cannot perform file system level tasks without prompting for FTP/SFTP connection information. Please consult the FAQ at ', 'ajax-plugin-helper'); ?><a href="http://sivel.net/wordpress/ajax-plugin-helper/#faq">http://sivel.net/wordpress/ajax-plugin-helper/#faq</a></div>
<?php		
	}

	/**
	 * Action hook callback to filter the plugin action links
	 *
	 * @since 1.0
	 * @return none
	 */
	function add_options_page() {
		if ( current_user_can('update_plugins') ) {
			add_filter("plugin_action_links" ,array(&$this, 'filter_plugin_actions'), 10, 2);
		}
	}

	/**
	 * Function to check whether PHP can make file system level changes
	 * without requesting login information at the time the update or 
	 * deletion is requested.
	 *
	 * @since 1.0
	 * @see http://codex.wordpress.org/Editing_wp-config.php#FTP.2FSSH_Constants
	 * @see http://www.firesidemedia.net/dev/wordpress-install-upgrade-ssh/
	 * @return boolean
	 */
	function can_modify_fs() {
		// Output buffer to supress the echoes from request_filesystem_credentials
		ob_start();
		if ( false !== ($credentials = request_filesystem_credentials('')) ) {
			ob_end_clean();
			return true;
		} else {
			ob_end_clean();
			return false;
		}
	}

	/**
	 * Action hook callback to populate update message show in below each plugin
	 * requiring an update on plugins.php
	 *
	 * Code taken from http://core.trac.wordpress.org/browser/tags/2.8.2/wp-admin/update.php
	 *
	 * @since 1.0
	 * @see http://core.trac.wordpress.org/browser/tags/2.8.2/wp-admin/update.php
	 * @param string $file plugin_basename of the current plugin the action was called for
	 * @param array $plugin_data array of plugin information of the current plugin the action was called for
	 * @return none
	 */
	function wp_plugin_update_row($file, $plugin_data) {
		$current = get_site_transient('update_plugins');
		if ( !isset($current->response[ $file ]) )
			return false;

		$r = $current->response[ $file ];

		$plugins_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());
		$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );

		$details_url = admin_url('plugin-install.php?tab=plugin-information&plugin=' . $r->slug . '&TB_iframe=true&width=600&height=800');

		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">';
		if ( ! current_user_can('update_plugins') )
			printf( __('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s Details</a>.', 'ajax-plugin-helper'), $plugin_name, esc_url($details_url), esc_attr($plugin_name), $r->new_version );
		else if ( empty($r->package) )
			printf( __('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s Details</a> <em>automatic upgrade unavailable for this plugin</em>.', 'ajax-plugin-helper'), $plugin_name, esc_url($details_url), esc_attr($plugin_name), $r->new_version );
		else if ( ! $this->can_modify_fs() || $file == $this->plugin_file_basename ) 
			printf( __('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s Details</a> or <a href="%5$s">upgrade automatically</a>.', 'ajax-plugin-helper'), $plugin_name, esc_url($details_url), esc_attr($plugin_name), $r->new_version, wp_nonce_url('update.php?action=upgrade-plugin&plugin=' . $file, 'upgrade-plugin_' . $file) );
		else
			printf( __('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s Details</a> or <a class="ajaxpluginupdate %5$s-update" rel="%6$s" href="">Ajax Upgrade</a>.', 'ajax-plugin-helper'), $plugin_name, esc_url($details_url), esc_attr($plugin_name), $r->new_version, str_replace(array('/','.'), '-', $file), $file );

		do_action( "in_plugin_update_message-$file", $plugin_data, $r );

		echo '</div></td></tr>';
	}

	/**
	 * Filter hook callback to insert and modify plugin action links
	 *
	 * @since 1.0
	 * @param array $links array of current action links to be filtered
	 * @param string $file plugin_basename of the current plugin the filter was called for
	 * @return array array of plugin action links
	 */
	function filter_plugin_actions($links, $file) {
		global $wp_version;
		// Remove the core update message action callback and use a custom one
		if ( $file != $this->plugin_file_basename ) {
			if ( version_compare('2.9', preg_replace('/[a-z-]+/i', '', $wp_version), '<=') ) {
				remove_action("after_plugin_row_$file", 'wp_plugin_update_row', 10, 2);
				add_action("after_plugin_row_$file", array(&$this, 'wp_plugin_update_row'), 10, 2);
			} else {
				remove_action('after_plugin_row', 'wp_plugin_update_row', 10, 2);
				add_action('after_plugin_row', array(&$this, 'wp_plugin_update_row'), 10, 2);
			}

		}

		$update_plugins = get_site_transient('update_plugins');
		if ( current_user_can('update_plugins') && $file != $this->plugin_file_basename ) {
			$class = str_replace(array('/','.'), '-', $file);
			$spin_act = "<img class='{$class}-spin hidden' src='" . admin_url('images/wpspin_light.gif') . "' alt='" . __('Loading...', 'ajax-plugin-helper') . "' />";
			$spin_del = "<img class='{$class}-spin-del hidden' src='" . admin_url('images/wpspin_light.gif') . "' alt='" . __('Loading...', 'ajax-plugin-helper') . "' />";
			// If plugin requires updating and php can make unquestioned file system changes add update link
			if ( isset($update_plugins->response[$file]) && $this->can_modify_fs() ) {
				$links['ajax_update'] = "<a class='ajaxpluginupdate {$class}-update' rel='{$file}' href=''>" . __('Ajax Upgrade', 'ajax-plugin-helper') . '</a>';
			}
			foreach ( $links as $key => $link ) {
				if ( $key == 'activate' ) { // Replace activate link with new
					$links[$key] = "{$spin_act}<a class='ajaxpluginactivate {$class}-activate' rel='{$file}' href=''>" . __('Ajax Activate', 'ajax-plugin-helper') . "</a><a class='ajaxplugindeactivate {$class}-deactivate hidden' rel='{$file}' href=''>" . __('Ajax Deactivate', 'ajax-plugin-helper') . '</a>';
				} else if ( $key == 'deactivate' ) { // Replace deactivate link with new
					$links[$key] = "{$spin_act}<a class='ajaxplugindeactivate {$class}-deactivate' rel='{$file}' href=''>" . __('Ajax Deactivate', 'ajax-plugin-helper') . "</a><a class='ajaxpluginactivate {$class}-activate hidden' rel='{$file}' href=''>" . __('Ajax Activate', 'ajax-plugin-helper') . '</a>';
				} else if ( $key == 'delete' && $this->can_modify_fs() ) { // Modify delete link if exists and can modify fs
					$links[$key] = "{$spin_del}<a class='ajaxplugindelete {$class}-delete' rel='{$file}' href=''>" . __('Ajax Delete', 'ajax-plugin-helper') . "</a>";
				}
			}
			if ( !isset($links['delete']) ) {
				$links['delete'] = "{$spin_del}<a class='ajaxplugindelete {$class}-delete hidden' rel='{$file}' href=''>" . __('Ajax Delete', 'ajax-plugin-helper') . "</a>";
			}
		}
		return $links;
	}

}
