<?php
class AjaxPluginHelperAjax extends AjaxPluginHelper {

	/**
	 * PHP4 style constructor.
	 *
	 * Calls the below PHP5 style constructor.
	 *
	 * @since 1.0
	 * @return none
	 */
	function AjaxPluginHelperAjax() {
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
		ini_set('display_errors', false);
		add_action('wp_ajax_ajaxpluginupdate', array(&$this, 'update'));
		add_action('wp_ajax_ajaxpluginactivate', array(&$this, 'activate'));
		add_action('wp_ajax_ajaxpluginispluginactive', array(&$this, 'is_plugin_active'));
		add_action('wp_ajax_ajaxplugindeactivate', array(&$this, 'deactivate'));
		add_action('wp_ajax_ajaxpluginsubsubsub', array(&$this, 'subsubsub'));
		add_action('wp_ajax_ajaxplugincounts', array(&$this, 'counts'));
		add_action('wp_ajax_ajaxpluginadminnotices', array(&$this, 'get_admin_notices'));
		add_action('wp_ajax_ajaxpluginadminmenu', array(&$this, 'get_admin_menu'));
		add_action('wp_ajax_ajaxpluginactivationerror', array(&$this, 'get_activation_error'));
		add_action('wp_ajax_ajaxplugindelete', array(&$this, 'delete'));
	}

	/**
	 * Returns a JSON representation of a value
	 *
	 * Uses the JSON class included with tinymce if json_encode is not present
	 *
	 * @since 1.0
	 * @param mixed $value value to retrieve JSON representation of
	 * @return string JSON representation of value
	 */
	function json_encode($value) {
		if ( function_exists('json_encode') ) {
			return json_encode($value);
		} else {
			include(ABSPATH . WPINC . '/js/tinymce/plugins/spellchecker/classes/utils/JSON.php');
			$json = new Moxiecode_JSON();
			return $json->encode($value);
		}
	}

