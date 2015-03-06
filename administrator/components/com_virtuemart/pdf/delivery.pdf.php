<?php
/*
 * @created For VirtueMart 1.1 created by Jan Pavelka
 * @info http://www.phoca.cz/
 * @copyright Copyright (C) 2008 Jan Pavelka. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @copyright Copyright (C) 2006 Ingemar Fällman. All rights reserved.
 * @copyright Copyright (C) 2004-2005 Soeren Eberhardt. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 */
require_once( JPATH_COMPONENT.DS.'pdf'.DS.'phoca.tcpdf.php' );
jimport('joomla.application.component.view');

class PhocaDeliveryPDF extends JView
{
	function createPDF( $VM_LANG, $d, $dbv, $dbo, $dbb, $dbst, $dbbt, $current_admin_user, $obliterated, $doc = '' )
	{
		global $CURRENCY_DISPLAY, $mosConfig_live_site;
// -----------------------------------------------------------------------------------------------------------	
$logo = IMAGEPATH . "vendor/". $dbv->f("vendor_full_image");

$details = explode( "|", $dbo->f("ship_method_id") );
if($dbb->f('is_invoice') == '1' && $d['gen'] == 'bill') {
	$vmTitle	= $VM_LANG->_('VM_DELIVERY_INVOICE');
} else if($dbb->f('is_invoice') == '0' && $d['gen'] == 'bill') {
	$vmTitle	= $VM_LANG->_('VM_DELIVERY_RECEIPT');
} else {
	$vmTitle	= $VM_LANG->_('VM_DELIVERY_NOTE');
}

if($dbv->f("vendor_country") != $dbst->f("country")) {
	$vmSTCountry	= $dbst->f("country");
} else {
	$vmSTCountry	= '';
}

if($dbv->f("vendor_country") != $dbbt->f("country")) {
	$vmBTCountry	= $dbbt->f("country");
} else {
	$vmBTCountry	= '';
}

if($dbv->f("vendor_country")) {
	$vmVCountry	= $dbv->f("vendor_country");
} else {
	$vmVCountry	= '';
}
if($dbv->f("vendor_state")) {
	$vmVState	= $dbv->f("vendor_state");
} else {
	$vmVState	= '';
}

// PDF SETTING
$pdfFont	= 'FreeSans';

// Set Header Data
$header['logo']				= $logo;
$header['vmTitle']			= $vmTitle;
$header['dbb']				= $dbb;
$header['dbo']				= $dbo;
$header['d']				= $d;
$header['dbst']				= $dbst;
$header['dbbt']				= $dbbt;
$header['dbv']				= $dbv;
$header['font']				= $pdfFont;
$header['vmBTCountry']		= $vmBTCountry;
$header['vmSTCountry']		= $vmSTCountry;
$header['currentAdminUser']	= $current_admin_user;
$header['details']			= $details;
$header['obliterated']		= $obliterated;

//krumo($header);exit;
// Set Footer Data
$footer['dbv']				= $dbv;
$footer['font']				= 'FreeSans';
$footer['vmBTCountry']		= $vmBTCountry;
$footer['vmVCountry']		= $vmVCountry;
$footer['vmVState']			= $vmVState;


$pdf = new PhocaTCPDF( "P", "mm", "A4", true );
$pdf->setHeaderData($header);
$pdf->setFooterData($footer);
$pdf->SetMargins("20.0","15.0");
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont( $pdfFont, "", 12 );


// Rows
$db 						= new ps_DB;
$without_tax 				= array();
$tax 						= array();
$with_tax 					= array();
$without_tax['item_total'] 	= 0;
$with_tax['item_total']		= 0;
$tax['item_total']			= 0;

$q = "SELECT di.order_item_id, product_quantity_delivered, product_quantity, ".
	"order_item_name, order_item_sku, product_final_price, product_item_price, ".
	"product_attribute FROM #__{vm}_order_item AS oi, #__{vm}_delivery_item AS di ".
	"WHERE oi.order_item_id = di.order_item_id ".
	"AND oi.vendor_id = di.vendor_id ".
	"AND oi.order_id = di.order_id ".
	"AND oi.order_id = ".$d['order_id']." ".
	"AND oi.vendor_id = ".$_SESSION["ps_vendor_id"]." ".
	"AND delivery_id = ".$d['delivery_id'];
$db->query($q);

while ($db->next_record()) {
	// reuse dbv
	$dbv2 = new ps_DB;
	$q = "SELECT SUM(product_quantity_delivered) as delivered FROM #__{vm}_delivery_item ".
		"WHERE vendor_id = ".$_SESSION["ps_vendor_id"]." ".
		"AND order_item_id = ".$db->f('order_item_id');
	$dbv2->query($q);
	$dbv2->next_record();

	$remaining = $db->f('product_quantity') - $dbv2->f('delivered');

	$price_with_tax 	= array();
	$price_without_tax 	= array();
	$price_tax 			= array();
	$displayed_price 	= array();
	

	if($d['gen'] == 'bill') {

		$price_with_tax['item'] = $db->f('product_final_price');
		$price_with_tax['sum'] = $db->f('product_final_price') * $db->f('product_quantity_delivered');
		$price_without_tax['item'] = $db->f('product_item_price');
		$price_without_tax['sum'] = $db->f('product_item_price') * $db->f('product_quantity_delivered');
		$price_tax['item'] = $price_with_tax['item'] - $price_without_tax['item'];
		$price_tax['sum'] = $price_with_tax['sum'] - $price_without_tax['sum'];

		$without_tax['item_total'] += $price_without_tax['sum'];
		$with_tax['item_total'] += $price_with_tax['sum'];
		$tax['item_total'] += $price_tax['sum'];

		if( isset($auth["show_price_including_tax"]) )
			$displayed_price = $price_with_tax;
		else 
			$displayed_price = $price_without_tax;
	} 

	// PDF
	
	// SKU
	$pdf->SetFont($pdfFont,'',7);
	$pdf->Cell(15,5,$db->f('order_item_sku'),0,0,'L');
	
	if($d['gen'] == 'bill') {
		
		$pdf->Cell(70,5,$db->f('order_item_name'),0,0,'L');
		$pdf->Cell(7,5,$db->f('product_quantity'),0,0,'C');
	//	$pdf->Cell(8,5,$db->f('product_quantity_delivered'),0,0,'C');
	//	$pdf->Cell(10,5,$remaining,0,0,'C');
		$pdf->Cell(18,5,$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($price_without_tax['item'])),0,0,'R');
		
		$pdf->Cell(19,5,$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($price_without_tax['sum'])),0,0,'R');
		
		$pdf->Cell(12,5,$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($price_tax['sum'])),0,0,'R');
		
