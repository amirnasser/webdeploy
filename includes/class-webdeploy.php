<?php

defined('ABSPATH') || exit;

class WebDeploy
{
	private static $_instance = null;
	public $admin = null;
	public $settings = null;
	public $ajax = null;
	public $rest = null;
	public $_version;
	public $_token;
	public $file;
	public $dir;
	public $assets_dir;
	public $assets_url;
	public $script_suffix;

	public static function instance($file = '', $version = '1.0.0')
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self($file, $version);
		}

		return self::$_instance;
	}

	function __construct($file = '', $version = '1.0.0')
	{
		$this->_version = $version;
		$this->_token   = 'web-deploy';

		$this->file       = $file;
		$this->dir        = dirname($this->file);
		$this->assets_dir = trailingslashit($this->dir) . 'assets';
		$this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));
		$this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 10);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_myvar'), 9);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 10);
		
		if (is_admin()) {
			$this->admin = new Webdeploy_Admin();
		}

		// Load admin JS & CSS.
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);

		add_action('wp_dashboard_setup', function () {
			$user = wp_get_current_user();
			if (is_admin() && in_array('administrator', $user->roles)) {
				wp_add_dashboard_widget('deploy_upload', 'Web Deploy ('.$this->_version.')', function () {
					$values = array(
						"ps" => (int)ini_get("post_max_size") . " MB",
						"uploadmaxfilesize" => ini_get("upload_max_filesize"),
						"backups" => WDUtilities::GetListOfBackups(10),
						"siteid" => get_current_blog_id()
					);
					echo WDUtilities::Render(self::$_instance, "deploy_upload.twig", $values);
				});
			}
		});

		register_activation_hook( $this->file, function(){			
			$apikey = get_option("wpwd_apikey");
			if(strlen($apikey)==0)
			{
				$newpassword = WDUtilities::GeneratePassword(24);
				update_option("wpwd_apikey", $newpassword);
			}
		});
	}

	public function enqueue_myvar()
	{
		wp_localize_script("jquery", 'wn', array('ajax_url' => admin_url('admin-ajax.php')));
	}

	public function admin_enqueue_styles($hook = '')
	{
		wp_register_style($this->_token . '-admin', esc_url($this->assets_url) . 'css/admin' . $this->script_suffix . '.css', array(), $this->_version);
		wp_enqueue_style($this->_token . '-admin');
	}

	public function admin_enqueue_scripts($hook = '')
	{
		wp_register_script($this->_token . '-jqvalidate', esc_url($this->assets_url) . 'js/jquery.validate' . $this->script_suffix . '.js', array('jquery'), $this->_version, true);
		wp_register_script($this->_token . '-jqvalidate-uv', esc_url($this->assets_url) . 'js/jquery.validate.unobtrusive' . $this->script_suffix . '.js', array('jquery'), $this->_version, true);
		wp_register_script($this->_token . '-main', esc_url($this->assets_url) . 'js/main' . $this->script_suffix . '.js', array('jquery'), $this->_version, true);

		wp_enqueue_script($this->_token . '-jqvalidate');
		wp_enqueue_script($this->_token . '-jqvalidate-uv');
		wp_enqueue_script($this->_token . '-main');
	}
}