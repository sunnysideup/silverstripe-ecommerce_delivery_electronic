<?php


/**
 * developed by www.sunnysideup.co.nz
 * author: Nicolaas - modules [at] sunnysideup.co.nz
**/


//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START ecommerce_delivery MODULE ----------------===================
//MUST SET
//Order::add_modifier("PickUpOrDeliveryModifier"); // OR //Order::set_modifiers(array("PickUpOrDeliveryModifier"));
//StoreAdmin::add_managed_model("PickUpOrDeliveryModifierOptions");
//Object::add_extension('EcommerceCountry', 'PickUpOrDeliveryModifierOptionsCountry');
//Object::add_extension('EcommerceRegion', 'PickUpOrDeliveryModifierOptionsRegion');

//MAY SET
//PickUpOrDeliveryModifier::set_form_header("Delivery Option (REQUIRED)");

//NOTE: add http://svn.gpmd.net/svn/open/multiselectfield/tags/0.2/ for nicer interface
//===================---------------- END ecommerce_delivery  MODULE ----------------===================
