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
* ps_order_delivery
*
* The class  acts as a plugin for the order_print page.
# It adds a new tab for delivery and delivery note handling.
* Delivery and delivery note processing is handled in the ps_delivery_note.
*
*************************************************************************/
class ps_order_delivery {
	var $classname = "ps_order_delivery";
	var $error;
    var $order_id;
    
	/**************************************************************************
	* name: ps_order_delivery (constructor)
	* created by: ingemar
	* description: constructor, setup initial variables
	* parameters: Order Id
	* returns:
	**************************************************************************/
    function ps_order_delivery($order_id) {
        $this->order_id = $order_id;
    }

	/**************************************************************************
	* name: pane_content
	* created by: ingemar
	* description: Show pane content
	* parameters: Tab Object
	* returns:
	**************************************************************************/
	function pane_content($tab) {
        global $VM_LANG;
      //  $tab->startTab( $VM_LANG->_VM_DELIVERY_TAB_LBL, "delivery_pane" );
        if( vmRequest::getVar ( 'delivery_pane' ) == '1') {
            ?>
<script type="text/javascript">
    var current = document.getElementById( "delivery_pane" );
    current.tabPage.select();
</script>
            <?php
        }

        $delivery_id = vmRequest::getVar ( 'delivery_id' );
        if( isset( $delivery_id ) && !is_numeric( $delivery_id ) ) {
            echo "<h2>The Delivery ID $delivery_id is not valid.</h2>";
       //     $tab->endTab();
            return;
        }
            
        // Descide what content to show in the Tab
        if( vmRequest::getVar ( 'delivery_add' ) != '' )
            $this->add_delivery();
        elseif( vmRequest::getVar ( 'delivery_edit' ) != '' ) 
            $this->edit_delivery( $delivery_id );
        else
            $this->list_deliveries(); 
    
      //  $tab->endTab();
        
    }
    	
