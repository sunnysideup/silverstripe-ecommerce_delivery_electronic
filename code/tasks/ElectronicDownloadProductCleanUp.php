<?php



class ElectronicDownloadProductCleanUp extends BuildTask{

	protected $title = "Remove expired downloads from the download folder.";

	protected $description = "Removes all the expired downloads from the download folder.";

	public function run($request) {
		$items = ElectronicDelivery_OrderLog::get()->filter(array("Completed" => 0));
		foreach($items as $item) {
			if($item->IsExpired()) {
				//a simple write will take care of all the deletion process...
				$item->deleteFolderIfExpired();
			}
			else {
				//do nothing
			}
		}
	}

}
