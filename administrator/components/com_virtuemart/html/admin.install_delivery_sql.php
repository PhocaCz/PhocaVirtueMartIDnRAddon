<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); 
/*
 * @modified For VirtueMart 1.1 modified by Jan Pavelka
 * @info http://www.phoca.cz/
 * @copyright Copyright (C) 2008 Jan Pavelka. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @version $Id: ps_delivery.php,v 1.2 2006/02/02 07:56:43 juniorhornan Exp $
 * @copyright Copyright (C) 2006 Ingemar Fällman. All rights reserved.
 * @copyright Copyright (C) 2004-2005 Soeren Eberhardt. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 */


$db = new ps_DB;
$q="CREATE TABLE `#__{vm}_bills` (
`vendor_id` int(11) NOT NULL default '0',
`bill_id` int(11) NOT NULL auto_increment,
`delivery_id` int(11) NOT NULL default '0',
`order_id` int(11) NOT NULL default '0',
`obliterated` tinyint(1) NOT NULL default '0',
`due_date` int(11) default NULL,
`paid` tinyint(1) NOT NULL default '0',
`is_invoice` tinyint(1) NOT NULL default '0',
`delay_interest` decimal(10,2) NOT NULL default '0.00',
`cdate` int(11) default NULL,
`mdate` int(11) default NULL,
PRIMARY KEY  (`vendor_id`,`bill_id`),
KEY `idx_bills_delivery_id` (`delivery_id`),
KEY `idx_bills_obliterated` (`obliterated`),
KEY `idx_bills_is_invoice` (`is_invoice`) 
) TYPE=MyISAM;";
$db->query($q);

$q="CREATE TABLE `#__{vm}_deliveries` (
`vendor_id` int(11) NOT NULL default '0',
`delivery_id` int(11) NOT NULL auto_increment,
`order_id` int(11) NOT NULL default '0',
`user_id` int(11) NOT NULL default '0',
`cdate` int(11) default NULL,
`mdate` int(11) default NULL,
`obliterated` tinyint(1) NOT NULL default '0',
`obliterated_by` varchar(50) default NULL,
`obliterated_date` int(11) default NULL,
PRIMARY KEY  (`vendor_id`,`delivery_id`),
KEY `idx_deliveries_user_id` (`user_id`),
KEY `idx_deliveries_obliterated` (`obliterated`)
) TYPE=MyISAM;";
$db->query($q);

$q="CREATE TABLE `#__{vm}_delivery_item` (
`delivery_item_id` int(11) NOT NULL auto_increment,
`delivery_id` int(11) NOT NULL default '0',
`order_id` int(11) NOT NULL default '0',
`vendor_id` int(11) NOT NULL default '0',
`order_item_id` int(11) NOT NULL default '0',
`obliterated` tinyint(1) NOT NULL default '0',
`product_quantity_delivered` int(11) default '0',
PRIMARY KEY  (`delivery_item_id`),
KEY `idx_delivery_item_delivery_id` (`delivery_id`),
KEY `idx_delivery_item_order_id` (`order_id`),
KEY `idx_delivery_item_vendor_id` (`vendor_id`),
KEY `idx_delivery_item_obliterated` (`obliterated`),
KEY `idx_delivery_item_order_item_id` (`order_item_id`)
) TYPE=MyISAM;";
$db->query($q);

$q="SELECT module_id FROM #__{vm}_module WHERE module_name = 'order'";
$db->query($q);
$db->next_record();
$module_id = $db->f('module_id');
if(!is_numeric($module_id)) {
    echo '<h2 style="color:#fc0000">Installation error: Cant find module id for the module "order".</h2>';
    exit;
}

$q="SELECT function_name FROM #__{vm}_function WHERE function_name = 'obliterateDelivery' AND module_id = ".$module_id; 
$db->query($q);
$db->next_record();
if($db->f('function_name') == 'obliterateDelivery') {
    echo '<p style="color:#008000">ObliterateDelivery function found ... OK</p>';
} else {
    $q="INSERT INTO `#__{vm}_function` (`module_id`, `function_name`, `function_class`, `function_method`, `function_description`, `function_perms`) 
VALUES (".$module_id.",'obliterateDelivery','ps_delivery','obliterate','Obliterate a delivery','admin,storeadmin')";
    $db->query($q);
	echo '<p style="color:#008000">ObliterateDelivery function installed ... OK</p>';
}

$q="SELECT function_name FROM #__{vm}_function WHERE function_name = 'addDelivery' AND module_id = ".$module_id; 
$db->query($q);
$db->next_record();
if($db->f('function_name') == 'addDelivery') {
	echo '<p style="color:#008000">AddDelivery function found ... OK</p>';
} else {
    $q="INSERT INTO `#__{vm}_function` (`module_id`, `function_name`, `function_class`, `function_method`, `function_description`, `function_perms`)
    VALUES (".$module_id.",'addDelivery','ps_delivery','add','Create a new delivery.','admin,storeadmin')";
    $db->query($q);
	echo '<p style="color:#008000">AddDelivery function installed ... OK</p>';
}

$q="SELECT function_name FROM #__{vm}_function WHERE function_name = 'saveDelivery' AND module_id = ".$module_id; 
$db->query($q);
$db->next_record();
if($db->f('function_name') == 'saveDelivery') {
	echo '<p style="color:#008000">SaveDelivery function found ... OK</p>';
} else {
    $q="INSERT INTO `#__{vm}_function` (`module_id`, `function_name`, `function_class`, `function_method`, `function_description`, `function_perms`)
VALUES (".$module_id.",'saveDelivery','ps_delivery','update','Update a delivery','admin,storeadmin')";
    $db->query($q); 
	echo '<p style="color:#008000">SaveDelivery function installed ... OK</p>';
}

$q="SELECT function_name FROM #__{vm}_function WHERE function_name = 'deliveryNoteAsPDF' AND module_id = ".$module_id; 
$db->query($q);
$db->next_record();
if($db->f('function_name') == 'deliveryNoteAsPDF') {
	echo '<p style="color:#008000">DeliveryNoteAsPDF function found ... OK</p>';
} else {
    $q="INSERT INTO `#__{vm}_function` (`module_id`, `function_name`, `function_class`, `function_method`, `function_description`, `function_perms`)
VALUES (".$module_id.",'deliveryNoteAsPDF','ps_delivery','pdf_output','Create pdf output','admin,storeadmin')";
    $db->query($q);
	echo '<p style="color:#008000">DeliveryNoteAsPDF function installed ... OK</p>';
}

$q="SELECT function_name FROM #__{vm}_function WHERE function_name = 'IDnREmail' AND module_id = ".$module_id; 
$db->query($q);
$db->next_record();
if($db->f('function_name') == 'IDnREmail') {
	echo '<p style="color:#008000">IDnREmail function found ... OK</p>';
} else {
    $q="INSERT INTO `#__{vm}_function` (`module_id`, `function_name`, `function_class`, `function_method`, `function_description`, `function_perms`)
VALUES (".$module_id.",'IDnREmail','ps_delivery','phocaIDnREmail','Email Sending of Invoice, Delivery note, Receipt','admin,storeadmin')";
    $db->query($q);
	echo '<p style="color:#008000">IDnREmail function installed ... OK</p>';
}

echo "<h2>Installation completed</h2>";
?>
