<?php

 defined('ABSPATH') || exit;

 class WebDeploy
 {
	private static $_instance = null; 
	public $admin = null;
	public $settings = null;
	public $_version; 
	public $_token; 
	public $file;
	public $dir;
	public $assets_dir;
	public $assets_url;
	public $script_suffix;

	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	} 

    function __construct($file = '', $version = '1.0.0' )
    {
		$this->_version = $version;
		$this->_token   = 'web-deploy';

		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url(trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_myvar'), 9 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		
		// Load API for generic admin functions.
		if ( is_admin() ) {
			$this->admin = new Webdeploy_Admin();
		}

		// Load admin JS & CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );	

		add_action('wp_dashboard_setup', function () {
            $path = $this->dir;
            if(is_admin())
            {
				wp_add_dashboard_widget('deploy_upload','Web Deploy',function() {
					$values = Array(
						"ps"=> (int)ini_get("post_max_size")." MB",
						"uploadmaxfilesize" => ini_get("upload_max_filesize"),
						"backups"=> $this->listOfBackups()
                    );
                    echo Webneat_Utilities::Render(self::$_instance,"deploy_upload.twig",$values);
                });
            }
        });

		add_action( 'rest_api_init', function () {
			register_rest_route( 'webdeploy/v1', '/deploy/(?P<apikey>.+)', array(
			  'methods' => 'POST',
			  'callback' => function(WP_REST_Request $request){
				  return $this->deploy($request);
			  }
			  )
			);

			register_rest_route( 'webdeploy/v1', '/packages/(?P<limit>[0-9]{1,3}+)/(?P<apikey>.+)', array(
				'methods' => 'GET',
				'callback' => function(WP_REST_Request $request){
					return $this->packages($request);
				}
				)
			  );

			  register_rest_route( 'webdeploy/v1', '/packages/(?P<apikey>.+)', array(
				'methods' => 'GET',
				'callback' => function(WP_REST_Request $request){
					return $this->packages($request);
				}
				)
			  );			  

			  register_rest_route( 'webdeploy/v1', '/details/(?P<file>.+)/(?P<apikey>.+)', array(
				'methods' => 'GET',
				'callback' => function(WP_REST_Request $request){
					return $this->packagedetail($request);
				}
				)
			  );

			  register_rest_route( 'webdeploy/v1', '/details/(?P<file>.+)/(?P<apikey>.+)', array(
				'methods' => 'GET',
				'callback' => function(WP_REST_Request $request){
					return $this->packagedetail($request);
				}
				)
			  );			  

			  register_rest_route( 'webdeploy/v1', '/redeploy/(?P<file>.+)/(?P<apikey>.+)', array(
				'methods' => 'GET',
				'callback' => function(WP_REST_Request $request){
					return $this->redeploy($request);
				}
				)
			  );		

			  register_rest_route( 'webdeploy/v1', '/revert/(?P<file>.+)/(?P<apikey>.+)', array(
				'methods' => 'GET',
				'callback' => function(WP_REST_Request $request){
					return $this->rest_revert($request);
				}
				)
			  );			  
		  });		

        $this->ajaxmethods();
     }

     public function ajaxmethods()
     {
         $methods = array();

         $adminmethods = array("unzip", "detail", "blist", "list", "revert");
 
         foreach($methods as $method)
         {
             add_action( 'wp_ajax_nopriv_'.$method,array($this, $method) );
             add_action( 'wp_ajax_'.$method, array($this, $method));
         }
 
         foreach($adminmethods as $method)
         {			
             add_action( 'wp_ajax_'.$method, array($this, $method));
         }
     }

	public function enqueue_styles() {

	} 

	public function enqueue_scripts() {
        
	} 

	public function enqueue_myvar(){		
		wp_localize_script("jquery", 'wn', array('ajax_url' => admin_url( 'admin-ajax.php' )));
	}

	public function admin_enqueue_styles( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin' . $this->script_suffix . '.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );		
	} 

	public function admin_enqueue_scripts( $hook = '' ) {
		wp_register_script( $this->_token . '-jqvalidate', esc_url( $this->assets_url ) . 'js/jquery.validate' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
		wp_register_script( $this->_token . '-jqvalidate-uv', esc_url( $this->assets_url ) . 'js/jquery.validate.unobtrusive' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
		
		wp_enqueue_script( $this->_token . '-jqvalidate' );
		wp_enqueue_script( $this->_token . '-jqvalidate-uv' );	
		wp_enqueue_script( $this->_token . '-admin' );	
	}

	public function list()
	{
		$values = Array(
			"backups"=> $this->listOfBackups()
		);
		echo Webneat_Utilities::Render(self::$_instance,"files.twig",$values);
	}

	public function listOfBackups($limit=10)
	{
		$blogid = get_current_blog_id();
		$backup = ABSPATH."wp-content/uploads/backup/$blogid/";
		if(file_exists($backup))
		{
			$backups = array_filter(array_values(array_diff(scandir($backup), array('..', '.'))),
			function($item){
				return strpos($item,"_deployed.zip") > 0;
			}
			);
			arsort($backups);	

			
			$backups = array_slice($backups, 0 , $limit);
			$result = array();

			foreach($backups as $k=>$v)
			{
				array_push($result, array(
					"backup"=>file_exists($backup.str_replace("_deployed.zip", ".zip", $v)) ? str_replace("_deployed.zip", ".zip", $v) : null,
					"package"=>file_exists($backup.$v) ? $v : null
				));
			}

			return $result;
		}
		else
			return array();
	}

	private function getDetail($filename)
	{
		$blogid = get_current_blog_id();
		$zipFile = new \PhpZip\ZipFile();
		$backuppath = ABSPATH."wp-content/uploads/backup/$blogid/";
		$zipFile->openFile($backuppath.$filename);
		$list = $zipFile->getListFiles();
		$result = array_values(array_filter($list, function($item){return !str_ends_with($item, "/");}));
		return $result;
	}	

	public function detail()
	{
		$filename = $_REQUEST["filename"];
		wp_send_json_success($this->getDetail($filename));
	}

	public function blist()
	{
		return wp_send_json_success($this->listOfBackups());
	}

	private function _unzip($file)
	{
        $path = ABSPATH;
		$blogid = get_current_blog_id();
		$zipFile = new \PhpZip\ZipFile();
		$fz = $zipFile->openfile($file);
		$dt = date("Ymd-his");
		$backupfile = "";
		//create backup directory
		$backup = ABSPATH."wp-content/uploads/backup/$blogid/";
		if(!file_exists($backup))
			mkdir($backup, 0777, true);
		
		//backup
		$list = $fz->getListFiles();
		$zipBackup = new \PhpZip\ZipFile();
		
		foreach($list as $f)
		{
			if(file_exists(ABSPATH.$f))
				$zipBackup->addFile(ABSPATH.$f, $f);
		}

		if(count($zipBackup->getListFiles())>0)
		{
			$backupfile = $dt.".zip";
			$zipBackup->saveAsFile($backup."/".$dt.".zip");
		}
		//end backup
		copy($file, $backup."/".$dt."_deployed.zip");
		$fz->extractTo($path);	
		
		return array(
			"message"=>"Deployed successfully", 
			"files"=>array_values(array_filter($list, function($item){return !str_ends_with($item, "/");}))			
		);
	}

	/*
	Rest Methods
	*/

	public function deploy(WP_REST_Request $request)
    {		
		ini_set("display_errors", "0");
        $path = ABSPATH;
		$blogid = get_current_blog_id();
		
		if(get_option("wpwd_apikey")!= $request->get_param("apikey"))
			wp_send_json_error(array("message"=>"Api Key is wrong"));
		
        $files = $request->get_file_params();
		
		foreach($files as $k=>$v)
		{			
			$file = $v["tmp_name"];
			try{
				wp_send_json_success($this->_unzip($file));
			}
			catch(Exception $exp)
			{
				wp_send_json_error(array("message"=>$exp->getMessage()));
			}
			break;
		}
    }	

	private function getPackages()
	{
		$blogid = get_current_blog_id();
		$backup = ABSPATH."wp-content/uploads/backup/$blogid/";
		if(file_exists($backup))
		{
			$backups = array_filter(array_values(array_diff(scandir($backup), array('..', '.'))),
				function($item){
					return strpos($item,"_deployed.zip") > 0;
				}
			);
			arsort($backups);	

			
			$result = array();

			foreach($backups as $k=>$v)
			{
				if(file_exists($backup.$v))
				{
					array_push($result, array(
						str_replace("_deployed.zip","",$v)
					));
				}
			}

			return $result;
		}
		else
		{
			return array();
		}
	}
	public function packages(WP_REST_Request $request)
	{
		ini_set("display_errors", "0");        
		
		if(get_option("wpwd_apikey")!= $request->get_param("apikey"))
			wp_send_json_error(array("message"=>"Api Key is wrong"));
		$limit = $request->get_param("limit") ?? 100;
		wp_send_json_success($this->listOfBackups($limit));
	}

	public function delete(WP_REST_Request $request)
	{
		try
		{
			ini_set("display_errors", "0");        
			$blogid = get_current_blog_id();
			
			if(get_option("wpwd_apikey")!= $request->get_param("apikey"))
				wp_send_json_error(array("message"=>"Api Key is wrong"));

			$file = $request->get_param("file");			
			$backup = ABSPATH."wp-content/uploads/backup/$blogid/";
			$zipfile = $backup.$file.".zip";
			if(file_exists($zipfile))
				unlink($zipfile);

			$packagefile = $backup.$file."_deployed.zip";
			if(file_exists($packagefile))
				unlink($packagefile);				

			wp_send_json_success();
		}
		catch(Exception $exp)
		{
			wp_send_json_error(array("message"=>$exp->getMessage()));
		}
	}	

	public function packagedetail(WP_REST_Request $request)
	{
		ini_set("display_errors", "0");        
		$blogid = get_current_blog_id();
		
		if(get_option("wpwd_apikey")!= $request->get_param("apikey"))
			wp_send_json_error(array("message"=>"Api Key is wrong"));

		$file = $request->get_param("file");
		wp_send_json_success($this->getDetail($file));
	}

	public function rest_revert(WP_REST_Request $request)
	{
		ini_set("display_errors", "0");        
		$blogid = get_current_blog_id();
		
		if(get_option("wpwd_apikey")!= $request->get_param("apikey"))
			wp_send_json_error(array("message"=>"Api Key is wrong"));

		$file = $request->get_param("file");
		$this->_revert($file);
	}	

	public function redeploy(WP_REST_Request $request)
	{
		ini_set("display_errors", "0");
		$blogid = get_current_blog_id();
        $path = ABSPATH."wp-content/uploads/backup/$blogid/";
		$blogid = get_current_blog_id();
		
		if(get_option("wpwd_apikey")!= $request->get_param("apikey"))
			wp_send_json_error(array("message"=>"Api Key is wrong"));
			
		$file = $request->get_param("file");
		try{
			wp_send_json_success($this->_unzip($path.$file));
		}
		catch(Exception $exp)
		{
			wp_send_json_error(array("message"=>$exp->getMessage()));
		}
	}

	public function revert()
	{
		$this->_revert($_REQUEST["filename"]);
	}

	public function _revert($file)
	{
		ini_set("display_errors", "0");
		$blogid = get_current_blog_id();
        $path = ABSPATH."wp-content/uploads/backup/$blogid/";
		$blogid = get_current_blog_id();
		// $zipFile = new \PhpZip\ZipFile();
		// $fz = $zipFile->openfile($path.$file);
		// $ziplist = $fz->getListFiles();

		// $zipPackage = new \PhpZip\ZipFile();
		// $fzp = $zipPackage->openfile($path.str_replace(".zip","_deployed.zip",$file));
		// $zipPackgeList = $fzp->getListFiles();

		// $diff = array_diff($ziplist, $zipPackgeList);
		// foreach($diff as $dif)
		// {
		// 	unlink($path.$dif);
		// }

		try{
			wp_send_json_success($this->_unzip($path.$file));
		}
		catch(Exception $exp)
		{
			wp_send_json_error(array("message"=>$exp->getMessage()));
		}
	}

    public function unzip()
    {
		ini_set("display_errors", "0");
		$blogid = get_current_blog_id();
		
        $file = $_FILES["wnp_file"]["tmp_name"];
        $path = ABSPATH;
		try{
			wp_send_json_success($this->_unzip($file));
		}
		catch(Exception $exp)
		{
			wp_send_json_error(array("message"=>$exp->getMessage()));
		}
    }
 }

 class Webneat_Utilities
 {
     public static function Render($instance, $templateName, $values)
     {
         $loader = new \Twig\Loader\FilesystemLoader($instance->dir.'/templates');         
         $twig = new \Twig\Environment($loader, array());         
         return $twig->render($templateName, $values);
     }
 }

