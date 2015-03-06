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

/****************************************************************************
 *
 * CLASS DESCRIPTION
 *
 * ps_delivery
 *
 * The class  acts as a plugin for the order_print page.
# It will add a new tab for delivery, invoice and delivery note handling.
 * Delivery and delivery note processing is handled in the ps_delivery_note.
 *
 *************************************************************************/
class ps_delivery {
	var $classname = "ps_delivery";

	/**************************************************************************
	 * name: obliterate
	 * created by: ingemar
	 * description: Obliterate selected delivery
	 * parameters: Deliver Id
	 * returns:
	 **************************************************************************/
	function obliterate( &$d ) {
		$check_items = array( "Delivery ID" => "delivery_id", "Order ID" => "order_id" );
		if( !$this->exists_and_is_numeric( $check_items, $d ) )
			return False;

		$db = new ps_DB;
		$q  = "SELECT name FROM #__users WHERE id='".$_SESSION['auth']['user_id']."'";
		$db->query($q);
		$db->next_record();

		$q = "UPDATE #__{vm}_deliveries ".
			"SET obliterated = 1, obliterated_date = ".time().", obliterated_by = '".$db->f("name")."' ".
			"WHERE delivery_id = ".$d["delivery_id"]." ".
			"AND vendor_id = ".$_SESSION["ps_vendor_id"]." ".
			"AND order_id = ".$d["order_id"];
		$db->query($q);
		$q = "UPDATE #__{vm}_delivery_item ".
			"SET obliterated = 1 ".
			"WHERE delivery_id = ".$d["delivery_id"]." ".
			"AND vendor_id = ".$_SESSION["ps_vendor_id"]." ".
			"AND order_id = ".$d["order_id"];
		$db->query($q);
		$q = "UPDATE #__{vm}_bills ".
			"SET obliterated = 1 ".
			"WHERE delivery_id = ".$d["delivery_id"]." ".
			"AND vendor_id = ".$_SESSION["ps_vendor_id"]." ".
			"AND order_id = ".$d["order_id"];
		$db->query($q);

		return True;
	}

	/**************************************************************************
	 * name: exists_and_is_numeric
	 * created by: ingemar
	 * description: Checks an array of items if they exists and is numeric
	 * parameters: Array of names and keys, Data
	 * returns:
	 **************************************************************************/
	function exists_and_is_numeric( $check, &$d ) {
		foreach( $check as $key => $value ) {
			if( isset( $d[$value] ) && !is_numeric( $d[$value] ) ) {
				$d["error"] = 'The '.$key.' '.$d[$value].' is not valid.';
				return False;
			}
			elseif( !isset( $d[$value] ) ) {
				$d["error"] = 'Missing '.$key;
				return False;
			}
		}
		return True;
	}


