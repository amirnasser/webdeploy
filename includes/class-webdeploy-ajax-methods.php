<?php

defined('ABSPATH') || exit;

class AjaxMethods
{

    private static $_instance = null;
    public $parent = null;
    
	public static function instance($parent)
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self($parent);
		}

		return self::$_instance;
	}

    function __construct($parent)
	{
        $this->parent = $parent;
		$this->ajaxmethods();
    }

	public function ajaxmethods()
	{
		$methods = array();

		$adminmethods = array("unzip", "detail", "blist", "list", "revert");

		foreach ($methods as $method) {
			add_action('wp_ajax_nopriv_' . $method, array($this, "ajax_".$method));
			add_action('wp_ajax_' . $method, array($this, "ajax_".$method));
		}

		foreach ($adminmethods as $method) {
			add_action('wp_ajax_' . $method, array($this, "ajax_".$method));
		}
	}   
    
	public function ajax_unzip()
	{
		ini_set("display_errors", "0");
		$blogid = get_current_blog_id();

		$file = $_FILES["wnp_file"]["tmp_name"];
		
		try {
			wp_send_json_success(Utilities::Unzip($file));
		} catch (Exception $exp) {
			wp_send_json_error(array("message" => $exp->getMessage()));
		}
	}   
    
	public function ajax_list()
	{
		$values = array(
			"backups" => Utilities::GetListOfBackups()
		);
		echo Utilities::Render(self::$_instance->parent, "files.twig", $values);
	}  
    
	public function ajax_detail()
	{
		$filename = $_REQUEST["filename"];
		wp_send_json_success(Utilities::GetDetail($filename));
	}

	public function ajax_blist()
	{
		return wp_send_json_success(Utilities::GetListOfBackups());
	}

	public function ajax_revert()
	{
		Utilities::Revert($_REQUEST["filename"]);
	}
    
}