<?php
/**
* @version		printpdf.php
* @package		Joomla.Framework
* @subpackage	Document
* @author		Jan Pavelka, http://www.phoca.cz
* @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

class JDocumentPrintPDF extends JDocument
{
	function __construct($options = array())
	{
		parent::__construct($options);

		//set mime type
		$this->_mime = 'application/pdf';

		//set document type
		$this->_type = 'printpdf';
		
		define('K_TCPDF_EXTERNAL_CONFIG', true);

		/*
		 * Path options
		 */

		// Installation path
		define("K_PATH_MAIN", JPATH_LIBRARIES.DS."tcpdf");

		// URL path
		define("K_PATH_URL", JPATH_BASE);

		// Fonts path
		define("FPDF_FONTPATH", JPATH_SITE.DS.'language'.DS."pdf_fonts".DS);
		define("K_PATH_FONTS", JPATH_SITE.DS.'language'.DS."pdf_fonts".DS);
		// Cache directory path
		define("K_PATH_CACHE", K_PATH_MAIN.DS."cache");

		// Cache URL path
		define("K_PATH_URL_CACHE", K_PATH_URL.DS."cache");

		// Images path
		define("K_PATH_IMAGES", K_PATH_MAIN.DS."images");

		// Blank image path
		define("K_BLANK_IMAGE", K_PATH_IMAGES.DS."_blank.png");

		/*
		 * Format options
		 */

		// Cell height ratio
		define("K_CELL_HEIGHT_RATIO", 1.25);

		// Magnification scale for titles
		define("K_TITLE_MAGNIFICATION", 1.3);

		// Reduction scale for small font
		define("K_SMALL_RATIO", 2/3);

		// Magnication scale for head
		define("HEAD_MAGNIFICATION", 1.1);

		/*
		 * Create the pdf document
		 */

		jimport('tcpdf.tcpdf');
	}

	/**
	 * Render the document.
	 *
	 * @access public
	 * @param boolean 	$cache		If true, cache the output
	 * @param array		$params		Associative array of attributes
	 * @return 	The rendered data
	 */
	function render( $cache = false, $params = array())
	{
		parent::render();
		return $this->getBuffer();
		
	}
}