	/**************************************************************************
	* name: edit_delivery
	* created by: ingemar
	* description: List all deliverys for a given order_id
	* parameters: 
	* returns:
	**************************************************************************/
	function edit_delivery($delivery_id) {
        global $VM_LANG, $sess;
        if( !isset( $delivery_id ) ) 
            return;

        $db = new ps_DB;
        $dbi = new ps_DB;

        ?>
        <form method="post" name="deliveryForm" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <h2><?php sprintf($VM_LANG->_('VM_DELIVERY_EDIT'), " %08d",$delivery_id) ?></h2>
        <table width="100%" border="0" cellspacing="0" cellpadding="1">
        <tr>
        <th width="10%"><?php echo $VM_LANG->_('VM_DELIVERY_DELIVER') ?></th>
        <th width="10%"><?php echo $VM_LANG->_('VM_DELIVERY_DELIVERED') ?></th>
        <th width="10%"><?php echo $VM_LANG->_('VM_DELIVERY_QUANTITY') ?></th>
        <th width="50%"><?php echo $VM_LANG->_('VM_DELIVERY_NAME') ?></th>
        <th width="20%"><?php echo $VM_LANG->_('VM_DELIVERY_SKU') ?></th>
        </tr>
        <?php
        $q = "SELECT oi.order_item_id, delivery_item_id, product_quantity_delivered, ".
                "product_quantity, order_item_name, order_item_sku ".
                "FROM #__{vm}_order_item AS oi, #__{vm}_delivery_item AS di ".
                "WHERE oi.order_item_id = di.order_item_id ".
                "AND oi.order_id = di.order_id ".
                "AND oi.vendor_id = di.vendor_id ".
                "AND oi.vendor_id = ".$_SESSION["ps_vendor_id"]." ".
                "AND oi.order_id = ".$this->order_id." ".
                "AND delivery_id = ".$delivery_id;
        $db->query($q);
		
		$i = 0;
        while ($db->next_record()) {
            if ($i++ % 2) {
                if (defined('SEARCH_COLOR_1')) {
					$bgcolor=SEARCH_COLOR_1;
				} else {
					$bgcolor='#c2c2c2';
				}
			} else {
				if (defined('SEARCH_COLOR_2')) {
					$bgcolor=SEARCH_COLOR_2;
				} else {
					$bgcolor='#ffffcc';
				}
			}
                $q = "SELECT SUM(product_quantity_delivered) as delivered FROM #__{vm}_delivery_item ".
                        "WHERE order_item_id = ".$db->f('order_item_id')." ".
                        "AND obliterated = 0 ".
                        "AND vendor_id = ".$_SESSION["ps_vendor_id"]." ".
                        "AND delivery_id != ".$delivery_id;
                $dbi->query($q);
                $dbi->next_record();
            ?>
        <tr style="background:<?php echo $bgcolor ?>">
        <td align="center"><input type="input" size="3" class="inputbox" name="<?php
                echo "delivery_item_".$db->f('delivery_item_id');
        ?>" value="<?php echo $db->f('product_quantity_delivered') ?>" /></td>
        <td align="center"><?php echo $dbi->f('delivered')!=''?$dbi->f('delivered'):0; ?></td>
        <td align="center"><?php echo $db->f('product_quantity'); ?></td>
        <td><?php echo $db->f('order_item_name'); ?></td>
        <td><?php echo $db->f('order_item_sku'); ?></td>
        </tr>
            <?php
        }
        $q = "SELECT due_date, paid, is_invoice, delay_interest FROM #__{vm}_bills ".
            "WHERE vendor_id = ".$_SESSION["ps_vendor_id"]." ".
            "AND delivery_id = ".$delivery_id;
        $dbi->query($q);
        $dbi->next_record();
        ?>
        <tr>
        <td colspan="4"><br />
        <script type="text/javascript">
            function toggle_invoice() {
                var form = document.deliveryForm;
                if(form.invoice.checked == true)  {
                    form.delay_interest.disabled = false;
                    form.due.disabled = false;
                } else {
                    form.delay_interest.disabled = true;
                    form.due.disabled = true;
                }
            }
        </script>
        <input type="checkbox" name="prepaid" value="1" 
        <?php echo $dbi->f('paid')?"checked":"" ?> /> <?php echo $VM_LANG->_('VM_DELIVERY_PREPAIED') ?><br />
        <input type="checkbox" name="invoice" value="1" onclick="toggle_invoice()" 
        <?php echo $dbi->f('is_invoice')?"checked":"" ?> /> <?php echo $VM_LANG->_('VM_DELIVERY_SEND_INVOICE') ?><br />
        <blockquote>
        <?php echo $VM_LANG->_('VM_DELIVERY_INVOICE_DUE') ?> <input type="text" name="due" size="3"
        value="<?php echo $dbi->f('due_date') ?>" <?php echo $dbi->f('is_invoice')?"":"disabled" ?>/>
        <?php echo $VM_LANG->_('VM_DELIVERY_DAYS') ?>. &nbsp; &nbsp;
        <?php echo $VM_LANG->_('VM_DELIVERY_DELAY_INTEREST') ?> <input type="text" name="delay_interest" size="4" 
        value="<?php echo $dbi->f('delay_interest') ?>" <?php echo $dbi->f('is_invoice')?"":"disabled" ?>/> %
        </blockquote>  
        </td> 
        </tr>
        </table>
		<input type="hidden" name="vmtoken" value="<?php echo vmSpoofValue($sess->getSessionId()) ?>" />
        <input type="hidden" name="delivery_pane" value="1" />
        <input type="hidden" name="page" value="order.order_print" />
        <input type="hidden" name="option" value="com_virtuemart" />
        <input type="hidden" name="order_id" value="<?php echo $this->order_id ?>" />
        <input type="hidden" name="delivery_id" value="<?php echo $delivery_id ?>" />
        <input type="hidden" name="func" value="" />
        <input type="submit" class="button" value="<?php echo $VM_LANG->_('VM_DELIVERY_CANCEL') ?>" /> &nbsp; &nbsp; &nbsp;
        <input type="submit" class="button" onclick="document.deliveryForm.func.value='saveDelivery'" 
                value="<?php echo $VM_LANG->_('VM_DELIVERY_SAVE_CHANGES') ?>" />
        </form>
        <?php
    }

