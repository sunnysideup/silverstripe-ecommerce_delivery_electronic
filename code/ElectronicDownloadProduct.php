<?php

class ElectronicDownloadProduct extends Product {

	/**
	 * Standard SS variable.
	 */
	private static $many_many = array(
		'DownloadFiles' => 'File'
	);

	/**
	 * Standard SS variable.
	 */
	private static $icon = 'ecommerce_delivery_electronic/images/icons/ElectronicDownloadProduct';

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "A product can be downloaded.";

	/**
	 * Standard SS variable.
	 */
	private static $singular_name = "Electronic Download Product";
		function i18n_singular_name() { return _t("ElectronicDownloadProduct.SINGULAR_NAME", "Electronic Download Product");}

	/**
	 * Standard SS variable.
	 */
	private static $plural_name = "Electronic Download Products";
		function i18n_plural_name() { return _t("ElectronicDownloadProduct.PLURAL_NAME", "Electronic Download Products");}


	/**
	 * Standard SS Method
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab('Root.Details', new UploadField('DownloadFiles', _t('ElectronicDownloadProduct.DOWNLOADFILES', 'Download Files')));
		return $fields;
	}

	/**
	 * This is used when you add a product to your cart
	 * if you set it to 1 then you can add 0.1 product to cart.
	 * If you set it to -1 then you can add 10, 20, 30, etc.. products to cart.
	 *
	 * @return Int
	 **/
	function QuantityDecimals(){
		return 0;
	}

}


class ElectronicDeliveryProduct_Controller extends Product_Controller {

}





