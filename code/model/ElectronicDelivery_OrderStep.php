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
		"SendMessageToCustomer" => "Boolean",
		"NumberOfHoursBeforeDownloadGetsDeleted" => "Float"
	);

	private static $many_many = array(
		"AdditionalFiles" => "File"
	);

	private static $field_labels = array(
		"SendMessageToCustomer" => "Send a message to the customer with download details?",
		"NumberOfHoursBeforeDownloadGetsDeleted" => "Number of hours before download expires (you can use decimals (e.g. 0.5 equals half-an-hour)."
	);

	private static $defaults = array(
		"Name" => "Download",
		"Code" => "DOWNLOAD",
		"Description" => "Customer downloads the files",
		"NumberOfHoursBeforeDownloadGetsDeleted" => 72,
		"SendMessageToCustomer" => true,

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

	/**
	 * The OrderStatusLog that is relevant to the particular step.
	 * @var String
	 */
	protected $relevantLogEntryClassName = "ElectronicDelivery_OrderLog";

	/**
	 * @var String
	 */
	protected $emailClassName = "Order_StatusEmail";


	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("NumberOfHoursBeforeDownloadGetsDeleted_Header", _t("ElectronicDelivery_OrderStep.NUMBEROFHOURSBEFOREDOWNLOADGETSDELETED", "Download Management"), 3), "NumberOfHoursBeforeDownloadGetsDeleted");
		$fields->addFieldToTab("Root.Main", new HeaderField("SendMessageToCustomer_Header", _t("ElectronicDelivery_OrderStep.SendMessageToCustomer", "Send a message to the customer?"), 3), "SendMessageToCustomer");
		$fields->addFieldToTab("Root.AdditionalFiles", new UploadField("AdditionalFiles", _t("ElectronicDelivery_OrderStep.ADDITIONALFILE", "Files to be added to download")));
		return $fields;
	}

	/**
	 * Can always run step.
	 * remove anything that is expired...
	 * @param Order $order
	 * @return Boolean
	 **/
	public function initStep(Order $order) {
		return true;
	}

	/**
	 * Add the member to the order, in case the member is not an admin.
	 * @param Order $order
	 * @return Boolean
	 **/
	public function doStep(Order $order) {
		$logClassName = $this->getRelevantLogEntryClassName();
		$obj = $logClassName::get()
			->filter(array("OrderID" => $order->ID))
			->first();
		if(!$obj) {
			$files = new ArrayList();
			$items = $order->Items();
			if($items && $items->count()) {
				foreach($items as $item) {
					$buyable = $item->Buyable();
					if($buyable) {
						$method = $this->Config()->get("download_method_in_byable");
						if($buyable->hasMethod($method)) {
							$itemDownloadFiles = $buyable->$method();
							if($itemDownloadFiles) {
								if($itemDownloadFiles instanceof DataList) {
									if($itemDownloadFiles->count()) {
										foreach($itemDownloadFiles as $itemDownloadFile) {
											$files->push($itemDownloadFile);
										}
									}
								}
								else {
									user_error("$method should return a Datalist. Specifically watch for has_one methods as they return a DataObject.", E_USER_NOTICE);
								}
							}
						}
					}
				}
			}
			if($files->count()) {
				//additional files ar only added to orders
				//with downloads
				$additionalFiles = $this->AdditionalFiles();
				foreach($additionalFiles as $additionalFile) {
					$files->push($additionalFile);
				}
				//create log with information...
				$obj = $logClassName::create();
				$obj->OrderID = $order->ID;
				$obj->AuthorID = $order->MemberID;
				$obj->NumberOfHoursBeforeDownloadGetsDeleted = $this->NumberOfHoursBeforeDownloadGetsDeleted;
				$obj->write();
				$obj->AddFiles($files);
			}
			else {
				//do nothingh....
			}
		}
		return true;
	}

	/**
	 * nextStep:
	 * returns the next step (after it checks if everything is in place for the next step to run...)
	 * @see Order::doNextStatus
	 *
	 * @param Order $order
	 *
	 * @return OrderStep | Null (next step OrderStep object)
	 **/
	public function nextStep(Order $order) {
		if($orderLog = $this->RelevantLogEntry($order)) {
			if($this->SendDetailsToCustomer){
				if(!$this->hasBeenSent($order)) {
					$subject = $this->EmailSubject;
					$message = $this->CustomerMessage;
					$order->sendEmail($subject, $message, $resend = false, $adminOnly = false, $this->getEmailClassName());
				}
			}
			if($orderLog->IsExpired()) {
				$orderLog->write();
				return parent::nextStep($order);
			}
		}
		//we immediately go to the next step if there is
		//nothing to download ...
		else {
			return parent::nextStep($order);
		}
		return null;
	}

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 *
	 * @param FieldList $fields
	 * @param Order $order
	 *
	 * @return FieldList
	 **/
	function addOrderStepFields(FieldList $fields, Order $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		$fields->addFieldToTab("Root.Next", new HeaderField("DownloadFiles", _t("ElectronicDelivery_OrderStep.AVAILABLE_FOR_DOWNLOAD", "Files are available for download"), 3), "ActionNextStepManually");
		return $fields;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.DOWNLOADED_DESCRIPTION", _t("ElectronicDelivery_OrderStep.description", "During this step the customer downloads her or his order. The shop admininistrator does not do anything during this step."));
	}


	/**
	 * For some ordersteps this returns true...
	 * @return Boolean
	 **/
	protected function hasCustomerMessage() {
		return $this->SendMessageToCustomer;
	}


}
