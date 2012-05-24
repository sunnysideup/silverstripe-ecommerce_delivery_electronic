<?php

/**
 * This file contains the tricks for delivering an order electronically.
 *
 * Firstly there is a step you can include as an order step.
 *
 * The order step works out the files to be added to the order
 * and the Order Status Log contains all the downloadable files.
 *
 *
 *
 * NOTA BENE: your buyable MUST have the following method:
 * DownloadFiles();
 *
 * TODO: add ability to first "disable" and then delete files...
 * TODO: add ability to restor downloads
 *
 *
 */


class ElectronicDelivery_OrderStep extends OrderStep {

	static $db = array(
		"NumberOfHoursBeforeDownloadGetsDeleted" => "Int"
	);

	static $has_one = array(
		"AdditionalFile1" => "File",
		"AdditionalFile2" => "File",
		"AdditionalFile3" => "File",
		"AdditionalFile4" => "File",
		"AdditionalFile5" => "File",
		"AdditionalFile6" => "File",
		"AdditionalFile7" => "File"
	);

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanPay" => 0,
		"CustomerCanCancel" => 0,
		"Name" => "Download",
		"Code" => "DOWNLOAD",
		"Sort" => 37,
		"ShowAsUncompletedOrder" => 0,
		"ShowAsInProcessOrder" => 1,
		"NumberOfHoursBeforeDownloadGetsDeleted" => 72
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("NumberOfHoursBeforeDownloadGetsDeleted_Header", _t("OrderStep.NUMBEROFHOURSBEFOREDOWNLOADGETSDELETED", "Download Management"), 3), "NumberOfHoursBeforeDownloadGetsDeleted");
		$fields->addFieldToTab("Root.Main", new HeaderField("AdditionalFile1_Header", _t("OrderStep.ADDITIONALFILE", "Files added to download"), 3), "AdditionalFile1");
		return $fields;
	}

	/**
	 * Can always run step.
	 * @param DataObject - $order Order
	 * @return Boolean
	 **/
	public function initStep($order) {
		$oldDownloadFolders = $this->getFoldersToBeExpired();
		if($oldDownloadFolders) {
			foreach($oldDownloadFolders as $oldDownloadFolder) {
				$oldDownloadFolder->Expired = 1;
				$oldDownloadFolder->write();
			}
		}
		return true;
	}

	/**
	 * Add the member to the order, in case the member is not an admin.
	 *@param DataObject - $order Order
	 *@return Boolean
	 **/
	public function doStep($order) {
		if(!DataObject::get_one("ElectronicDelivery_OrderLog", "\"OrderID\" = ".$order->ID)) {
			$files = new DataObjectSet();
			for($i = 1; $i < 8; $i++) {
				$fieldName = "AdditionalFile".$i."ID";
				if($this->$fieldName) {
					$file = DataObject::get_by_id("File", $this->$fieldName);
					$files->push($file);
				}
			}
			$items = $order->Items();
			if($items) {
				foreach($items as $item) {
					$buyable = $item->Buyable();
					if($buyable) {
						if(method_exists($buyable, "DownloadFiles")) {
							$itemDownloadFiles = $buyable->DownloadFiles();
							if($itemDownloadFiles) {
								foreach($itemDownloadFiles as $itemDownloadFile) {
									$files->push($itemDownloadFile);
								}
							}
						}
					}
				}
			}
			$log = new ElectronicDelivery_OrderLog();
			$log->OrderID = $order->ID;
			$log->AuthorID = $order->MemberID;
			$log->write();
			$log->AddFiles($files);
		}
		return true;
	}


	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 *@param FieldSet $fields
	 *@param Order $order
	 *@return FieldSet
	 **/
	function addOrderStepFields(&$fields, $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		$fields->addFieldToTab("Root.Next", new HeaderField("DownloadFiles", "Files are available for download", 3), "ActionNextStepManually");
		return $fields;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.DOWNLOADED_DESCRIPTION", "During this step the customer downloads her or his order. The shop admininistrator does not do anything during this step.");
	}


	protected function getFoldersToBeExpired() {
		return DataObject::get(
			"ElectronicDelivery_OrderLog",
			"\"Expired\" = 0 AND UNIX_TIMESTAMP(NOW())  - UNIX_TIMESTAMP(\"Created\")  > (60 * 60 * 24 * ".$this->NumberOfHoursBeforeDownloadGetsDeleted." ) "
		);
	}




}

/**
 * This is an OrderStatusLog for the downloads
 * It shows the download links
 * To make it work, you will have to add files.
 *
 *
 *
 *
 *
 *
 *
 *
 *
 */