		$pdf->Cell(19,5,$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($price_with_tax['sum'])),0,0,'R');
	} else {
		$pdf->Cell(70,5,$db->f('order_item_name'),0,0,'L');
		$pdf->Cell(10,5,$db->f('product_quantity'),0,0,'C');
		$pdf->Cell(10,5,$db->f('product_quantity_delivered'),0,0,'C');
		$pdf->Cell(20,5,$remaining,0,0,'C');
	}
	$pdf->Ln(5);

	if(!empty($attribute)) {
		$pdf->Cell(30,5,'',0,0,'L');
		$pdf->Cell(130,5,"(".$db->f('product_attribute').")",0,0,'L');
		$pdf->Ln(5);
	}
}

// Sumarize
if($d['gen'] == 'bill') {

	$db2 = new ps_DB;
	$q = "SELECT MIN(delivery_id) as delivery_id FROM #__{vm}_deliveries ".
		"WHERE order_id = ".$d['order_id']." ".
		"AND obliterated = 0 ".
		"AND vendor_id = ".$_SESSION['ps_vendor_id'];
	$db2->query($q);
	$db2->next_record();

	// Add shipping costs to the fist delivery of the order
	if($db2->f('delivery_id') == $d['delivery_id']) {
		$without_tax['shipping'] = $dbo->f('order_shipping');
		$tax['shipping'] = $dbo->f('order_shipping_tax');
		$with_tax['shipping'] = ($dbo->f('order_shipping') + $dbo->f('order_shipping_tax'));
		if($dbo->f("order_discount") < 0) {
			$order_fee = abs($dbo->f("order_discount"));
		} else {
			$order_fee = 0;
		}
	} else {
		$without_tax['shipping'] = 0;
		$tax['shipping'] = 0;
		$with_tax['shipping'] = 0;
		$order_fee = 0; 
	}

	// find out what procentage of the order this delivery is
	$db3 = new ps_DB;
	$q = "SELECT order_subtotal ".
		"FROM #__{vm}_orders ".
		"WHERE order_id = ".$d['order_id']." ".
		"AND vendor_id = ".$_SESSION['ps_vendor_id'];
	$db3->query($q);
	$db3->next_record();
	$order_value_procentage = $without_tax['item_total'] / $db3->f('order_subtotal');

	$order_discount = 0;
	if($dbo->f("order_discount") > 0) {
		$order_discount = $dbo->f("order_discount") * $order_value_procentage;
	}
	$coupon_discount = 0;
	if($dbo->f("coupon_discount") > 0) {
		$coupon_discount = $dbo->f("coupon_discount") * $order_value_procentage;
	}

	$without_tax['final_price'] = $without_tax['item_total'] - $coupon_discount - $order_discount + $order_fee;
	$tax_rate = abs($without_tax['item_total']/$with_tax['item_total']-1);
	$tax['final_price'] = ($with_tax['item_total'] - $order_discount - $coupon_discount + $order_fee) * $tax_rate;
	
	$tax['final_price_without_shipping'] = $tax['item_total'];
	
	$tax['final_price'] += $tax['shipping'];
	$with_tax['final_price'] = $with_tax['item_total'] - $coupon_discount - $order_discount + $order_fee;;

	if($without_tax['final_price'] < 0)
		$without_tax['final_price'] = 0;
	if($with_tax['final_price'] < 0)
		$with_tax['final_price'] = 0;

	$without_tax['final_price'] += $without_tax['shipping'];
	$with_tax['final_price'] += $with_tax['shipping'];

	if($dbb->f('paid')) {
		$without_tax['to_pay'] = 0;
		$with_tax['to_pay'] = 0;
	} else {
		$without_tax['to_pay'] = $without_tax['final_price'];
		$with_tax['to_pay'] = $with_tax['final_price'];
	}

	
	$pdf->Ln(5);
	$pdf->SetX(110);
	$pdf->SetFont($pdfFont,'B',9);
	$pdf->Cell(40,5,$VM_LANG->_('VM_DELIVERY_PRINT_SUBTOTAL_LBL').' :',0,0,'R');
	$pdf->SetFont($pdfFont,'',9);
	$pdf->Cell(30,5,$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($without_tax['item_total'])),0,0,'R');
	
	$pdf->Ln(5);
	$pdf->SetX(110);
	$pdf->SetFont($pdfFont,'B',9);
	$pdf->Cell(40,5,$VM_LANG->_('VM_DELIVERY_PRINT_TAX_LBL') .' :',0,0,'R');
	$pdf->SetFont($pdfFont,'',9);
	$pdf->Cell(30,5,$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($tax['final_price_without_shipping'])),0,0,'R');
	
	$pdf->Ln(5);
	$pdf->SetX(110);
	
	if($with_tax['shipping'] > 0) {
		
		$pdf->SetFont($pdfFont,'B',9);
		$pdf->Cell(40,5,$VM_LANG->_('VM_DELIVERY_PRINT_SHIPPING_LBL').' :',0,0,'R');
		$pdf->SetFont($pdfFont,'',9);
		$pdf->Cell(30,5,$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($without_tax['shipping'])),0,0,'R');
		$pdf->Ln(5);
		$pdf->SetX(110);
		
		$pdf->SetFont($pdfFont,'B',9);
		$pdf->Cell(40,5,$VM_LANG->_('VM_DELIVERY_PRINT_SHIPPING_TAX_LBL') .' :',0,0,'R');
		$pdf->SetFont($pdfFont,'',9);
		$pdf->Cell(30,5,$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($tax['shipping'])),0,0,'R');
		$pdf->Ln(5);
		$pdf->SetX(110);
		
	}
	
	if($coupon_discount > 0) {
		$pdf->SetFont($pdfFont,'B',9);
		$pdf->Cell(40,5,$VM_LANG->_('VM_DELIVERY_PRINT_COUPON_DISCOUNT_LBL').' :',0,0,'R');
		$pdf->SetFont($pdfFont,'',9);
		$pdf->Cell(30,5," - ".$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($coupon_discount)),0,0,'R');
		$pdf->Ln(5);
		$pdf->SetX(110);
	}
	if($order_discount > 0) {
		$pdf->SetFont($pdfFont,'B',9);
		$pdf->Cell(40,5,$VM_LANG->_('VM_DELIVERY_PRINT_DISCOUNT_LBL') .' :',0,0,'R');
		$pdf->SetFont($pdfFont,'',9);
		$pdf->Cell(30,5," - ".$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($order_discount)),0,0,'R');
		$pdf->Ln(5);
		$pdf->SetX(110);
	}
	if($order_fee > 0) {
		$pdf->SetFont($pdfFont,'B',9);
		$pdf->Cell(40,5,$VM_LANG->_('VM_DELIVERY_PRINT_FEE_LBL').' :',0,0,'R');
		$pdf->SetFont($pdfFont,'',9);
		$pdf->Cell(30,5,"".$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($order_fee)),0,0,'R');
		$pdf->Ln(5);
		$pdf->SetX(110);
	}
	
	
	$pdf->SetFont($pdfFont,'B',9);
	$pdf->Cell(40,5,$VM_LANG->_('VM_DELIVERY_PRINT_TOTAL_LBL') .' :',0,0,'R');
	$pdf->SetFont($pdfFont,'',9);
	$pdf->Cell(30,5,$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($with_tax['final_price'])),0,0,'R');
	$pdf->Ln(7);
	$pdf->SetX(112);
	$pdf->SetFillColor(240);
	$pdf->Cell(70,10,"",0,0,'C',1);
	$pdf->Ln(3);
	$pdf->SetX(110);
	$pdf->SetFont($pdfFont,'B',11);
	$pdf->Cell(40,5,$VM_LANG->_('VM_DELIVERY_PRINT_TO_PAY_LBL') .' :',0,0,'R');
	$pdf->Cell(30,5,$pdf->unhtmlentities($CURRENCY_DISPLAY->getFullValue($with_tax['to_pay'])),0,0,'R');
	if($dbb->f('is_invoice')) {
		$pdf->Ln(-5);
		$pdf->SetX(20);
		$pdf->SetFont($pdfFont,'B',11);
		$pdf->Cell(50,10,$VM_LANG->_('VM_DELIVERY_PRINT_SIGNED_LBL')  .' : _ _ _ _ _ _ _ _ _',0,0,'L');
	} else {
		$pdf->Ln(10);
		$pdf->SetX(20);
		$pdf->SetFont($pdfFont,'B',10);
		$pdf->Cell(160,10,sprintf($VM_LANG->_('VM_DELIVERY_PRINT_INVOICE_INFO'),$dbb->f('bill_id')),0,0,'C');
	}

}

// Rendering the PDF - render into a browser or render into a doc file

if ($doc =='') {
	$pdf->Output( 'delivery.pdf', "I" );
} else {
	$pdf->Output( $doc, "F" );
	return true;
}
// -----------------------------------------------------------------------------------------------------------

	}
}
?>