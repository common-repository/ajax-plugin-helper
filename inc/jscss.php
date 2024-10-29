<?php
class AjaxPluginHelperJsCss extends AjaxPluginHelper {

	/**
	 * PHP4 style constructor.
	 *
	 * Calls the below PHP5 style constructor.
	 *
	 * @since 1.0
	 * @return none
	 */
	function AjaxPluginHelperJsCss() {
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
		add_action('admin_head-plugins.php', array(&$this, 'admin_jquery'));
		add_action('admin_head-plugins.php', array(&$this, 'admin_css'));
		add_action('admin_footer-plugins.php', array(&$this, 'admin_js'));
	}

	/**
	 * Enqueue jQuery so this plugin can use it
	 *
	 * @since 1.0
	 * @return none
	 */
	function admin_jquery() {
		wp_enqueue_script('jquery');
	}

	/**
	 * Output CSS required by this plugin to head
	 *
	 * @since 1.1
	 * @return none
	 */
	function admin_css() {
?>
<style type="text/css">
	.disabled a { color: grey ! important; text-decoration: none ! important; cursor: text ! important; }
</style>
<?php
	}

	/**
	 * Echo the JS required for this plugin to work
	 *
	 * @since 1.0
	 * @return none
	 */
	function admin_js() {
?>
<script type="text/javascript">
/* <![CDATA[ */
	(function($) {
		$.fn.quadParent = function() {
			return this.parent().parent().parent().parent();
		}
		$.fn.triParent = function() {
			return this.parent().parent().parent();
		}
	})(jQuery);
	var reloadadminnotices = function() {
		jQuery('div.updated, div.error').remove();
		jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', 'action=ajaxpluginadminnotices&_wpnonce=<?php echo wp_create_nonce(); ?>', function(notices) {
			jQuery('div.wrap h2:first').after(notices);
		});
	}
	var reloadadminmenu = function() {
		jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', 'action=ajaxpluginadminmenu&_wpnonce=<?php echo wp_create_nonce(); ?>', function(menu) {
			jQuery('#adminmenu').replaceWith(menu);
			adminMenu.init();
		});
	}
	var reloadcounts = function() {
		jQuery('.subsubsub').load('<?php echo admin_url('admin-ajax.php'); ?>', 'action=ajaxpluginsubsubsub&_wpnonce=<?php echo wp_create_nonce(); ?>&plugin_status=' + jQuery('input[name="plugin_status"]').val());
		jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'ajaxplugincounts', _wpnonce: '<?php echo wp_create_nonce(); ?>'}, function(count) {
			updateplugins = jQuery('.update-plugins');
			jQuery(updateplugins).removeClass();
			jQuery(updateplugins).addClass('update-plugins count-' + count);
			jQuery('.plugin-count').html(count);
			if (count == 0) {
				jQuery('.ajaxpluginupdateall').remove();
			}
		}, 'text');
		reloadadminnotices();
		reloadadminmenu();
	}
	var ajaxpluginactivate = function(data) {
		var baseclass = '.' + data.plugin.replace('/','-').replace('.','-');
		jQuery(baseclass + '-spin').addClass('hidden');
		if (data.response == 0) {
			jQuery(baseclass + '-deactivate').removeClass('hidden');
			jQuery(baseclass + '-deactivate').quadParent().removeClass('inactive');
			jQuery(baseclass + '-deactivate').quadParent().addClass('active');
			jQuery(baseclass + '-deactivate').quadParent().prev().removeClass('inactive');
			jQuery(baseclass + '-deactivate').quadParent().prev().addClass('active');
			jQuery(baseclass + '-delete').addClass('hidden');
			<?php if ( ! is_multisite() ) : ?>
			jQuery(baseclass + '-delete').parent().prev().html(jQuery(baseclass + '-delete').parent().prev().html().replace('|',''));
			<?php endif; ?>
			reloadcounts();
		} else {
			jQuery(baseclass + '-activate').removeClass('hidden');
			jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', 'action=ajaxpluginactivationerror&plugin=' + data.plugin + '&_wpnonce=<?php echo wp_create_nonce(); ?>', function(error) {
				reloadadminnotices();
				jQuery('div.wrap h2:first').after('<div class="updated fade"><p><strong>' + data.plugin + '</strong> <?php _e('could not be activated because it triggered a <strong>fatal error</strong>'); ?>.</p><p>' + error + '</p></div>');
			});
		}
		jQuery(baseclass + '-deactivate').quadParent().removeClass('disabled');
	}
	var ajaxplugindeactivate = function(data) {
		var baseclass = '.' + data.plugin.replace('/','-').replace('.','-');
		jQuery(baseclass + '-spin').addClass('hidden');
		if (data.response == 0) {
			jQuery(baseclass + '-activate').removeClass('hidden');
			jQuery(baseclass + '-activate').quadParent().removeClass('active');
			jQuery(baseclass + '-activate').quadParent().addClass('inactive');
			jQuery(baseclass + '-activate').quadParent().prev().removeClass('active');
			jQuery(baseclass + '-activate').quadParent().prev().addClass('inactive');
			jQuery(baseclass + '-delete').parent().prev().html(jQuery(baseclass + '-delete').parent().prev().html() + ' | ');
			jQuery(baseclass + '-delete').removeClass('hidden');
			reloadcounts();
		} else {
			jQuery(baseclass + '-deactivate').removeClass('hidden');
		}
		jQuery(baseclass + '-activate').quadParent().removeClass('disabled');
	}
	var ajaxplugindelete = function(data) {
		var baseclass = '.' + data.plugin.replace('/','-').replace('.','-');
		jQuery(baseclass + '-spin-del').addClass('hidden');
		if (data.response == 0) {
			jQuery(baseclass + '-delete').quadParent().prev().remove();
			if (jQuery(baseclass + '-delete').quadParent().next().next().attr('class') == 'plugin-update-tr') {
				jQuery(baseclass + '-delete').quadParent().next().next().remove();
				jQuery(baseclass + '-delete').quadParent().next().remove();
			} else if (jQuery(baseclass + '-delete').quadParent().next().attr('class') == 'plugin-update-tr') {
				jQuery(baseclass + '-delete').quadParent().next().remove();
			} else if (jQuery(baseclass + '-delete').quadParent().next().attr('class').length == 0) {
				jQuery(baseclass + '-delete').quadParent().next().remove();
			}
			jQuery(baseclass + '-delete').quadParent().remove();
			reloadcounts();
		} else {
			jQuery(baseclass + '-delete').removeClass('hidden');
			jQuery(baseclass + '-delete').quadParent().removeClass('disabled');
		}
	}
	jQuery(document).ready(function() {
<?php
		$update_plugins = get_site_transient( 'update_plugins' );
		$update_count = 0;
		if ( !empty($update_plugins->response) )
			$update_count = count( $update_plugins->response );
		if ( $update_count > 0 ) :
?>
		jQuery('.actions').append('<input type="submit" value="<?php _e('Ajax Upgrade All', 'ajax-plugin-helper'); ?>" class="button-secondary ajaxpluginupdateall" />');
<?php endif; ?>
		jQuery('.disabled a').live('click', function() {
			return false;
		});
		jQuery('.ajaxplugindelete:hidden').each(function() {
			if (jQuery(this).parent().prev().html() != null) {
				jQuery(this).parent().prev().html(jQuery(this).parent().prev().html().replace('|',''));
			}
		});
		jQuery('.ajaxpluginupdateall').click(function() {
			jQuery('.ajaxpluginupdate').each(function() {
				if (jQuery(this).quadParent().next().children().children().attr('class') == 'update-message' ) {
					updatetr = jQuery(this).quadParent().next().children().children();
				} else {
					updatetr = jQuery(this).quadParent().next().next().children().children();
				}
				jQuery(this).quadParent().addClass('disabled');
				jQuery(updatetr).html('<img src="<?php echo admin_url('images/wpspin_light.gif'); ?>" alt="<?php _e('Loading...', 'ajax-plugin-helper'); ?>" />');
				jQuery(updatetr).load('<?php echo admin_url('admin-ajax.php'); ?>', 'action=ajaxpluginupdate&plugin=' + jQuery(this).attr('rel') + '&_wpnonce=<?php echo wp_create_nonce(); ?>');
				jQuery('.ajaxpluginupdateall').remove();
			});
			return false;
		});
		jQuery('.ajaxpluginupdate').click(function() {
			if (jQuery(this).quadParent().next().children().children().attr('class') == 'update-message' ) {
				updatetr = jQuery(this).quadParent().next().children().children();
			} else if (jQuery(this).quadParent().next().next().children().children().attr('class') == 'update-message' ) {
				updatetr = jQuery(this).quadParent().next().next().children().children();
			} else {
				updatetr = jQuery(this).parent();
			}
			if (jQuery(this).quadParent().is('tr')) {
				jQuery(this).quadParent().addClass('disabled');
			} else {
				jQuery(this).triParent().prev().addClass('disabled');
			}
			jQuery(updatetr).html('<img src="<?php echo admin_url('images/wpspin_light.gif'); ?>" alt="<?php _e('Loading...', 'ajax-plugin-helper'); ?>" />');
			jQuery(updatetr).load('<?php echo admin_url('admin-ajax.php'); ?>', 'action=ajaxpluginupdate&plugin=' + jQuery(this).attr('rel') + '&_wpnonce=<?php echo wp_create_nonce(); ?>');
			return false;
		});
		jQuery('.ajaxpluginactivate').click(function() {
			var plugin = jQuery(this).attr('rel');
			var baseclass = '.' + plugin.replace('/','-').replace('.','-');
			jQuery(baseclass + '-activate').addClass('hidden');
			jQuery(baseclass + '-spin').removeClass('hidden');
			jQuery(this).quadParent().addClass('disabled');
			jQuery.ajax({type: 'GET', url: '<?php echo admin_url('admin-ajax.php'); ?>', data: {action: 'ajaxpluginactivate', plugin: plugin, _wpnonce: '<?php echo wp_create_nonce(); ?>'}, complete: function() {
				jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'ajaxpluginispluginactive', plugin: plugin, _wpnonce: '<?php echo wp_create_nonce(); ?>'}, ajaxpluginactivate, 'json')
			}});
			return false;
		});
		jQuery('.ajaxplugindeactivate').click(function() {
			var baseclass = '.' + jQuery(this).attr('rel').replace('/','-').replace('.','-');
			jQuery(baseclass + '-deactivate').addClass('hidden');
			jQuery(baseclass + '-spin').removeClass('hidden');
			jQuery(this).quadParent().addClass('disabled');
			jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'ajaxplugindeactivate', plugin: jQuery(this).attr('rel'), _wpnonce: '<?php echo wp_create_nonce(); ?>'}, ajaxplugindeactivate, 'json');
			return false;
		});
		jQuery('.ajaxplugindelete').click(function() {
			var baseclass = '.' + jQuery(this).attr('rel').replace('/','-').replace('.','-');
			jQuery(baseclass + '-delete').addClass('hidden');
			jQuery(baseclass + '-spin-del').removeClass('hidden');
			jQuery(this).quadParent().addClass('disabled');
			jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'ajaxplugindelete', plugin: jQuery(this).attr('rel'), _wpnonce: '<?php echo wp_create_nonce(); ?>'}, ajaxplugindelete, 'json');
			return false
		})
	});
/* ]]> */
</script>
<?php
	}

}
