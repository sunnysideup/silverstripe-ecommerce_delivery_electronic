<?php

/**
 * This is an OrderStatusLog for the downloads
 * It shows the download links
 * To make it work, you will have to add files.
 *
 * When it is first written it creates a folder for the downloads
 * you can then add files using the AddFiles method.
 *
 *
 */
class ElectronicDelivery_OrderLog extends OrderStatusLog {
	/**
	 * Use for debugging
	 * uses debug::log
	 * @boolean
	 */
	private $debug = false;

	/**
	 * Standard SS variable
	 */
	private static $db = array(
		"FolderName" => "Varchar(32)",
		"Completed" => "Boolean",
		"NumberOfHoursBeforeDownloadGetsDeleted" => "Float"
	);

	/**
	 * Standard SS variable
	 */
	private static $many_many = array(
		"Files" => "File"
	);

	/**
	 * Standard SS variable
	 */
	private static $summary_fields = array(
		"Created" => "Date",
		"Type" => "Type",
		"Title" => "Title",
		"FolderName" => "Folder"
	);

	/**
	 * Standard SS variable
	 */
	private static $defaults = array(
		"InternalUseOnly" => false,
		"Completed" => false
	);

	/**
	 * Set the default: the files are not ready yet!
	 * Standard SS method
	 */
	function populateDefaults(){
		parent::populateDefaults();
		$this->Note =  "<p>"._t("OrderLog.NODOWNLOADSAREAVAILABLEYET", "No downloads are available yet.")."</p>";
	}

	/**
	 *
	 * @return Boolean
	 **/
	public function canDelete($member = null) {
		return true;
	}

	/**
	 *
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		return true;
	}

	/**
	 *
	 * @return Boolean
	 **/
	public function canEdit($member = null) {
		return false;
	}

