<?php 
define( '_VALID_MOS', 1 );
define( '_JEXEC', 1 );
//if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
/**
 * Virtuemart Categorie SOA Connector
 *
 * THis file generate wsdl dynamicly whith good <soap:address location = ....
 *
 * @package    mod_vm_soa
 * @subpackage classes
 * @author     Mickael cabanas (cabanas.mickael|at|gmail.com)
 * @copyright  2010 Mickael Cabanas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @version    $Id:$
 */

ob_start();//to prevent some bad users change codes 

 /** loading framework **/
include_once('VM_Commons.php');

/** WSDL file name to load**/
$wsdlFilename = $vmConfig->get('soap_wsdl_sql')!= "" ? $vmConfig->get('soap_wsdl_sql') : WSDL_SQL;

$string = file_get_contents($wsdlFilename,"r");
$wsdlReplace = $string;

//Get URL + BASE From Joomla conf
if (empty($conf['BASESITE']) && empty($conf['URL']) ){
	//$wsdlReplace = str_replace('http://___HOST___/___BASE___/', $URL_BASE, $wsdlReplace);
	$wsdlReplace = str_replace('http://___HOST___/___BASE___/administrator/components/com_virtuemart/services/', JURI::root(false), $wsdlReplace);
}
// Else Get URL + BASE form SOA For VM Conf
else if (empty($conf['BASESITE']) && !empty($conf['URL'])){
	$wsdlReplace = str_replace("___HOST___", $conf['URL'], $string);
	$wsdlReplace = str_replace("___BASE___/", $conf['BASESITE'], $wsdlReplace);
} else {
	$wsdlReplace = str_replace("___HOST___", $conf['URL'], $string);
	$wsdlReplace = str_replace("___BASE___", $conf['BASESITE'], $wsdlReplace);
}

$serviceFilename = $vmConfig->get('soap_EP_sql')!= "" ? $vmConfig->get('soap_EP_sql') : SERVICE_SQL;
$wsdlReplace = str_replace("___SERVICE___", $serviceFilename, $wsdlReplace);

ob_end_clean();//to prevent some bad users change code 

if ($vmConfig->get('soap_ws_sql_on')==1){
	header('Content-type: text/xml; charset=UTF-8'); 
	header("Content-Length: ".strlen($wsdlReplace));
	echo $wsdlReplace;
}
else{
	echoXmlMessageWSDisabled('SQL Queries');
	
}
?>