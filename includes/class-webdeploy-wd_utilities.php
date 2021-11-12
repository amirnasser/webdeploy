<?php
class wd_utilities
{
	public static function Render($instance, $templateName, $values)
	{
		$loader = new \Twig\Loader\FilesystemLoader($instance->dir . '/templates');
		$twig = new \Twig\Environment($loader, array());
		return $twig->render($templateName, $values);
	}

	public static function GetListOfBackups($limit = 0)
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

			if($limit!=0)
				$backups = array_slice($backups, 0, $limit);
			
			$result = array();

			foreach ($backups as $k => $v) {
				array_push($result, array(
					"backup" => file_exists($backup . str_replace("_deployed.zip", "_backedup.zip", $v)) ? str_replace("_deployed.zip", "_backedup.zip", $v) : null,
					"deploy" => file_exists($backup . $v) ? $v : null
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
			$backupfile = $dt . "_backedup.zip";
			$zipBackup->saveAsFile($backup . "/" . $backupfile);
		}
		//end backup
		copy($file, $backup . "/" . $dt . "_deployed.zip");
		$fz->extractTo($path);

		return array(
			"message" => "Deployed successfully",
			"package" => $dt,
			"files" => array_values(array_filter($list, function ($item) {
				return !str_ends_with($item, "/");
			}))
		);
	}

	public static function Revert($packagename)
	{
		$dt = date("Ymd-his");
		$blogid = get_current_blog_id();
		$path = ABSPATH . "wp-content/uploads/backup/$blogid/";
		$blogid = get_current_blog_id();

		$file = $packagename . "_deployed.zip";
		$packagefile = $packagename . "_deployed.zip";

		if (!file_exists($path . $file)) {
			wp_send_json_error(array("message" => "File '$file' does not exists."));
			wp_die();
		}

		if (!file_exists($path . $packagefile)) {
			$zipFile = new \PhpZip\ZipFile();

			if(!file_exists($path . $file))
				throw new Exception("'$file' does not exists.");

			$fz = $zipFile->openfile($path . $file);
			$ziplist = $fz->getListFiles();

			foreach ($ziplist as $f) {
				unlink(ABSPATH . $f);
			}
			return array("message" => "All added files removed.", "files" => $ziplist);
			wp_die();
		} else {
			$zipFile = new \PhpZip\ZipFile();
			$fz = $zipFile->openfile($path . $file);
			$ziplist = $fz->getListFiles();

			$zipPackage = new \PhpZip\ZipFile();
			$fzp = $zipPackage->openfile($path . str_replace("_backedup.zip", "_deployed.zip", $file));
			$zipPackgeList = $fzp->getListFiles();

			$diff = array_diff($ziplist, $zipPackgeList);

			foreach ($diff as $dif) {
				unlink($path . $dif);
			}

			return wd_utilities::Unzip($path . $file);
		}
	}

	public static function GetDetail($filename)
	{
		$blogid = get_current_blog_id();
		$zipFile = new \PhpZip\ZipFile();
		$backuppath = ABSPATH . "wp-content/uploads/backup/$blogid/";
		if(!file_exists($backuppath . $filename))
			throw new Exception("'$filename' does not exists.");

		$zipFile->openFile($backuppath . $filename);
		$list = $zipFile->getListFiles();
		$result = array_values(array_filter($list, function ($item) {
			return !str_ends_with($item, "/");
		}));
		return $result;
	}


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

	public static function CleanUp()
	{
		$blogid = get_current_blog_id();
		$root = ABSPATH . "wp-content/uploads/backup/$blogid/";

		$all = wd_utilities::GetListOfBackups(1000);

		$dif = array_splice($all, 5);
		$errors = array();
		foreach ($dif as $d) {
			try {
				if($d["deploy"]!=null)
					unlink($root . $d["deploy"]);
				if($d["backup"]!=null)
					unlink($root . $d["backup"]);
			} catch (Exception $exp) {
				array_push($erros, $exp->getMessage());
			}
		}

		return $errors;
	}
}