	/**************************************************************************
	* name: add_delivery
	* created by: ingemar
	* description: Show insert for for a new delivery
	* parameters: Delivery Id
	* returns:
	**************************************************************************/
	function add_delivery() {
        global $VM_LANG, $sess;

        $db = new ps_DB;
        $dbi = new ps_DB;
        ?>
        <form method="post" name="deliveryForm" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <h2><?php echo $VM_LANG->_('VM_DELIVERY_ADD') ?></h2>
        <table width="100%" border="0" cellspacing="0" cellpadding="1">
        <tr>
        <th width="10%"><?php echo $VM_LANG->_('VM_DELIVERY_DELIVER') ?></th>
        <th width="10%"><?php echo $VM_LANG->_('VM_DELIVERY_DELIVERED') ?></th>
        <th width="10%"><?php echo $VM_LANG->_('VM_DELIVERY_QUANTITY') ?></th>
        <th width="50%"><?php echo $VM_LANG->_('VM_DELIVERY_NAME') ?></th>
        <th width="20%"><?php echo $VM_LANG->_('VM_DELIVERY_SKU') ?></th>
        </tr>
        <?php
		
        $q = "SELECT order_item_id, product_quantity, order_item_name, order_item_sku ".
                "FROM #__{vm}_order_item WHERE order_id = ".$this->order_id." ".
                "AND vendor_id = ".$_SESSION["ps_vendor_id"];
        $db->query($q);
        $i = 0;
		while ($db->next_record()) {
			if ($i++ % 2) {
                if (defined('SEARCH_COLOR_1')) {
					$bgcolor=SEARCH_COLOR_1;
				} else {
					$bgcolor='#c2c2c2';
				}
			} else {
				if (defined('SEARCH_COLOR_2')) {
					$bgcolor=SEARCH_COLOR_2;
				} else {
					$bgcolor='#FFFFCC';
				}
			}	
				
                $q = "SELECT SUM(product_quantity_delivered) as delivered FROM #__{vm}_delivery_item ".
                        "WHERE order_item_id = ".$db->f('order_item_id')." ".
                        "AND order_id = ".$this->order_id." ".
                        "AND obliterated = 0 ".
                        "AND vendor_id = ".$_SESSION["ps_vendor_id"];
                $dbi->query($q);
                $dbi->next_record();
                $deliver = ($db->f('product_quantity') - $dbi->f('delivered'))
            ?>
        <tr style="background:<?php echo $bgcolor ?>">
        <td align="center"><input type="input" size="3" class="inputbox" name="<?php
            echo "order_item_".$db->f('order_item_id');
        ?>" <?php
            echo !($db->f('product_quantity') - $dbi->f('delivered'))?'disabled value="0"':'value = "'.$deliver.'"';
        ?> /></td>
        <td align="center"><?php echo $dbi->f('delivered')!=''?$dbi->f('delivered'):0; ?></td>
        <td align="center"><?php echo $db->f('product_quantity'); ?></td>
        <td><?php echo $db->f('order_item_name'); ?></td>
        <td><?php echo $db->f('order_item_sku'); ?></td>
        </tr>
            <?php
        }
        ?>
        <tr>
        <td colspan="4"><br />
        <script type="text/javascript">
            function toggle_invoice() {
                var form = document.deliveryForm;
                if(form.invoice.checked == true)  {
                    form.delay_interest.disabled = false;
                    form.due.disabled = false;
                } else {
                    form.delay_interest.disabled = true;
                    form.due.disabled = true;
                }
            }
        </script>
        <input type="checkbox" name="prepaid" value="1" /> <?php echo $VM_LANG->_('VM_DELIVERY_PREPAIED') ?><br />
        <input type="checkbox" name="invoice" value="1" onclick="toggle_invoice()" /> <?php echo $VM_LANG->_('VM_DELIVERY_SEND_INVOICE') ?><br />
        <blockquote>
        <?php echo $VM_LANG->_('VM_DELIVERY_INVOICE_DUE') ?> <input type="text" name="due" size="3" value="30" disabled /> 
        <?php echo $VM_LANG->_('VM_DELIVERY_DAYS') ?>. &nbsp; &nbsp;
        <?php echo $VM_LANG->_('VM_DELIVERY_DELAY_INTEREST') ?> <input type="text" name="delay_interest" size="4" value="10" disabled /> %
        </blockquote>
        </td> 
        </tr>
        </table>
		<input type="hidden" name="vmtoken" value="<?php echo vmSpoofValue($sess->getSessionId()) ?>" />
        <input type="hidden" name="delivery_pane" value="1" />
        <input type="hidden" name="page" value="order.order_print" />
        <input type="hidden" name="option" value="com_virtuemart" />
        <input type="hidden" name="func" value="" />
        <input type="hidden" name="order_id" value="<?php echo $this->order_id ?>" />
        <input type="submit" class="button" value="<?php echo $VM_LANG->_('VM_DELIVERY_CANCEL') ?>" /> &nbsp; &nbsp; &nbsp;
        <input type="submit" class="button" onclick="document.deliveryForm.func.value='addDelivery'"
            value="<?php echo $VM_LANG->_('VM_DELIVERY_SAVE') ?>" />
        </form>
        <?php
    }