	/**************************************************************************
	 * name: add
	 * created by: ingemar
	 * description: Create a delivery containing selected items from the order
	 * parameters:
	 * returns:
	 **************************************************************************/ 
	function add( &$d ) {
		if(!isset($d["due"]))
			$d["due"] = 30;
		if(!isset($d["delay_interest"]))
			$d["delay_interest"] = 10;

		$check_items = array( "Order ID" => "order_id", "Due" => "due", 
				"Delay Interest" => "delay_interest" );
		if( !$this->exists_and_is_numeric( $check_items, $d ) )
			return False;

		$db = new ps_DB;
		$dbi = new ps_DB;

		$q = "SELECT SUM(product_quantity) AS 'items_total_quantity' ".
			"FROM #__{vm}_order_item ".
			"WHERE order_id = ".$d["order_id"]." ".
			"AND vendor_id = ".$_SESSION["ps_vendor_id"];
		$db->query($q);
		$db->next_record();

		$quantity_not_delivered = $db->f('items_total_quantity');

		$q = "SELECT SUM(product_quantity_delivered)  AS 'items_total_delivered' ".
			"FROM #__{vm}_delivery_item ".
			"WHERE obliterated = 0 ".
			"AND vendor_id = ".$_SESSION["ps_vendor_id"]." ".
			"AND order_id = ".$d["order_id"];
		$db->query($q);
		$db->next_record();

		$quantity_not_delivered -= $db->f('items_total_delivered');

# Check to see if we can add a delivery
		if($quantity_not_delivered == '0') 
			return True;

		$timestamp = time();
		$q = "SELECT * FROM #__{vm}_orders WHERE order_id = ".$d["order_id"]." ".
			"AND vendor_id = ".$_SESSION["ps_vendor_id"];
		$db->query($q);
		$db->next_record();
		$q = "INSERT INTO #__{vm}_deliveries SET ".
			'order_id = "'.addslashes($db->f('order_id')).'", '.
			'user_id = "'.addslashes($db->f('user_id')).'", '.
			'vendor_id = "'.addslashes($db->f('vendor_id')).'", '.
			'cdate = "'.$timestamp.'", '.
			'mdate = "'.$timestamp.'"';
		$db->query($q);
		$id = $db->last_insert_id();
		if( $id == '' ) {
			$d["error"] = '<h2>Database operation failed!</h2>';
			return False;
		}

		$q = "SELECT order_id, order_item_id, product_quantity FROM #__{vm}_order_item WHERE order_id = ".$d["order_id"];
		$db->query($q);
		while ($db->next_record()) {

			$q = "SELECT SUM(product_quantity_delivered) as delivered FROM #__{vm}_delivery_item ".
				"WHERE order_item_id = ".$db->f('order_item_id')." ".
				"AND vendor_id = ".$_SESSION["ps_vendor_id"]." ".
				"AND obliterated = 0 ".
				"AND order_id = ".$d["order_id"];
			$dbi->query($q);

			$previously_delivered = $dbi->f('delivered')!=''?$dbi->f('delivered'):0;
			$form_item_deliver_quantity = $d['order_item_'.$db->f('order_item_id')];

			// Make sure we don't get more than the product_quantity
			if( !isset($form_item_deliver_quantity) or !is_numeric($form_item_deliver_quantity) ) 
				$form_item_deliver_quantity = 0;
			elseif( $form_item_deliver_quantity < 0 )
				$form_item_deliver_quantity = 0;
			elseif( $form_item_deliver_quantity > ($db->f('product_quantity') - $previously_delivered) )
				$form_item_deliver_quantity = ($db->f('product_quantity') - $previously_delivered);


			$q = "INSERT INTO #__{vm}_delivery_item SET ".
				'delivery_id = "'.$id.'", '.
				'order_id = "'.addslashes($db->f('order_id')).'", '.
				'order_item_id = "'.addslashes($db->f('order_item_id')).'", '.
				'vendor_id = "'.$_SESSION["ps_vendor_id"].'", '.
				'product_quantity_delivered = '.$form_item_deliver_quantity;
			$dbi->query($q);

		}


		$q = "INSERT INTO #__{vm}_bills SET ".
			'order_id = "'.$d["order_id"].'", '.
			'vendor_id = "'.$_SESSION["ps_vendor_id"].'", '.
			'delivery_id = "'.$id.'", '.
			'paid = '.(isset($d["prepaid"])?1:0).', '.
			'is_invoice = '.(isset($d["invoice"])?1:0).', '.
			'due_date = '.$d["due"].', '.
			'delay_interest = '.$d["delay_interest"].', '.
			'cdate = "'.$timestamp.'", '.
			'mdate = "'.$timestamp.'"';
		$db->query($q);


		return True;
	}

	/**************************************************************************
	 * name: update
	 * created by: ingemar
	 * description: Create a delivery containing selected items from the order
	 * parameters: Delivery Id
	 * returns:
	 **************************************************************************/ 
	function update(&$d) {
		if(!isset($d["due"]))
			$d["due"] = 30;
		if(!isset($d["delay_interest"]))
			$d["delay_interest"] = 10;

		$check_items = array( "Delivery ID" => "delivery_id", "Order ID" => "order_id", 
				"Due" => "due", "Delay Interest" => "delay_interest" );

		if( !$this->exists_and_is_numeric( $check_items, $d ) )
			return False;
		$db = new ps_DB;
		$dbi = new ps_DB;

		$timestamp = time();
		$q = 'UPDATE #__{vm}_deliveries SET mdate = "'.$timestamp.'" WHERE delivery_id = '.$d['delivery_id']." ".
			"AND vendor_id = ".$_SESSION["ps_vendor_id"];
		$db->query($q);

		$q = "SELECT delivery_item_id, oi.order_item_id, product_quantity ".
			"FROM #__{vm}_order_item AS oi, #__{vm}_delivery_item AS di ".
			"WHERE oi.order_item_id = di.order_item_id ".
			"AND oi.vendor_id = di.vendor_id ".
			"AND oi.vendor_id = ".$_SESSION["ps_vendor_id"]." ".
			"AND oi.order_id = ".$d["order_id"];
		$db->query($q);
		
		while ($db->next_record()) {

			$q = "SELECT SUM(product_quantity_delivered) as delivered FROM #__{vm}_delivery_item ".
				"WHERE order_item_id = ".$db->f('order_item_id')." ".
				"AND delivery_id != ".$d["delivery_id"]." ".
				"AND obliterated = 0 ".
				"AND vendor_id = ".$_SESSION["ps_vendor_id"]." ".
				"AND order_id = ".$d["order_id"];
			$dbi->query($q);

			$previously_delivered = $dbi->f('delivered')!=''?$dbi->f('delivered'):0;
			
			if (isset($d['delivery_item_'.$db->f('delivery_item_id')])) {
				$form_item_deliver_quantity = $d['delivery_item_'.$db->f('delivery_item_id')];
			}

			// Make sure we don't get more than the product_quantity
			if( !isset($form_item_deliver_quantity) or !is_numeric($form_item_deliver_quantity) ) 
				$form_item_deliver_quantity = 0;
			elseif( $form_item_deliver_quantity < 0 )
				$form_item_deliver_quantity = 0;
			elseif( $form_item_deliver_quantity > ($db->f('product_quantity') - $previously_delivered) )
				$form_item_deliver_quantity = ($db->f('product_quantity') - $previously_delivered);


			$q = "UPDATE #__{vm}_delivery_item SET ".
				'product_quantity_delivered = '.$form_item_deliver_quantity.' '.
				'WHERE delivery_id = "'.$d["delivery_id"].'" '.
				"AND vendor_id = ".$_SESSION["ps_vendor_id"].' '.
				'AND delivery_item_id = '.$db->f('delivery_item_id');
			$dbi->query($q);
		}

		$q = "UPDATE #__{vm}_bills SET ".
			'paid = '.(isset($d["prepaid"])?1:0).', '.
			'is_invoice = '.(isset($d["invoice"])?1:0).', '.
			'due_date = '.$d["due"].', '.
			'delay_interest = '.$d["delay_interest"].', '.
			'mdate = "'.$timestamp.'"'.
			'WHERE delivery_id = '.$d['delivery_id']." ".
			"AND vendor_id = ".$_SESSION["ps_vendor_id"];            
		$db->query($q);


		return True;
	}

