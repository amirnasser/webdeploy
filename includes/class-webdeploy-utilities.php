<?php
class Utilities
{
	public static function Render($instance, $templateName, $values)
	{
		$loader = new \Twig\Loader\FilesystemLoader($instance->dir . '/templates');
		$twig = new \Twig\Environment($loader, array());
		return $twig->render($templateName, $values);
	}

	public static function GetListOfBackups($limit = 10)
	{
		$blogid = get_current_blog_id();
		$backup = ABSPATH . "wp-content/uploads/backup/$blogid/";
		if (file_exists($backup)) {
			$backups = array_filter(
				array_values(array_diff(scandir($backup), array('..', '.'))),
				function ($item) {
					return strpos($item, "_deployed.zip") > 0;
				}
			);
			arsort($backups);


			$backups = array_slice($backups, 0, $limit);
			$result = array();

			foreach ($backups as $k => $v) {
				array_push($result, array(
					"backup" => file_exists($backup . str_replace("_deployed.zip", ".zip", $v)) ? str_replace("_deployed.zip", ".zip", $v) : null,
					"package" => file_exists($backup . $v) ? $v : null
				));
			}

			return $result;
		} else
			return array();
	}
	
	public static function Unzip($file)
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

	public static function Revert($file)
	{
		ini_set("display_errors", "0");
		$blogid = get_current_blog_id();
		$path = ABSPATH . "wp-content/uploads/backup/$blogid/";
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

		try {
			wp_send_json_success(Utilities::Unzip($path . $file));
		} catch (Exception $exp) {
			wp_send_json_error(array("message" => $exp->getMessage()));
		}
	}	

	public static function GetDetail($filename)
	{
		$blogid = get_current_blog_id();
		$zipFile = new \PhpZip\ZipFile();
		$backuppath = ABSPATH . "wp-content/uploads/backup/$blogid/";
		$zipFile->openFile($backuppath . $filename);
		$list = $zipFile->getListFiles();
		$result = array_values(array_filter($list, function ($item) {
			return !str_ends_with($item, "/");
		}));
		return $result;
	}		

	// public static function Unzip($file)
	// {
	// 	$path = ABSPATH;
	// 	$blogid = get_current_blog_id();
	// 	$zipFile = new \PhpZip\ZipFile();
	// 	$fz = $zipFile->openfile($file);
	// 	$dt = date("Ymd-his");
		
	// 	//create backup directory
	// 	$backup = ABSPATH . "wp-content/uploads/backup/$blogid/";
	// 	if (!file_exists($backup))
	// 		mkdir($backup, 0777, true);

	// 	//backup
	// 	$list = $fz->getListFiles();
	// 	$zipBackup = new \PhpZip\ZipFile();

	// 	foreach ($list as $f) {
	// 		if (file_exists(ABSPATH . $f))
	// 			$zipBackup->addFile(ABSPATH . $f, $f);
	// 	}

	// 	if (count($zipBackup->getListFiles()) > 0) {
	// 		$backupfile = $dt . ".zip";
	// 		$zipBackup->saveAsFile($backup . "/" . $dt . ".zip");
	// 	}
	// 	//end backup
	// 	copy($file, $backup . "/" . $dt . "_deployed.zip");
	// 	$fz->extractTo($path);

	// 	return array(
	// 		"message" => "Deployed successfully",
	// 		"files" => array_values(array_filter($list, function ($item) {
	// 			return !str_ends_with($item, "/");
	// 		}))
	// 	);
	// }	
	
    public static function GeneratePassword($num)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $num; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
}