class ElectronicDelivery_OrderLog extends OrderStatusLog {

	/**
	 * Standard SS variable
	 */
	static $db = array(
		"FolderName" => "Varchar(32)",
		"Expired" => "Boolean",
		"FilesAsString" => "Text"
	);

	/**
	 * Standard SS variable
	 */
	static $many_many = array(
		"Files" => "File"
	);

	/**
	 * Standard SS variable
	 */
	public static $summary_fields = array(
		"Created" => "Date",
		"Type" => "Type",
		"Title" => "Title",
		"FolderName" => "Folder"
	);

	/**
	 * Standard SS variable
	 */
	public static $defaults = array(
		"InternalUseOnly" => false,
		"Expired" => false
	);

	function populateDefaults(){
		parent::populateDefaults();
		$this->Note =  "<p>"._t("OrderLog.NODOWNLOADSAREAVAILABLEYE", "No downloads are available yet.")."</p>";
	}

	/**
	*
	*@return Boolean
	**/
	public function canDelete($member = null) {
		return true;
	}

	/**
	*
	*@return Boolean
	**/
	public function canCreate($member = null) {
		return true;
	}

	/**
	*
	*@return Boolean
	**/
	public function canEdit($member = null) {
		return false;
	}

	/**
	 * Standard SS var
	 * @var Array
	 */
	public static $searchable_fields = array(
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
	public static $singular_name = "Electronic Delivery Details";
		function i18n_singular_name() { return _t("OrderStatusLog.ELECTRONICDELIVERYDETAIL", "Electronic Delivery Detail");}

	/**
	 * Standard SS var
	 * @var String
	 */
	public static $plural_name = "Electronic Deliveries Details";
		function i18n_plural_name() { return _t("OrderStatusLog.ELECTRONICDELIVERIESDETAILS", "Electronic Deliveries Details");}

	/**
	 * Standard SS var
	 * @var String
	 */
	public static $default_sort = "\"Created\" DESC";


	/**
	* Size of the folder name (recommended to be at least 5+)
	* @var Int
	*/
	protected static $random_folder_name_character_count = 12;
		static function set_random_folder_name_character_count($i){self::$random_folder_name_character_count = $i;}

	/**
	 * if set to true, an .htaccess file will be added to the download folder with the following
	 * content: Options -Indexes
	* @var Boolean
	*/
	protected static $add_htaccess_file = true;
		static function set_add_htaccess_file($b){self::$add_htaccess_file = $b;}

	/**
	 * List of files to be ignored
	 * @var Array
	 */
	protected static $files_to_be_excluded = array();
		static function set_files_to_be_excluded($a){self::$files_to_be_excluded = $a;}
		static function get_files_to_be_excluded(){return self::$files_to_be_excluded;}

	/**
	 * Permissions on download folders
	 * @var string
	 */
	protected static $permissions_on_folder = "0755";
		static function set_permissions_on_folder($s){self::$permissions_on_folder = $s;}
		static function get_permissions_on_folder(){return self::$permissions_on_folder;}


	/**
	 * @var String $order_dir - the root folder for the place where the files for the order are saved.
	 * if the variable is equal to downloads then the downloads URL is www.mysite.com/downloads/
	 */
	protected static $order_dir = 'downloads';
		static function set_order_dir($s) {self::$order_dir = $s;}
		static function get_order_dir() {return self::$order_dir;}


	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new LiteralField("FilesInFolder", _t("OrderStep.ACTUALFilesInFolder", "Actual files in folder: ").implode(", ", $this->getFilesInFolder())));
		return $fields;
	}

	/**
	 * Adds the download files to the Log and makes them available for download.
	 * @param DataObjectSet | Null $dosWithFiles - Data Object Set with files
	 */
	public function AddFiles($dosWithFiles){
		$this->Title = _t("OrderStatusLog.DOWNLOADFILES", "Download Files");
		$this->Note = "<ul>";
		if(!$this->OrderID) {
			user_error("Tried to add files to an ElectronicDelivery_OrderStatus object without an OrderID");
		}
		if($dosWithFiles && $dosWithFiles->count()){
			$fullFolderPath = $this->createOrderDownloadFolder(true);
			$folderOnlyPart = $this->createOrderDownloadFolder(false);
			$existingFiles = $this->Files();
			$alreadyCopiedFileNameArray = array();
			foreach($dosWithFiles as $file) {
				if($file->exists()) {
					$existingFiles->add($file);
					$copyFrom = $file->getFullPath();
					$fileName = $file->Name;
					if(file_exists($copyFrom)) {
						$destinationFile = $fullFolderPath."/".$file->Name;
						$destinationURL = Director::absoluteURL("/".$this->getBaseFolder(false)."/".$folderOnlyPart."/".$fileName);
						if(!in_array($copyFrom, $alreadyCopiedFileNameArray)) {
							$alreadyCopiedFileNameArray[] = $fileName;
							if(copy($copyFrom, $destinationFile)) {
								$this->FilesAsString .= "\r\n COPYING $copyFrom to $destinationFile \r\n |||".serialize($file);
								$this->Note .= '<li><a href="'.$destinationURL.'">'.$file->Title.'</a></li>';
							}
						}
					}
					else {
						$this->Note .= "<li>"._t("OrderLog.NOTINCLUDEDIS", "no download available: ").$file->Title."</li>";
					}
				}
			}
		}
		else {
			$this->Note .= "<li>"._t("OrderStatusLog.THEREARENODOWNLOADSWITHTHISORDER", "There are no downloads for this order.")."</li>";
		}
		$this->Note .= "</ul>";
		$this->Expired = false;
		$this->write();

	}


	/**
	 * Standard SS method
	 * Creates the folder and files.
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->FolderName = $this->createOrderDownloadFolder(true);
	}

	/**
	 * Standard SS method
	 * Creates the folder and files.
	 */
	function onAfterWrite() {
		parent::onAfterWrite();
		if($this->Expired) {
			$this->deleteFolderContents();
			$this->Note = "<p>"._t("OrderStatusLog.DOWNLOADSHAVEEXPIRED", "Downloads have expired")."</p>";
		}
	}


	/**
	 * Standard SS method
	 * Deletes the files in the download folder, and the actual download folder itself.
	 * You can use the "MakeExpired" method to stop downloads.
	 * There is no need to delete the download.
	 */
	function onBeforeDelete(){
		parent::onBeforeDelete();
		if($this->FolderName) {
			$this->deleteFolderContents();
			@unlink($this->FolderName);
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
	 * @param Boolean $absolutePath
	 * @return String | NULL
	 */
	protected function createOrderDownloadFolder($absolutePath = true){
		//already exists - do nothing
		if($this->FolderName) {
			$fullFolderName = $this->FolderName;
		}
		else {
		//create folder....
			$randomFolderName = substr(md5(time()+rand(1,999)), 0, self::$random_folder_name_character_count)."_".$this->OrderID;
			$fullFolderName = $this->getBaseFolder(true)."/".$randomFolderName;
			if(file_exists($fullFolderName)) {
				$allOk = true;
			}
			else {
				$allOk = @mkdir($fullFolderName, self::get_permissions_on_folder());
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
		return "error";
	}

	/**
	 * returns the folder in which the orders are kept
	 * (each order has an individual folder within this base folder)
	 * @param Boolean $absolutePath - absolute folder path (set to false to get relative path)
	 * @return String
	 */
	protected function getBaseFolder($absolutePath = true) {
		$baseFolderRelative = self::$order_dir;
		$baseFolderAbsolute = Director::baseFolder()."/".$baseFolderRelative;
		if(!file_exists($baseFolderAbsolute)) {
			@mkdir($baseFolderAbsolute, self::get_permissions_on_folder());
		}
		if(!file_exists($baseFolderAbsolute)) {
			user_error("Can not create folder: ".$baseFolderAbsolute);
		}
		$manifestExcludeFile = $baseFolderAbsolute."/"."_manifest_exclude";
		if(!file_exists($manifestExcludeFile)) {
			$manifestExcludeFileHandle = fopen($manifestExcludeFile, 'w') or user_error("Can not create ".$manifestExcludeFile);
			fwrite($manifestExcludeFileHandle, "Please do not delete this file");
			fclose($manifestExcludeFileHandle);
		}
		if(self::$add_htaccess_file) {
			$htaccessfile = $baseFolderAbsolute."/".".htaccess";
			if(!file_exists($htaccessfile)) {
				$htaccessfileHandle = fopen($htaccessfile, 'w') or user_error("Can not create ".$htaccessfile);
				fwrite($htaccessfileHandle, "Options -Indexes");
				fclose($htaccessfileHandle);
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
	 * get folder contents
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
							if(!in_array($file, self::$files_to_be_excluded)) {
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

	protected function deleteFolderContents(){
		if($this->FolderName) {
			$files = $this->getDirectoryContents($this->FolderName, $showFiles = 1, $showFiles = 0);
			if($files) {
				foreach($files as $file) {
					unlink($file);
				}
			}
		}
	}


}
