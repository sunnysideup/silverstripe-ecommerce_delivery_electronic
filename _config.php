<?php


/**
 * developed by www.sunnysideup.co.nz
 * author: Nicolaas - modules [at] sunnysideup.co.nz
**/


//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START ecommerce_delivery_electronically MODULE ----------------===================
//MUST SET
//ElectronicDelivery_OrderLog::set_random_folder_name_character_count(12);
//ElectronicDelivery_OrderLog::set_add_htaccess_file(true);
//ElectronicDelivery_OrderLog::set_files_to_be_excluded(array());
//ElectronicDelivery_OrderLog::set_permissions_on_folder("0755");
//ElectronicDelivery_OrderLog::set_order_dir("downloads");
//===================---------------- END ecommerce_delivery_electronically  MODULE ----------------===================
/*

dont forget to add the following .htaccess file to your originating folders:

IndexIgnore *
order allow,deny
deny from all

so that no-one can access the original files.


*/
