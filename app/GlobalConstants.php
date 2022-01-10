<?php

/* Global constants for site */

define('DS', DIRECTORY_SEPARATOR);

define('ROOT', base_path());

define('APP_PATH', app_path());

define('WEBSITE_URL', url('/').'/');
define('WEBSITE_CSS_URL', WEBSITE_URL . 'css/');

define('WEBSITE_JS_URL', WEBSITE_URL . 'js/');

define('WEBSITE_IMG_URL', WEBSITE_URL . 'images/');

define('WEBSITE_UPLOADS_ROOT_PATH', ROOT . DS . 'public' .DS );
define('WEBSITE_UPLOADS_URL', WEBSITE_URL . 'public/');

define('DOCUMENT_URL', WEBSITE_UPLOADS_URL . 'documents/');
define('DOCUMENT_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'documents' . DS); 

define('STORE_LOGO_URL', WEBSITE_UPLOADS_URL . 'storelogo/');
define('STORE_LOGO_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'storelogo' . DS);

define('THEMES_IMAGE_URL', WEBSITE_UPLOADS_URL . 'themeimage/');
define('THEMES_IMAGE_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'themeimage' . DS);

define('CKEDITOR_IMAGE_URL', WEBSITE_UPLOADS_URL . 'uploadeditor/');
define('CKEDITOR_IMAGE_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'uploadeditor' . DS); 

define('PRODUCT_URL', WEBSITE_UPLOADS_URL . 'productimg/');
define('PRODUCT_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'productimg' . DS); 

//Seller Product File Image Locations 
define('PRODUCT_FILE_URL', WEBSITE_UPLOADS_URL . 'productfile/');
define('PRODUCT_FILE_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'productfile' . DS); 

define('PRODUCT_EXCEL_FILE_URL', WEBSITE_UPLOADS_URL . 'productexcelfile/');
define('PRODUCT_EXCEL_FILE_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'productexcelfile' . DS); 

//Catelogue Image Location
define('CATELOGUE_URL', WEBSITE_UPLOADS_URL . 'catelogueimg/');
define('CATELOGUE_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'catelogueimg' . DS); 

//Catelogue Image Location
define('SLIDER_URL', WEBSITE_UPLOADS_URL . 'sellerslider/');
define('SLIDER_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'sellerslider' . DS); 

//Seller Staff Image Location
define('STAFF_LOGO_URL', WEBSITE_UPLOADS_URL . 'staff/');
define('STAFF_LOGO_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'staff' . DS);

//Seller Customer Image Location
define('CUSTOMER_LOGO_URL', WEBSITE_UPLOADS_URL . 'customer/');
define('CUSTOMER_LOGO_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'customer' . DS);

//Seller StoreType Image Location
define('STORETYPE_LOGO_URL', WEBSITE_UPLOADS_URL . 'storetypeimg/');
define('STORETYPE_LOGO_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'storetypeimg' . DS);

//Admin profile image path
define('ADMIN_LOGO_URL', WEBSITE_UPLOADS_URL . 'users/');
define('ADMIN_LOGO_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'users' . DS); 

define('EMPLOYEE_IMAGE_URL', WEBSITE_UPLOADS_URL . 'employee/');
define('EMPLOYEE_IMAGE_ROOT_PATH', WEBSITE_UPLOADS_ROOT_PATH .  'employee' . DS); 
define('ACTIVE','1');
define('DEACTIVE','0');

define('CATALOGUE_ACTIVE','1');
define('CATALOGUE_DEACTIVE','0');

define('CATEGORY_ACTIVE','1');
define('CATEGORY_DEACTIVE','0');


define('BANK_STATUS_ACTIVE','1');
define('BANK_STATUS_DEACTIVE','0');

define('ADMIN_ROLE',1);
define('FRONT_USER_ROLE',2);
define('AFFILATE_ROLE',3);
define('MALE',1);
define('FEMALE',2);
define('RECORDS_PER_PAGE',10);
define('RECORDS_PER_PAGE_ACTION',"5,10,15,20,25");
define('PF_ACTIVE',1);
define('PF_DEACTIVE',2);

define('RECORD_PER_PAGE',2);
Config::set('PF_STATUS',array(
	PF_ACTIVE => 'Active',
	PF_DEACTIVE => 'Deactive',
));

//Seller Staff Permission Constant
define('SELLER_FLLACCESS',1);
define('SELLER_CATELOGUE_VIEW',2);
define('SELLER_CATELOGUE_EDIT',3);
define('SELLER_ORDER_ENQUIRIES_VIEW',4);
define('SELLER_ORDER_ENQUIRIES_MANAGE',5);

Config::set('SELLER_STAFF_PERMISSION',array(
	SELLER_FLLACCESS => 'Full Permission',
	SELLER_CATELOGUE_VIEW => 'Only Catelogue View',
	SELLER_CATELOGUE_EDIT => 'Only Catelogue Edit',
	SELLER_ORDER_ENQUIRIES_VIEW => 'Order and Enquiries View',
	SELLER_ORDER_ENQUIRIES_MANAGE => 'Orders and Enquiries Management',
));

//Week
define('MONDAY',1);
define('TUESDAY',2);
define('WEDNESDAY',3);
define('THRUSDAY',4);
define('FRIDAY',5);
define('SATURDAY',6);
define('SUNDAY',7);

Config::set('Week',array(
	MONDAY => 'MONDAY',
	TUESDAY => 'TUESDAY',
	WEDNESDAY => 'WEDNESDAY',
	THRUSDAY => 'THRUSDAY',
	FRIDAY => 'FRIDAY',
	SATURDAY => 'SATURDAY',
	SUNDAY => 'SUNDAY',
));