	/**************************************************************************
	 * name: pdf_output
	 * created by: ingemar
	 * description: Create a PDF with the Delivery Note
	 * parameters: Deliver Id
	 * returns:
	 **************************************************************************/
	function pdf_output( &$d ) {
		global  $VM_LANG, $mosConfig_live_site;
		
		$check_items = array( "Delivery ID" => "delivery_id", "Order ID" => "order_id" );
		if( !$this->exists_and_is_numeric( $check_items, $d ) )
			return False;
		if(!isset($d['gen']))
			$d['gen'] = 'delnote';

		$db = new ps_DB;
		$q  = "SELECT name FROM #__users WHERE id='".$_SESSION['auth']['user_id']."'";
		$db->query($q); 
		$db->next_record();
		$current_admin_user = $db->f("name");

		$q = "SELECT obliterated FROM #__{vm}_deliveries ".
			"WHERE order_id = ".$d['order_id']." ".
			"AND vendor_id = ".$_SESSION['ps_vendor_id']." ".
			"AND delivery_id = ".$d['delivery_id'];
		$db->query($q);
		$db->next_record();
		$obliterated = $db->f('obliterated');

		$dbo = new ps_DB;
		$q = "SELECT *, FROM_UNIXTIME(cdate, '%Y-%m-%d') as order_date ";
		$q .= "FROM #__{vm}_orders WHERE order_id='".$d['order_id']."' ";
		$q .= "AND vendor_id = '".$_SESSION['ps_vendor_id']."'";
		$dbo->query($q);
		$dbo->next_record();
		$user_id = $dbo->f("user_id");

		$dbv = new ps_DB;
		$qt = "SELECT * from #__{vm}_vendor ";
		$qt .= "WHERE vendor_id = '".$_SESSION['ps_vendor_id']."'";
		$dbv->query($qt);
		$dbv->next_record();

		$dbb = new ps_DB;
		$qt = "SELECT *, FROM_UNIXTIME(mdate, '%Y-%m-%d') as bill_date, ";
		$qt .= "FROM_UNIXTIME(mdate + (due_date * 86400), '%Y-%m-%d') as bill_due ";
		$qt .= "FROM #__{vm}_bills ";
		$qt .= "WHERE vendor_id = '".$_SESSION['ps_vendor_id']."' ";
		$qt .= "AND delivery_id = '".$d['delivery_id']."' ";
		$dbb->query($qt);
		$dbb->next_record();

		$dbbt = new ps_DB;
		$qt = "SELECT * FROM #__{vm}_user_info WHERE user_id='".$user_id."' AND address_type='BT'";
		$dbbt->query($qt);
		$dbbt->next_record();

		$dbst = new ps_DB;
		$qt = "SELECT * FROM #__{vm}_user_info WHERE user_info_id='". $dbo->f("user_info_id") . "'";
		$dbst->query($qt); 
		$dbst->next_record();


		require_once( JPATH_COMPONENT.DS.'pdf'.DS.'delivery.pdf.php' );
		PhocaDeliveryPDF::createPDF( $VM_LANG, $d, $dbv, $dbo, $dbb, $dbst, $dbbt, $current_admin_user, $obliterated);
		exit;
	}
}

?>
