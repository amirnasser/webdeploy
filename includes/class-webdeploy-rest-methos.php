<?php

defined('ABSPATH') || exit;

class RestMethods
{

    private static $_instance = null;
	public $parent;

	public static function instance($_parent)
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self($_parent);
		}
		
		return self::$_instance;
	}

    function __construct($_parent)
	{

		$this->parent = $_parent;
		$this->rest_api_method();
    }

	public function rest_api_method()
	{
		add_action('rest_api_init', function () {
			register_rest_route(
				'webdeploy/v1',
				'/deploy/(?P<apikey>.+)',
				array(
					'methods' => 'POST',
					'callback' => function (WP_REST_Request $request) {
						return $this->rest_deploy($request);
					}
				)
			);

			register_rest_route(
				'webdeploy/v1',
				'/packages/(?P<limit>[0-9]{1,3}+)/(?P<apikey>.+)',
				array(
					'methods' => 'GET',
					'callback' => function (WP_REST_Request $request) {
						return $this->rest_packages($request);
					}
				)
			);

			register_rest_route(
				'webdeploy/v1',
				'/packages/(?P<apikey>.+)',
				array(
					'methods' => 'GET',
					'callback' => function (WP_REST_Request $request) {
						return $this->rest_packages($request);
					}
				)
			);

			register_rest_route(
				'webdeploy/v1',
				'/details/(?P<file>.+)/(?P<apikey>.+)',
				array(
					'methods' => 'GET',
					'callback' => function (WP_REST_Request $request) {
						return $this->rest_packagedetail($request);
					}
				)
			);

			register_rest_route(
				'webdeploy/v1',
				'/delete/(?P<file>.+)/(?P<apikey>.+)',
				array(
					'methods' => 'GET',
					'callback' => function (WP_REST_Request $request) {
						return $this->rest_delete($request);
					}
				)
			);

			register_rest_route(
				'webdeploy/v1',
				'/redeploy/(?P<file>.+)/(?P<apikey>.+)',
				array(
					'methods' => 'GET',
					'callback' => function (WP_REST_Request $request) {
						return $this->rest_redeploy($request);
					}
				)
			);

			register_rest_route(
				'webdeploy/v1',
				'/revert/(?P<file>.+)/(?P<apikey>.+)',
				array(
					'methods' => 'GET',
					'callback' => function (WP_REST_Request $request) {
						return $this->rest_revert($request);
					}
				)
			);

			register_rest_route(
				'webdeploy/v1',
				'/ver/(?P<apikey>.+)',
				array(
					'methods' => 'GET',
					'callback' => function (WP_REST_Request $request) {
						return $this->rest_ver($request);
					}
				)
			);	
			
			register_rest_route(
				'webdeploy/v1',
				'/generatepassword',
				array(
					'methods' => 'GET',
					'callback' => function (WP_REST_Request $request) {
						return $this->rest_generatepassword($request);
					}
				)
			);
		});
	}

	private function rest_deploy(WP_REST_Request $request)
	{
		ini_set("display_errors", "0");
		$path = ABSPATH;
		$blogid = get_current_blog_id();

		if (get_option("wpwd_apikey") != $request->get_param("apikey"))
			wp_send_json_error(array("message" => "Api Key is wrong"));

		$files = $request->get_file_params();

		foreach ($files as $k => $v) {
			$file = $v["tmp_name"];
			try {
				wp_send_json_success($this->_unzip($file));
			} catch (Exception $exp) {
				wp_send_json_error(array("message" => $exp->getMessage()));
			}
			break;
		}
	}

	private function rest_packages(WP_REST_Request $request)
	{
		ini_set("display_errors", "0");

		if (get_option("wpwd_apikey") != $request->get_param("apikey"))
			wp_send_json_error(array("message" => "Api Key is wrong"));
		$limit = $request->get_param("limit") ?? 100;
		wp_send_json_success(Utilities::GetListOfBackups($limit));
	}	

	private function rest_delete(WP_REST_Request $request)
	{
		try {
			ini_set("display_errors", "0");
			$blogid = get_current_blog_id();

			if (get_option("wpwd_apikey") != $request->get_param("apikey"))
				wp_send_json_error(array("message" => "Api Key is wrong"));

			$file = $request->get_param("file");
			$backup = ABSPATH . "wp-content/uploads/backup/$blogid/";
			$zipfile = $backup . $file . ".zip";
			if (file_exists($zipfile))
				unlink($zipfile);

			$packagefile = $backup . $file . "_deployed.zip";
			if (file_exists($packagefile))
				unlink($packagefile);

			wp_send_json_success();
		} catch (Exception $exp) {
			wp_send_json_error(array("message" => $exp->getMessage()));
		}
	}

	public function rest_packagedetail(WP_REST_Request $request)
	{
		ini_set("display_errors", "0");
		$blogid = get_current_blog_id();

		if (get_option("wpwd_apikey") != $request->get_param("apikey"))
			wp_send_json_error(array("message" => "Api Key is wrong"));

		$file = $request->get_param("file");
		wp_send_json_success(Utilities::GetDetail($file));
	}

	public function rest_revert(WP_REST_Request $request)
	{
		ini_set("display_errors", "0");
		$blogid = get_current_blog_id();

		if (get_option("wpwd_apikey") != $request->get_param("apikey"))
			wp_send_json_error(array("message" => "Api Key is wrong"));

		$file = $request->get_param("file");
		Utilities::Revert($file);
	}

	private function rest_ver(WP_REST_Request $request)
	{		
		wp_send_json_success($this->parent->_version);
	}

	public function rest_redeploy(WP_REST_Request $request)
	{
		ini_set("display_errors", "0");
		$blogid = get_current_blog_id();
		$path = ABSPATH . "wp-content/uploads/backup/$blogid/";
		$blogid = get_current_blog_id();

		if (get_option("wpwd_apikey") != $request->get_param("apikey"))
			wp_send_json_error(array("message" => "Api Key is wrong"));

		$file = $request->get_param("file");
		try {
			wp_send_json_success($this->_unzip($path . $file));
		} catch (Exception $exp) {
			wp_send_json_error(array("message" => $exp->getMessage()));
		}
	}	


	private function _unzip($file)
	{
		$path = ABSPATH;
		$blogid = get_current_blog_id();
		$zipFile = new \PhpZip\ZipFile();
		$fz = $zipFile->openfile($file);
		$dt = date("Ymd-his");
		
		//create backup directory
		$backup = ABSPATH . "wp-content/uploads/backup/$blogid/";
		if (!file_exists($backup))
			mkdir($backup, 0777, true);

		//backup
		$list = $fz->getListFiles();
		$zipBackup = new \PhpZip\ZipFile();

		foreach ($list as $f) {
			if (file_exists(ABSPATH . $f))
				$zipBackup->addFile(ABSPATH . $f, $f);
		}

		if (count($zipBackup->getListFiles()) > 0) {
			$backupfile = $dt . ".zip";
			$zipBackup->saveAsFile($backup . "/" . $dt . ".zip");
		}
		//end backup
		copy($file, $backup . "/" . $dt . "_deployed.zip");
		$fz->extractTo($path);

		return array(
			"message" => "Deployed successfully",
			"files" => array_values(array_filter($list, function ($item) {
				return !str_ends_with($item, "/");
			}))
		);
	}	

	private function rest_generatepassword()
	{
		
	}

}