	/**
	 * Action hook callback for API to deactivate a plugin
	 *
	 * @since 1.0
	 * @return none
	 */
	function deactivate() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			$active_plugins = get_option('active_plugins');
			$plugin = esc_attr($_GET['plugin']);
			if ( in_array($plugin, $active_plugins) ) {
				deactivate_plugins($plugin);
			}
			if ( ! is_plugin_active($plugin) ) {
				echo $this->json_encode(array('response' => 0, 'plugin' => $plugin));
			} else {
				echo $this->json_encode(array('response' => 1, 'plugin' => $plugin));
			}
		}
		die();
	}

	/**
	 * Action hook callback for API to activate a plugin.
	 *
	 * @since 1.0
	 * @return none
	 */
	function activate() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			$active_plugins = get_option('active_plugins');
			$plugin = esc_attr($_GET['plugin']);
			if ( ! in_array($plugin, $active_plugins) ) {
				// Output buffer to supress errors on activation
				//ob_start();
				activate_plugin($plugin);
				//ob_end_clean();
			}
		}
		die();
	}

	/**
	 * Action hook callback for API to check if a plugin is active.
	 *
	 * @since 1.1
	 * @return none
	 */
	function get_activation_error() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			if ( ! WP_DEBUG ) {
				if ( defined('E_RECOVERABLE_ERROR') )
					error_reporting(E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR);
				else
					error_reporting(E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING);
			}
			ini_set('display_errors', true); //Ensure that Fatal errors are displayed.
			$plugin = esc_attr($_GET['plugin']);
			include(WP_PLUGIN_DIR . '/' . $plugin);
			do_action('activate_' . $plugin);
		}
		die();
	}

	/**
	 * Action hook callback for API to check if a plugin is active.
	 *
	 * @since 1.1
	 * @return none
	 */
	function is_plugin_active() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			$plugin = esc_attr($_GET['plugin']);
			if ( ! is_plugin_active($plugin) ) {
				echo $this->json_encode(array('response' => 1, 'plugin' => $plugin));
			} else {
				echo $this->json_encode(array('response' => 0, 'plugin' => $plugin));
			}

		}
		die();
	}

	/**
	 * Action hook callback for API to delete a plugin.
	 *
	 * @since 1.0
	 * @return none
	 */
	function delete() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce'])) {
			$plugin = esc_attr($_GET['plugin']);
			if ( ! is_plugin_active($plugin) ) {
				$delete_result = delete_plugins(array($plugin));
				if ( $delete_result === true ) {
					echo $this->json_encode(array('response' => 0, 'plugin' => $plugin));
				} else {
					echo $this->json_encode(array('response' => 1, 'plugin' => $plugin));
				}
			}
		}
		die();
	}

	/**
	 * Action hook callback for API to retrieve the number of plugins requiring updates.
	 *
	 * This function will echo the number of plugins requiring updates.
	 *
	 * @since 1.0
	 * @return none
	 */
	function counts() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			wp_update_plugins();
			$update_plugins = get_transient('update_plugins');
			$update_count = 0;
			if ( !empty($update_plugins->response) )
				$update_count = count($update_plugins->response);
			echo $update_count;
		}
		die();
	}

	/**
	 * Action hook callback to check if there are admin notices.
	 *
	 * @since 1.1
	 * @return none
	 */
	function get_admin_notices() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			do_action('admin_notices');
		}
		die();
	}

	/**
	 * Action hook callback to update the admin menu.
	 *
	 * @since 1.1
	 * @return none
	 */
	function get_admin_menu() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			global $wp_taxonomies, $pagenow, $menu, $submenu, $_wp_menu_nopriv, $_wp_submenu_nopriv;
			global $plugin_page, $_registered_pages, $parent_file, $submenu_file;
			$pagenow = $submenu_file = $parent_file = 'plugins.php';
			require(ABSPATH . 'wp-admin/menu.php');
			require(ABSPATH . 'wp-admin/menu-header.php');
		}
		die();
	}

	/**
	 * Action hook callback for API to update the status links at the top of the 
	 * admin plugins.php page.
	 *
	 * Code taken from http://core.trac.wordpress.org/browser/tags/2.8.2/wp-admin/plugins.php
	 *
	 * @since 1.0
	 * @see http://core.trac.wordpress.org/browser/tags/2.8.2/wp-admin/plugins.php
	 * @return none
	 */
	function subsubsub() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			$default_status = 'all';
			$status = isset($_REQUEST['plugin_status']) ? $_REQUEST['plugin_status'] : $default_status;
			if ( !in_array($status, array('all', 'active', 'inactive', 'recent', 'upgrade', 'search')) )
				$status = 'all';

			$all_plugins = get_plugins();
			$search_plugins = array();
			$active_plugins = array();
			$inactive_plugins = array();
			$recent_plugins = array();
			$recently_activated = get_option('recently_activated', array());
			$upgrade_plugins = array();

			set_transient( 'plugin_slugs', array_keys($all_plugins), 86400 );

			// Clean out any plugins which were deactivated over a week ago.
			foreach ( $recently_activated as $key => $time )
				if ( $time + (7*24*60*60) < time() ) //1 week
					unset($recently_activated[ $key ]);
			if ( $recently_activated != get_option('recently_activated') ) //If array changed, update it.
				update_option('recently_activated', $recently_activated);
			$current = get_transient( 'update_plugins' );

			foreach ( (array)$all_plugins as $plugin_file => $plugin_data) {

				//Translate, Apply Markup, Sanitize HTML
				$plugin_data = _get_plugin_data_markup_translate($plugin_file, $plugin_data, false, true);
				$all_plugins[ $plugin_file ] = $plugin_data;

				//Filter into individual sections
				if ( is_plugin_active($plugin_file) ) {
					$active_plugins[ $plugin_file ] = $plugin_data;
				} else {
					if ( isset( $recently_activated[ $plugin_file ] ) ) // Was the plugin recently activated?
						$recent_plugins[ $plugin_file ] = $plugin_data;
					$inactive_plugins[ $plugin_file ] = $plugin_data;
				}

				if ( isset( $current->response[ $plugin_file ] ) )
					$upgrade_plugins[ $plugin_file ] = $plugin_data;
			}

			$total_all_plugins = count($all_plugins);
			$total_inactive_plugins = count($inactive_plugins);
			$total_active_plugins = count($active_plugins);
			$total_recent_plugins = count($recent_plugins);
			$total_upgrade_plugins = count($upgrade_plugins);

			$status_links = array();
			$class = ( 'all' == $status ) ? ' class="current"' : '';
			$status_links[] = "<li><a href='plugins.php?plugin_status=all' $class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_all_plugins, 'plugins', 'ajax-plugin-helper' ), number_format_i18n( $total_all_plugins ) ) . '</a>';
			if ( ! empty($active_plugins) ) {
				$class = ( 'active' == $status ) ? ' class="current"' : '';
				$status_links[] = "<li><a href='plugins.php?plugin_status=active' $class>" . sprintf( _n( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $total_active_plugins, 'ajax-plugin-helper' ), number_format_i18n( $total_active_plugins ) ) . '</a>';
			}
			if ( ! empty($recent_plugins) ) {
				$class = ( 'recent' == $status ) ? ' class="current"' : '';
				$status_links[] = "<li><a href='plugins.php?plugin_status=recent' $class>" . sprintf( _n( 'Recently Active <span class="count">(%s)</span>', 'Recently Active <span class="count">(%s)</span>', $total_recent_plugins, 'ajax-plugin-helper' ), number_format_i18n( $total_recent_plugins ) ) . '</a>';
			}
			if ( ! empty($inactive_plugins) ) {
				$class = ( 'inactive' == $status ) ? ' class="current"' : '';
				$status_links[] = "<li><a href='plugins.php?plugin_status=inactive' $class>" . sprintf( _n( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', $total_inactive_plugins, 'ajax-plugin-helper' ), number_format_i18n( $total_inactive_plugins ) ) . '</a>';
			}
			if ( ! empty($upgrade_plugins) ) {
				$class = ( 'upgrade' == $status ) ? ' class="current"' : '';
				$status_links[] = "<li><a href='plugins.php?plugin_status=upgrade' $class>" . sprintf( _n( 'Upgrade Available <span class="count">(%s)</span>', 'Upgrade Available <span class="count">(%s)</span>', $total_upgrade_plugins, 'ajax-plugin-helper' ), number_format_i18n( $total_upgrade_plugins ) ) . '</a>';
			}
			if ( ! empty($search_plugins) ) {
				$class = ( 'search' == $status ) ? ' class="current"' : '';
				$term = isset($_REQUEST['s']) ? urlencode(stripslashes($_REQUEST['s'])) : '';
				$status_links[] = "<li><a href='plugins.php?s=$term' $class>" . sprintf( _n( 'Search Results <span class="count">(%s)</span>', 'Search Results <span class="count">(%s)</span>', $total_search_plugins, 'ajax-plugin-helper' ), number_format_i18n( $total_search_plugins ) ) . '</a>';
			}
			echo implode( " |</li>\n", $status_links ) . '</li>';
			unset( $status_links );
		}
		die();
	}

	/**
	 * Action hook callback for API to update a plugin.
	 *
	 * @since 1.0
	 * @return none
	 */
	function update() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce'])) {
			$active_plugins = get_option('active_plugins');
			$plugin = esc_attr($_GET['plugin']);
			// Output buffer the update to suppress the echoes it generates
			ob_start();
			include(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
			include('class-extends.php');
			$upgrader = new Plugin_Upgrader(new AjaxPluginHelper_Plugin_Installer_Skin());
			$upgrader->upgrade($plugin);
			$output = ob_get_contents();
			ob_end_clean();
			// Remove tags from the output that we don't want
			echo preg_replace('%</?(div|h2|br)([^>]+)?>%i', '', $output);
			// Check if the was active before the update and that the update did not fail.
			if ( in_array($plugin, $active_plugins) && ! stristr($output, 'Failed') ) {
				echo '<p>Attempting plugin reactivation.</p>' . "\n";
				echo '<p class="' . str_replace(array('/','.'), '-', $plugin) . '-autoactivate"><img src="' . admin_url('images/wpspin_light.gif') . '" alt="' . __('Loading...', 'ajax-plugin-helper') . '" /></p>' . "\n";
?>
<script type="text/javascript">
/* <![CDATA[ */
	var ajaxpluginautoactivate = function(data) {
		var baseclass = '.' + data.plugin.replace('/','-').replace('.','-');
		if (data.response == 0) {
			var message = 'Plugin reactivated successfully.';
		} else {
			var message = 'Plugin could not be reactivated successfully.';
		}
		jQuery(baseclass + '-autoactivate').html(message);
	}
	jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'ajaxpluginactivate', plugin: "<?php echo $plugin; ?>", _wpnonce: '<?php echo wp_create_nonce(); ?>'}, function() {
		jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'ajaxpluginispluginactive', plugin: "<?php echo $plugin; ?>", _wpnonce: '<?php echo wp_create_nonce(); ?>'}, ajaxpluginautoactivate, 'json')
	});
/* ]]> */
</script>
<?php
			} else {
				echo '<p>Plugin reactivation not attempted.</p>';
			}
?>
<script type="text/javascript">
/* <![CDATA[ */
	var plugin = jQuery('.<?php echo str_replace(array('/','.'), '-', $plugin); ?>-update');
	if (jQuery(plugin).quadParent().is('tr')) {
		jQuery(plugin).quadParent().removeClass('disabled');
	} else {
		jQuery(plugin).triParent().prev().removeClass('disabled');
	}
	jQuery(plugin).parent().prev().html(jQuery(plugin).parent().prev().html().replace('|', ''));
	jQuery(plugin).parent().remove();
	reloadcounts();
/* ]]> */
</script>
<?php
		}
		die();
	}


}