	/**
	 * Standard SS var
	 * @var Array
	 */
	private static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		"Title" => "PartialMatchFilter",
		"Note" => "PartialMatchFilter",
		"FolderName" => "PartialMatchFilter"
	);


	/**
	 * Standard SS var
	 * @var String
	 */
	private static $singular_name = "Electronic Delivery Details for one Order";
		function i18n_singular_name() { return _t("OrderStatusLog.ELECTRONICDELIVERYDETAIL", "Electronic Delivery Details for one Order");}

	/**
	 * Standard SS var
	 * @var String
	 */
	private static $plural_name = "Electronic Deliveries Detail for many Orders";
		function i18n_plural_name() { return _t("OrderStatusLog.ELECTRONICDELIVERIESDETAILS", "Electronic Deliveries Detail for many Orders");}

	/**
	 * Standard SS var
	 * @var String
	 */
	private static $default_sort = "\"Created\" DESC";


	/**
	* Size of the folder name (recommended to be at least 5+)
	* @var Int
	*/
	private static $random_folder_name_character_count = 12;

	/**
	 * if set to anything except an empty string,
	 * an .htaccess file will be added to the download folder
	 * with the content of the variable
	 * content idea: Options -Indexes (stops directly from listing folders)
	 * @var String
	 */
	private static $htaccess_content = "";

	/**
	 * List of files to be ignored when searching for files in the folder
	 * This may allow you to add "hidden" files or ignore other files.
	 * Can be added as
	 *     1 => mypng.png
	 *     2 => mysecondImage.jpg
	 *
	 * @var Array
	 */
	private static $files_to_be_excluded = array();

	/**
	 * Permissions on download folders
	 * if not set, it will use:
	 * Config::inst()->get('Filesystem', 'folder_create_mask')
	 * @var string
	 */
	private static $permissions_on_folder = "";

	/**
	 * @var String $order_dir - the root folder for the place where the files for the order are saved.
	 * if the variable is equal to downloads then the downloads URL is www.mysite.com/downloads/
	 */
	private static $order_dir = 'downloads';

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new LiteralField("FilesInFolder", _t("OrderStep.ACTUALFilesInFolder", "Actual files in folder: ").implode(", ", $this->getFilesInFolder())));
		return $fields;
	}

	/**
	 * Adds the download files to the Log and makes them available for download.
	 * @param ArrayList | Null $dosWithFiles - Data Object Set with files
	 */
	public function AddFiles($listOfFiles){
		//update log fields
		$this->Title = _t("OrderStatusLog.DOWNLOADFILES", "Download Files");
		$this->Note = "<ul>";
		if(!$this->OrderID) {
			user_error("Tried to add files to an ElectronicDelivery_OrderStatus object without an OrderID");
		}
		if($this->debug) {
			debug::log(print_r($listOfFiles, 1));
			debug::log("COUNT: ".$listOfFiles->count());
		}
		//are there any files?
		if($listOfFiles && $listOfFiles->count()){
			if($this->debug) {
				debug::log("doing it");
			}
			//create folder
			$fullFolderPath = $this->getOrderDownloadFolder(true);
			$folderOnlyPart = $this->getOrderDownloadFolder(false);
			$existingFiles = $this->Files();
			$alreadyCopiedFileNameArray = array();

			//loop through files
			foreach($listOfFiles as $file) {
				if($file->exists() && file_exists($file->getFullPath())) {
					$existingFiles->add($file);
					$copyFrom = $file->getFullPath();
					$fileName = $file->Name;
					$destinationFile = $fullFolderPath."/".$file->Name;
					$destinationURL = Director::absoluteURL("/".$this->getBaseFolder(false)."/".$folderOnlyPart."/".$fileName);
					if(!in_array($copyFrom, $alreadyCopiedFileNameArray)) {
						$alreadyCopiedFileNameArray[] = $fileName;
						if(copy($copyFrom, $destinationFile)) {
							if($this->debug) {
								debug::log("\r\n COPYING $copyFrom to $destinationFile \r\n |||".serialize($file));
							}
							$this->Note .= '<li><a href="'.$destinationURL.'" target="_blank">'.$file->Title.'</a></li>';
						}
					}
					else {
						$this->Note .= "<li>"._t("OrderLog.NOTINCLUDEDIS", "no download available: ").$file->Title."</li>";
					}
				}
			}
		}
		else {
			$this->Completed = true;
			$this->Note .= "<li>"._t("OrderStatusLog.THEREARENODOWNLOADSWITHTHISORDER", "There are no downloads for this order.")."</li>";
		}
		$this->Note .= "</ul>";
		$this->write();
	}


	/**
	 * checks if the download has expired (i.e. too much time has passed)
	 * @return Boolean
	 */
	public function IsExpired(){
		if($this->Completed) {
			return true;
		}
		if(!$this->Created) {
			return false;
		}
		return (strtotime("Now") - strtotime($this->Created)) > (60 * 60 * $this->NumberOfHoursBeforeDownloadGetsDeleted);
	}

	/**
	 * Standard SS method
	 * Creates the folder and files.
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
		if(!$this->IsExpired()) {
			$this->FolderName = $this->getOrderDownloadFolder(true);
		}
	}

	/**
	 * making sure we dont end up in an infinite loop
	 * @var int
	 */
	private $loopEscape = 0;

	/**
	 * Standard SS method
	 * If it has expired, then the folder is deleted...
	 */
	function onAfterWrite() {
		parent::onAfterWrite();
		if($this->FolderName) {
			if($this->Completed) {
				//do nothing ...
			}
			else {
				$this->loopEscape++;
				if($this->IsExpired() && $this->loopEscape < 10) {
					$this->Note = "<p>"._t("OrderStatusLog.DOWNLOADSHAVEEXPIRED", "Downloads have expired.")."</p>";
					$this->Completed = $this->deleteFolderContents();
					$this->write();
				}
				elseif($this->loopEscape == 10) {
					user_error("Tried to deleted ".$this->FolderName." 10 times without success", E_USER_NOTICE);
				}
			}
		}
	}

	/**
	 * Standard SS method
	 * Deletes the files in the download folder,
	 * and the actual download folder itself.
	 */
	function onBeforeDelete(){
		parent::onBeforeDelete();
		if($this->FolderName && !$this->Completed) {
			$this->deleteFolderContents();
		}
	}

	/**
	 * returns the list of files that are in the current folder
	 * @return Array
	 */
	protected function getFilesInFolder() {
		if($this->FolderName && file_exists($this->FolderName)) {
			return $this->getDirectoryContents($this->FolderName, $showFiles = 1, $showFolders = 0);
		}
		else {
			return array(_t("OrderStatus.NOFOLDER", "No folder is associated with this download entry."));
		}
	}

	/**
	 * creates a folder and returns the full folder path
	 * if the folder is already created it still returns the folder path,
	 * but it does not create the folder.
	 *
	 * @param Boolean $absolutePath
	 *
	 * @return NULL | String
	 */
	protected function getOrderDownloadFolder($absolutePath = true){
		//already exists - do nothing
		if($this->FolderName) {
			$fullFolderName = $this->FolderName;
		}
		elseif($baseFolder = $this->getBaseFolder(true)) {
			//create folder....
			$randomFolderName = substr(md5(time()+rand(1,999)), 0, $this->Config()->get("random_folder_name_character_count"))."_".$this->OrderID;
			$fullFolderName = $baseFolder."/".$randomFolderName;
			if(file_exists($fullFolderName)) {
				$allOk = true;
			}
			else {
				$allOk = mkdir($fullFolderName, $this->getFolderPermissions());
			}
			if(!file_exists($fullFolderName)) {
				user_error("Can not create folder: ".$fullFolderName);
				return;
			}
			if($allOk){
				$this->FolderName = $fullFolderName;
			}
		}
		if($absolutePath) {
			return $fullFolderName;
		}
		else {
			//TO DO: test
			return str_replace($this->getBaseFolder(true)."/", "", $fullFolderName);
		}
	}

	/**
	 * returns the folder in which all the downloads are kept
	 * (each order has an individual folder within this base folder)
	 * returns location of base folder.
	 *
	 * @param Boolean $absolutePath - absolute folder path (set to false to get relative path)
	 *
	 * @return NULL | String
	 */
	protected function getBaseFolder($absolutePath = true) {
		$baseFolderRelative = $this->Config()->get("order_dir");
		$baseFolderAbsolute = Director::baseFolder()."/".$baseFolderRelative;
		if(!file_exists($baseFolderAbsolute)) {
			mkdir($baseFolderAbsolute, $this->getFolderPermissions());
		}
		if(!file_exists($baseFolderAbsolute)) {
			user_error("Can not create folder: ".$baseFolderAbsolute);
			return;
		}
		$manifestExcludeFile = $baseFolderAbsolute."/"."_manifest_exclude";
		if(!file_exists($manifestExcludeFile)) {
			$manifestExcludeFileHandle = fopen($manifestExcludeFile, 'w') or user_error("Can not create ".$manifestExcludeFile);
			fwrite($manifestExcludeFileHandle, "Please do not delete this file");
			fclose($manifestExcludeFileHandle);
		}
		if($htAccessContent = $this->Config()->get("htaccess_content")) {
			$htAccessFile = $baseFolderAbsolute."/".".htaccess";
			if(!file_exists($htAccessFile)) {
				$htAccessFileHandle = fopen($htaccessfile, 'w') or user_error("Can not create ".$htAccessFile);
				fwrite($htAccessFileHandle, $htAccessContent);
				fclose($htAccessFileHandle);
			}
		}
		if($absolutePath) {
			return $baseFolderAbsolute;
		}
		else {
			return $baseFolderRelative;
		}
	}

	/**
	 * returns the permissions for the folder to be created.
	 * @return String
	 */
	protected function getFolderPermissions(){
		return $this->Config()->get("permissions_on_folder") ? $this->Config()->get("permissions_on_folder") : Config::inst()->get('Filesystem', 'folder_create_mask');
	}

	/**
	 * get folder contents
	 *
	 * @param String $fullPath (e.g. /var/www/mysite.co.nz/downloads)
	 * @param Boolean $showFiles - list the files in the directory?
	 * @param Boolean $showFolders - list the folders in the directory?
	 *
	 * @return array
	 */
	protected function getDirectoryContents($fullPath, $showFiles = false, $showFolders = false) {
		$files = array();
		if(file_exists($fullPath)) {
			if ($directoryHandle = opendir($fullPath)) {
				while (($file = readdir($directoryHandle)) !== false) {
					/* no links ! */
					$fullFileName = $fullPath."/".$file;
					if( substr($file, strlen($file) - 1) != "." ) {
						if ( (!is_dir($fullFileName) && $showFiles) || ($showFolders && is_dir($fullFileName)) ) {
							if(!in_array($file, $this->Config()->get("files_to_be_excluded"))) {
								array_push($files, $fullFileName);
							}
						}
					}
				}
				closedir($directoryHandle);
			}
		}
		return $files;
	}

	/**
	 * remove all the folder contents and remove the folder itself
	 * as well... Returns true on success.
	 * Assumes that there are no folders in the folder...
	 *
	 * @return Boolean
	 */
	protected function deleteFolderContents(){
		if($this->FolderName) {
			if(file_exists($this->FolderName)) {
				$files = $this->getDirectoryContents($this->FolderName, $showFiles = 1, $showFolders = 0);
				if($files) {
					foreach($files as $file) {
						unlink($file);
					}
				}
				return rmdir($this->FolderName);
			}
		}
		return true;
	}


}