 	/**************************************************************************
	* name: list_deliveries
	* created by: ingemar
	* description: List all deliverys for a given order_id
	* parameters:
	* returns:
	**************************************************************************/
	function list_deliveries() {
        global $VM_LANG, $sess, $mosConfig_live_site, $CURRENCY_DISPLAY;
        $db = new ps_DB;
        $dbi = new ps_DB;
        
        $q = "SELECT SUM(product_quantity) AS 'items_total_quantity' ".
            "FROM #__{vm}_order_item ".
            "WHERE order_id = ".$this->order_id." ".
            "AND vendor_id = ".$_SESSION["ps_vendor_id"];
        $db->query($q);
        $db->next_record();
        
        $quantity_not_delivered = $db->f('items_total_quantity');
        
        $q = "SELECT SUM(product_quantity_delivered)  AS 'items_total_delivered' ".
            "FROM #__{vm}_delivery_item ".
            "WHERE obliterated = 0 ".
            "AND vendor_id = ".$_SESSION["ps_vendor_id"]." ".
            "AND order_id = ".$this->order_id;
        $db->query($q);
        $db->next_record();

        $quantity_not_delivered -= $db->f('items_total_delivered');

        $q = "SELECT d.delivery_id, is_invoice ".
            "FROM #__{vm}_deliveries AS d, #__{vm}_bills AS b ".
            "WHERE d.obliterated = 0 ".
            "AND d.vendor_id = b.vendor_id ".
            "AND d.order_id = b.order_id ".
            "AND d.delivery_id = b.delivery_id ".
            "AND d.vendor_id = ".$_SESSION["ps_vendor_id"]." ".
            "AND d.order_id = ".$this->order_id." ".
            "ORDER BY d.delivery_id DESC";
        $db->query($q);
        

        ?>
        <table width="100%" border="0" cellspacing="0" cellpadding="1">
        <tr>
        <td colspan="6">
        <?php
        if($quantity_not_delivered == '0')
            echo '<img src="'.$mosConfig_live_site.'/administrator/images/tick.png" />&nbsp;'.$VM_LANG->_('VM_DELIVERY_COMPLETE');
        else {
            ?>
        <form method="post" name="deliveryForm" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <img src="<?php echo $mosConfig_live_site ?>/administrator/images/publish_x.png" />&nbsp;
        <?php echo $VM_LANG->_('VM_DELIVERY_NOT_COMPLETE') ?><br /> <br />
        <input type="submit" class="button" value="<?php echo $VM_LANG->_('VM_DELIVERY_ADD') ?>" name="delivery_add" />
        <input type="hidden" name="vmtoken" value="<?php echo vmSpoofValue($sess->getSessionId()) ?>" />
		<input type="hidden" name="delivery_pane" value="1" />
        <input type="hidden" name="page" value="order.order_print" />
        <input type="hidden" name="option" value="com_virtuemart" />
        <input type="hidden" name="order_id" value="<?php echo $this->order_id ?>" />
        </form>
        <?php
        }
        ?>
        </td>
        </tr>
        <tr style="background:#ebebeb;">
	        <th width="7%" style="text-align:center;border:1px solid #ffffff"><?php echo $VM_LANG->_('VM_DELIVERY_RECEIPT') ?></th>
	        <th width="7%" style="text-align:center;border:1px solid #ffffff"><?php echo $VM_LANG->_('VM_DELIVERY_INVOICE') ?></th>
	        <th width="7%" style="text-align:center;border:1px solid #ffffff"><?php echo $VM_LANG->_('VM_DELIVERY_NOTE') ?></th>
	        <th width="30%" style="text-align:center;border:1px solid #ffffff"><?php echo $VM_LANG->_('VM_DELIVERY_NUMBER') ?></th>
	        <th width="30%" style="text-align:center;border:1px solid #ffffff"><?php echo $VM_LANG->_('VM_DELIVERY_EXTENT') ?></th>
	        <th width="9%" style="text-align:center;border:1px solid #ffffff"><?php echo $VM_LANG->_('VM_DELIVERY_VALUE')?></th>
	        <th width="10%" style="text-align:center;border:1px solid #ffffff"><?php echo $VM_LANG->_('VM_DELIVERY_OBLITERATE') ?></th>
        </tr>
        <?php
        $i = 0;       
        while ($db->next_record()) {
            if ($i++ % 2) {
                if (defined('SEARCH_COLOR_1')) {
					$bgcolor=SEARCH_COLOR_1;
				} else {
					$bgcolor='#c2c2c2';
				}
			} else {
				if (defined('SEARCH_COLOR_2')) {
					$bgcolor=SEARCH_COLOR_2;
				} else {
					$bgcolor='#FFFFCC';
				}
			}
						 
			$pdf_url= $sess->url( URL 
			. "index2.php?page=order.order_print"
			. "&func=deliveryNoteAsPDF&format=printpdf&tmpl=component"
			. "&order_id=".$this->order_id
            . "&no_menu=1&no_html&delivery_id=".$db->f('delivery_id'));
					
            $url = $sess->url( URL
			. "index.php?page=order.order_print&order_id=".$this->order_id
            . "&delivery_edit=1&delivery_id=".$db->f('delivery_id')
			. "&delivery_pane=1");
									 
            $obliterate_url = $sess->url( URL
			. "index.php?page=order.order_print&order_id=".$this->order_id
			. "&func=obliterateDelivery&delivery_id=".$db->f('delivery_id')
			. "&delivery_pane=1");
			
            $q = "SELECT SUM(product_final_price * product_quantity_delivered) as value ".
                "FROM #__{vm}_order_item AS oi, #__{vm}_delivery_item AS di ".
                "WHERE oi.vendor_id = di.vendor_id ".
                "AND oi.order_id = di.order_id ".
                "AND oi.order_item_id = di.order_item_id ".
                "AND di.delivery_id=".$db->f('delivery_id')." ".
                "AND di.vendor_id = ".$_SESSION["ps_vendor_id"]." ".
                "AND di.order_id = ".$this->order_id." ";
            $dbi->query($q);
            $dbi->next_record();
            ?>
        <tr bgcolor="<?php echo $bgcolor ?>">
        <td align="center"><?php
            if($db->f('is_invoice') == '0')
                echo '<a href="'.$pdf_url.'&gen=bill" target="_blank"><img src="'.$mosConfig_live_site.'/images/M_images/pdf_button.png" border="0" /></a>';
        ?></td>
        <td align="center"><?php
            if($db->f('is_invoice') == '1')
                echo '<a href="'.$pdf_url.'&gen=bill" target="_blank"><img src="'.$mosConfig_live_site.'/images/M_images/pdf_button.png" border="0" /></a>';
        ?></td>
        <td align="center"><a href="<?php echo $pdf_url ?>&gen=delnote" target="_blank"><img src="<?php echo $mosConfig_live_site ?>/images/M_images/pdf_button.png" border="0" /></a></td>
        <td align="center"><a href="<?php echo $url ?>"><?php printf("%08d", $db->f('delivery_id')); ?></a></td>
        <td><?php
            if( $quantity_not_delivered == '0' && $db->num_rows() == 1 )
                echo $VM_LANG->_('VM_DELIVERY_EXTENT_FULL');
            else
                echo $VM_LANG->_('VM_DELIVERY_EXTENT_PARTIAL');
        ?>
        </td>
        <td align="center"><?php echo $CURRENCY_DISPLAY->getFullValue($dbi->f('value')) ?></td>
        <td align="center">
		<?php
		
			$msg 	= sprintf ($VM_LANG->_('VM_DELIVERY_REALLY_OBLITERATE ')," %08d",$db->f('delivery_id'));
			echo '<a href="'.$obliterate_url.'"'
	        . ' onclick="return confirm(\''.$msg.'\');"' 
	        . ' onmouseout="MM_swapImgRestore();"'  
	        . ' onmouseover="MM_swapImage(\'obliterate'. $db->f('delivery_id') .'\',\'\',\''. IMAGEURL .'ps_image/delete_f2.gif\',1);">'
	        . '<img name="obliterate'. $db->f('delivery_id') .'" src="'. IMAGEURL .'ps_image/delete.gif" border="0" /></a>'
			.'</td></tr>';
        }

        $q = "SELECT d.delivery_id, d.obliterated_by, FROM_UNIXTIME(d.obliterated_date, '%M %d, %Y') as ob_date, is_invoice ".
            "FROM #__{vm}_deliveries AS d, #__{vm}_bills AS b ".
            "WHERE d.obliterated = 1 ".
            "AND d.vendor_id = b.vendor_id ".
            "AND d.order_id = b.order_id ".
            "AND d.delivery_id = b.delivery_id ".
            "AND d.vendor_id = ".$_SESSION["ps_vendor_id"]." ".
            "AND d.order_id = ".$this->order_id." ".
            "ORDER BY d.obliterated_date DESC";
        $db->query($q);
        
		while ($db->next_record()) {
            if ($i++ % 2) {
                if (defined('SEARCH_COLOR_1')) {
					$bgcolor=SEARCH_COLOR_1;
				} else {
					$bgcolor='#c2c2c2';
				}
			} else {
				if (defined('SEARCH_COLOR_2')) {
					$bgcolor=SEARCH_COLOR_2;
				} else {
					$bgcolor='#FFFFCC';
				}
			}
            $ob_date = $db->f('ob_date');
            $ob_name = $db->f('obliterated_by');
            $q = "SELECT SUM(product_final_price * product_quantity) as value ".
                "FROM #__{vm}_order_item AS oi, #__{vm}_delivery_item AS di ".
                "WHERE oi.vendor_id = di.vendor_id ".
                "AND oi.order_id = di.order_id ".
                "AND oi.order_item_id = di.order_item_id ".
                "AND di.delivery_id=".$db->f('delivery_id')." ".
                "AND di.vendor_id = ".$_SESSION["ps_vendor_id"]." ".
                "AND di.order_id = ".$this->order_id." ";
            $dbi->query($q);
            $dbi->next_record();
            
            $pdf_url= $sess->url( URL
			. "index2.php?page=order.order_print"
			. "&func=deliveryNoteAsPDF&order_id=".$this->order_id
			. "&format=printpdf&tmpl=component&no_menu=1"
			. "&no_html&delivery_id=".$db->f('delivery_id'));

            ?>
        <tr style="background:<?php echo $bgcolor ?>">
        <td align="center"><?php
            if($db->f('is_invoice') == '0')
                echo '<a href="'.$pdf_url.'&gen=bill" target="_blank"><img src="'.$mosConfig_live_site.'/images/M_images/pdf_button.png" border="0" /></a>';
        ?></td>
        <td align="center"><?php
            if($db->f('is_invoice') == '1')
                echo '<a href="'.$pdf_url.'&gen=bill" target="_blank"><img src="'.$mosConfig_live_site.'/images/M_images/pdf_button.png" border="0" /></a>';
        ?></td>
        <td align="center"><a href="<?php echo $pdf_url ?>&gen=delnote" target="_blank"><img src="<?php echo $mosConfig_live_site ?>/images/M_images/pdf_button.png" border="0" /></a></td>
        <td align="center"><strike><?php printf("%08d", $db->f('delivery_id')); ?></strike></td>
        <td><?php echo $ob_date." ".$VM_LANG->_('VM_DELIVERY_OBLITERATED_BY')." ".$ob_name ?></td>
        <td align="center"><?php echo $CURRENCY_DISPLAY->getFullValue($dbi->f('value')) ?></td>
        <td><?php echo $VM_LANG->_('VM_DELIVERY_OBLITERATED') ?></td>
        </tr>
            <?php
        }
        echo "</table>\n";
	}
}

if( vmRequest::getVar ( 'page' ) == 'order.order_print' ) {
    $ps_order_delivery = new ps_order_delivery( $order_id );
    $ps_order_delivery->pane_content( $tab );
}
?>
