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

	private static $db = array(
		"NumberOfHoursBeforeDownloadGetsDeleted" => "Float"
	);

	private static $many_many = array(
		"AdditionalFiles" => "File"
	);

	private static $defaults = array(
		"Name" => "Download",
		"Code" => "DOWNLOAD",
		"Description" => "Customer downloads the files",
		"NumberOfHoursBeforeDownloadGetsDeleted" => 72,

		//customer privileges
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 0,
		//What to show the customer...
		"ShowAsUncompletedOrder" => 0,
		"ShowAsInProcessOrder" => 1,
		"ShowAsCompletedOrder" => 0,
		//sort
		"Sort" => 37,
	);

	/**
	 * The method that provides a datalist of files to be downloaded for a buyable.
	 * @var String
	 */
	private static $download_method_in_byable = "DownloadFiles";

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("NumberOfHoursBeforeDownloadGetsDeleted_Header", _t("OrderStep.NUMBEROFHOURSBEFOREDOWNLOADGETSDELETED", "Download Management"), 3), "NumberOfHoursBeforeDownloadGetsDeleted");
		$fields->addFieldToTab("Root.AdditionalFiles", new UploadField("AdditionalFiles", _t("OrderStep.ADDITIONALFILE", "Files added to download")));
		return $fields;
	}

	/**
	 * Can always run step.
	 * remove anything that is expired...
	 * @param Order $order
	 * @return Boolean
	 **/
	public function initStep(Order $order) {
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
	 * @param Order $order
	 * @return Boolean
	 **/
	public function doStep(Order $order) {
		$obj = ElectronicDelivery_OrderLog::get()
			->filter(array("OrderID" => $order->ID))
			->first();
		if(!$obj) {
			$files = new ArrayList();
			$items = $order->Items();
			if($items) {
				foreach($items as $item) {
					$buyable = $item->Buyable();
					if($buyable) {
						$method = $this->Config()->get("download_method_in_byable");
						$itemDownloadFiles = $buyable->$method();
						if($itemDownloadFiles && $itemDownloadFiles->count()) {
							foreach($itemDownloadFiles as $itemDownloadFile) {
								debug::log("adding: ".$itemDownloadFile->ID);
								$files->push($itemDownloadFile);
							}
						}
					}
				}
			}
			//additional files
			$additionalFiles = $this->AdditionalFiles();
			foreach($additionalFiles as $additionalFile) {
				$files->push($additionalFile);
			}
			//create log with information...
			$obj = ElectronicDelivery_OrderLog::create();
			$obj->OrderID = $order->ID;
			$obj->AuthorID = $order->MemberID;
			$obj->NumberOfHoursBeforeDownloadGetsDeleted = $this->NumberOfHoursBeforeDownloadGetsDeleted;
			$obj->write();
			$obj->AddFiles($files);
		}
		return true;
	}


	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 * @param FieldList $fields
	 * @param Order $order
	 * @return FieldList
	 **/
	function addOrderStepFields(FieldList $fields, Order $order) {
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
		return ElectronicDelivery_OrderLog::get()
			->where(
				"\"Expired\" = 0 AND UNIX_TIMESTAMP(NOW())  - UNIX_TIMESTAMP(\"Created\")  > (60 * 60 * 24 * ".$this->NumberOfHoursBeforeDownloadGetsDeleted." ) "
			);
	}




}
