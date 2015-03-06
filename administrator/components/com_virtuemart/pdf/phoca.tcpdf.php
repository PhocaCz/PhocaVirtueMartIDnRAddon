<?php
/*
 * @created For VirtueMart 1.1 created by Jan
 * @info http://www.phoca.cz/
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @based Based on Ingemar Fällman scripts
 * @copyright Copyright (C) 2006 Ingemar Fällman. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');
jimport('tcpdf.tcpdf');

class PhocaTCPDF extends TCPDF
{
	var $footerData;
	var $headerData;
	
	function setFooterData($footer)
	{
		$this->footerData = $footer;
	}
	
	function setHeaderData($header)
	{
		$this->headerData = $header;
	}

	
	function Footer()
	{
		global $VM_LANG, $mosConfig_live_site;

		$fD	= $this->footerData;
		$this->SetY(-191);
		$this->Cell(163,160,"",1,0,'L');
		$this->Ln(161);
		$this->SetFont($fD['font'],'B',9);
		$this->Cell(0,4,$VM_LANG->_('VM_DELIVERY_PRINT_VENDOR_ADDRES_LBL'),0,0,'L');
		$this->SetX(70);
		$this->Cell(0,4,$VM_LANG->_('VM_DELIVERY_PRINT_VENDOR_PHONE_LBL'),0,0,'L');
		$this->SetX(130);
		$this->Cell(0,4,$VM_LANG->_('VM_DELIVERY_PRINT_VENDOR_EMAIL_LBL'),0,0,'L');
		$this->SetFont($fD['font'],'',9);
		$this->Ln(4);
		$this->Cell(0,4,$fD['dbv']->f('vendor_store_name'),0,0,'L');
		$this->SetX(70);
		$this->Cell(0,4,$fD['dbv']->f('contact_phone_1'),0,0,'L');
		$this->SetX(130);
		$this->Cell(0,4,$fD['dbv']->f('contact_email'),0,0,'L');
		$this->Ln(4);
		$this->Cell(0,4,$fD['dbv']->f('vendor_address_1'),0,0,'L');
		$this->SetX(130);
		$this->SetFont($fD['font'],'B',9);
		$this->Cell(0,4,$VM_LANG->_('VM_DELIVERY_PRINT_VENDOR_URL_LBL'),0,0,'L');
		
		if($VM_LANG->_('VM_DELIVERY_VENDOR_VAT_NR1_VALUE') && $VM_LANG->_('VM_DELIVERY_VENDOR_VAT_NR1_VALUE') != '') { 
			$this->SetX(70);
			$this->SetFont($fD['font'],'',9);
			$this->Cell(0,4,$VM_LANG->_('VM_DELIVERY_VENDOR_VAT_NR1_LABEL') . ': '. $VM_LANG->_('VM_DELIVERY_VENDOR_VAT_NR1_VALUE'),0,0,'L');
		}
		
		$this->Ln(4);
		$this->SetFont($fD['font'],'',9);
		$this->Cell(0,4,$fD['dbv']->f('vendor_zip'). ' '. $fD['dbv']->f('vendor_city'),0,0,'L');
		
		if($VM_LANG->_('VM_DELIVERY_VENDOR_VAT_NR2_VALUE') && $VM_LANG->_('VM_DELIVERY_VENDOR_VAT_NR2_VALUE') != '') { 
			$this->SetX(70);
			$this->Cell(0,4,$VM_LANG->_('VM_DELIVERY_VENDOR_VAT_NR2_LABEL') . ': '. $VM_LANG->_('VM_DELIVERY_VENDOR_VAT_NR2_VALUE'),0,0,'L');
		}
		$this->SetX(130);
		$this->Cell(0,4,$mosConfig_live_site,0,0,'L');
		$this->Ln(4); 
		if($fD['vmVCountry'] != '') { 
			$this->Cell(0,4,$fD['vmVCountry'],0,0,'L');
		}
		 
		
		
		// let's set pagenumber when we're at it
		$this->SetY(280);
		$this->SetX(-40);
		$this->SetFont($fD['font'],'I',8);
		$this->SetTextColor(102, 102, 102, false);
		$this->Cell(0,10,$VM_LANG->_('VM_DELIVERY_PRINT_PAGE_LBL'). ' ' .$this->PageNo().'/{nb}',0,0,'R');
		$this->SetTextColor(0,0,0, false);
	
	}
	
	function Header()
	{
		global $VM_LANG, $mosConfig_live_site;
		
		$hD	= $this->headerData;
		$this->Image($hD['logo'],20,15,70);
		$this->SetX(-107);
		$this->SetFont($hD['font'],'B',15);
		$this->Cell(80,10, $hD['vmTitle'],1,0,'L');
		$this->Ln(10);
		$this->SetX(-107);
		

		$this->SetFont($hD['font'],'B',9);
		if($hD['d']['gen'] == 'bill') {
			if($VM_LANG->_('VM_DELIVERY_PRINT_BILL_NUMBER_LBL') != '') {
						$this->Cell(24,5,$VM_LANG->_('VM_DELIVERY_PRINT_BILL_NUMBER_LBL'),0,0,'C');
			} else {
						$this->Cell(24,5,$VM_LANG->_('VM_DELIVERY_PRINT_DATE_LBL'),0,0,'L');
			}
		} else {
			$this->Cell(24,5,$VM_LANG->_('VM_DELIVERY_PRINT_ORDER_DATE_LBL'),0,0,'L');
		}
		$this->Cell(28,5,$VM_LANG->_('VM_DELIVERY_PRINT_ORDER_NUMBER_LBL'),0,0,'C');
		$this->Cell(28,5,$VM_LANG->_('VM_DELIVERY_PRINT_DELNOTE_NUMBER_LBL'),0,0,'C');
		$this->Ln(4);
		$this->SetX(-107);
		$this->SetFont($hD['font'],'',9);
		if($hD['d']['gen'] == 'bill') {
					$this->Cell(24,5,$hD['dbb']->f('bill_id'),0,0,'C');
		} else {
					$this->Cell(24,5,$hD['dbo']->f('order_date'),0,0,'L');
		}
		$this->Cell(28,5,$hD['dbo']->f("order_id"),0,0,'C');
		$this->Cell(28,5,$hD['d']['delivery_id'],0,0,'C');
		if($hD['d']['gen'] == 'bill') {
					$this->Ln(4);
					$this->SetX(-107);
					$this->SetFont($hD['font'],'B',9);
					$this->Cell(26,4,$VM_LANG->_('VM_DELIVERY_PRINT_BILL_DATE_LBL'),0,0,'C');
					$this->Cell(27,4,$VM_LANG->_('VM_DELIVERY_PRINT_ORDER_DATE_LBL'),0,0,'C');
					if($hD['dbb']->f('is_invoice')) {
						$this->Cell(27,4,$VM_LANG->_('VM_DELIVERY_PRINT_DUE_DATE_LBL'),0,0,'C');
					}
					$this->Ln(4);
					$this->SetX(-107); 
					$this->SetFont($hD['font'],'',9);
					$this->Cell(26,4,$hD['dbb']->f('bill_date'),0,0,'C');
					$this->Cell(27,4,$hD['dbo']->f('order_date'),0,0,'C');
					if($hD['dbb']->f('is_invoice')) {
						$this->Cell(27,4,$hD['dbb']->f('bill_due'),0,0,'C');
					}
					$this->Ln(6);
		} else {    
					$this->Ln(14);
		}

		// begin ship to
		$ship_to_X = $this->GetX();
		$ship_to_Y = $this->GetY();
		$this->Cell(80,33,"",1);
		$this->SetY(($ship_to_Y + 3));
		$this->SetFont($hD['font'],'B',9);
		$this->Cell(0,0,$VM_LANG->_('VM_DELIVERY_PRINT_SHIP_TO_LBL'),0,0,'L');
		$this->SetFont($hD['font'],'',9);
		$this->Ln(4);
		$this->SetX(($ship_to_X + 3));
		if($hD['dbst']->f("company") != '') {
					$this->Cell(0,0,$hD['dbst']->f("company"),0,0,'L');
					$this->Ln(4);
					$this->SetX(($ship_to_X + 3));
		}
		$this->Cell(0,0,$hD['dbst']->f("first_name") . $hD['dbst']->f("middle_name").$hD['dbst']->f("last_name"),0,0,'L');
		$this->Ln(4);
		$this->SetX(($ship_to_X + 3));
		$this->Cell(0,0,$hD['dbst']->f("address_1"),0,0,'L');
		$this->Ln(4);
		$this->SetX(($ship_to_X + 3));
		if($hD['dbst']->f("address_2") != '') {
					$this->Cell(0,0,$hD['dbst']->f("address_2"),0,0,'L');
					$this->Ln(4);
					$this->SetX(($ship_to_X + 3));
		}
		$this->Cell(0,0, $hD['dbst']->f("zip") .' '. $hD['dbst']->f("city"),0,0,'L');
		$this->Ln(4);
		$this->SetX(($ship_to_X + 3));
		if($hD['dbst']->f("state") != '') {
					$this->Cell(0,0,$hD['dbst']->f("state"),0,0,'L');
					$this->Ln(4);
					$this->SetX(($ship_to_X + 3));
		}

		$this->Cell(0,0,$hD['vmSTCountry'],0,0,'L');

		// return to start position
		$this->SetX($ship_to_X);
		$this->SetY($ship_to_Y);


		// begin bill to
		$this->SetX(-107);
		$bill_to_X = $this->GetX();
		$bill_to_Y = $this->GetY();
		$this->Cell(80,33,'',1);
		$this->SetY($bill_to_Y + 3);
		$this->SetX(-107);
		$this->SetFont($hD['font'],'B',9);
		$this->Cell(0,0,$VM_LANG->_('VM_DELIVERY_PRINT_BILL_TO_LBL'),0,0,'L');
		$this->SetFont($hD['font'],'',9);
		
		$this->Ln(4);
		$this->SetX(-104);
		if($hD['dbbt']->f("company") != '') {
					$this->Cell(0,0,$hD['dbbt']->f("company"),0,0,'L');
					$this->Ln(4);
					$this->SetX(-104);
		}
		$this->Cell(0,0,$hD['dbbt']->f("first_name")." ".$hD['dbbt']->f("middle_name")." ".$hD['dbbt']->f("last_name"),0,1,'L');
		
		// VAT extra field
		if($hD['dbbt']->f("extra_field_1") && $hD['dbbt']->f("extra_field_1") != '') { 
			$this->SetX(150);
			$this->SetFont($hD['font'],'',9);
			$this->Cell(0,0,$VM_LANG->_('PHPSHOP_SHOPPER_FORM_EXTRA_FIELD_1') . ': '. $hD['dbbt']->f("extra_field_1"),0,0,'L');
		}
		
		$this->Ln(4);
		$this->SetX(-104);
		$this->Cell(0,0,$hD['dbbt']->f("address_1"),0,0,'L');
		
		// VAT extra field
		if($hD['dbbt']->f("extra_field_2") && $hD['dbbt']->f("extra_field_2") != '') { 
			$this->SetX(150);
			$this->SetFont($hD['font'],'',9);
			$this->Cell(0,0,$VM_LANG->_('PHPSHOP_SHOPPER_FORM_EXTRA_FIELD_2') . ': '. $hD['dbbt']->f("extra_field_2"),0,0,'L');
		}
		
		
		$this->Ln(4);
		$this->SetX(-104);
		if($hD['dbbt']->f("address_2") != '') {
					$this->Cell(0,0,$hD['dbbt']->f("address_2"),0,0,'L');
					$this->Ln(4);
					$this->SetX(-104);
		}
		$this->Cell(0,0,$hD['dbbt']->f("zip") . ' ' . $hD['dbbt']->f("city"),0,0,'L');
		$this->Ln(4);
		$this->SetX(-104);
		if($hD['dbbt']->f("state") != '') {
					$this->Cell(0,0,$hD['dbbt']->f("state"),0,0,'L');
					$this->Ln(4);
					$this->SetX(-104);
		}
		$this->Cell(0,0,$hD['vmBTCountry'],0,0,'L');

		// return to start position and make a huge newline
		$this->SetX($ship_to_X);
		$this->SetY($ship_to_Y);
		$this->Ln(34);

		$this->SetFont($hD['font'],'B',9);
		$this->Cell(80,4,$VM_LANG->_('VM_DELIVERY_PRINT_YOUR_REF_LBL'),0,0,'L');
		$this->SetX(-107);
		$this->Cell(80,4,$VM_LANG->_('VM_DELIVERY_PRINT_OUR_REF_LBL'),0,0,'L');
		$this->Ln(4);
		$this->SetFont($hD['font'],'',9); 
		$this->Cell(80,4,$hD['dbbt']->f("first_name")." ".$hD['dbbt']->f("middle_name")." ".$hD['dbbt']->f("last_name"),0,0,'L');
		$this->SetX(-107);
		$this->Cell(80,4,$hD['currentAdminUser'],0,0,'L');
		$this->Ln(5);
		$this->SetFont($hD['font'],'B',9);
		$this->Cell(80,4,$VM_LANG->_('VM_DELIVERY_PRINT_SHIPPING_CARRIER_LBL'),0,0,'L');
		$this->SetX(-107);
		$this->Cell(80,4,$VM_LANG->_('VM_DELIVERY_PRINT_BILL_TERMS_LBL'),0,0,'L');
		$this->Ln(4);
		$this->SetFont($hD['font'],'',9);
		if (isset($hD['details'][1])) {
			$this->Cell(80,4,$hD['details'][1],0,0,'L');
		} else {
			$this->Cell(80,4,'',0,0,'L');
		}
		$this->SetX(-107);
		if($hD['dbb']->f('is_invoice')) {
			if ($hD['dbb']->f('paid') == 0) {
				$this->Cell(80,4,$hD['dbb']->f('due_date')." ".$VM_LANG->_('VM_DELIVERY_DAYS'),0,0,'L');
			} else {
				$this->Cell(80,4,'-',0,0,'L');
			}
		}
		$this->Ln(5);
		$this->SetFont($hD['font'],'B',9);
		$this->Cell(80,4,$VM_LANG->_('VM_DELIVERY_PRINT_SHIPPING_MODE_LBL'),0,0,'L');
		$this->SetX(-107);
		$this->Cell(80,4,$VM_LANG->_('VM_DELIVERY_PRINT_OVERDUE_INTEREST_LBL'),0,0,'L'); 
		$this->Ln(4);
		$this->SetFont($hD['font'],'',9);
		if (isset($hD['details'][2])) {
			$this->Cell(80,4,$hD['details'][2],0,0,'L');
		} else {
			$this->Cell(80,4,'',0,0,'L');
		}
		$this->SetX(-107);
		if ($hD['dbb']->f('paid') == 0) {
			$this->Cell(80,4,$hD['dbb']->f('delay_interest') . ' %',0,0,'L');
		} else {
			$this->Cell(80,4,'-',0,0,'L');
		}
		$this->Ln(7); 
		$this->Cell(163,5,"",1,0,'L');
		$this->Ln(0);
		
		// Fields
		// SKU
		$this->SetFont($hD['font'],'',6);
		$this->Cell(15,5,$VM_LANG->_('VM_DELIVERY_PRINT_SKU_LBL'),0,0,'L');
		
		
		if($hD['d']['gen'] == 'bill') {
					$this->Cell(70,5,$VM_LANG->_('VM_DELIVERY_PRINT_PRODUCT_LBL'),0,0,'L');
					$this->Cell(7,5,$VM_LANG->_('VM_DELIVERY_PRINT_QUANTITY_LBL'),0,0,'C');
				//	$this->Cell(5,5,$VM_LANG->_('VM_DELIVERY_PRINT_DELIVERED_LBL'),0,0,'C');
				//	$this->Cell(10,5,$VM_LANG->_('VM_DELIVERY_PRINT_REMAINING_LBL'),0,0,'C');
					$this->Cell(18,5,$VM_LANG->_('VM_DELIVERY_PRINT_UNIT_PRICE_LBL'),0,0,'C');
					$this->Cell(19,5,$VM_LANG->_('VM_DELIVERY_PRINT_TOTAL_PRICE_WITHOUT_TAX_LBL'),0,0,'C');
					$this->Cell(12,5,$VM_LANG->_('VM_DELIVERY_PRINT_TAX_LBL'),0,0,'C');
					$this->Cell(19,5,$VM_LANG->_('VM_DELIVERY_PRINT_TOTAL_PRICE_LBL'),0,0,'C');
		} else {
					$this->Cell(70,5,$VM_LANG->_('VM_DELIVERY_PRINT_PRODUCT_LBL'),0,0,'L');
					$this->Cell(10,5,$VM_LANG->_('VM_DELIVERY_PRINT_QUANTITY_LBL'),0,0,'C');
					$this->Cell(10,5,$VM_LANG->_('VM_DELIVERY_PRINT_DELIVERED_LBL'),0,0,'C');
					$this->Cell(20,5,$VM_LANG->_('VM_DELIVERY_PRINT_REMAINING_LBL'),0,0,'C');
		}
		$this->Ln(5);
		$this->SetAutoPageBreak(true,40);
		
		
		if (isset($hD['obliterated']) && (int)$hD['obliterated'] == 1) {
		
			$ship_to_X = $this->GetX();
			$ship_to_Y = $this->GetY();
			$this->SetDrawColor(180);
			$this->SetTextColor(180);
			$this->SetFont($hD['font'],'B',46);
			$this->SetY(-150);
			$this->SetX(40);
			$this->Cell(120,25,$VM_LANG->_('VM_DELIVERY_PRINT_OBLITERATED_LBL'),1,0,'C');
			$this->SetDrawColor(0);
			$this->SetTextColor(0);
			$this->SetX($ship_to_X);
			$this->SetY($ship_to_Y);
		}
	
	}
}
?>