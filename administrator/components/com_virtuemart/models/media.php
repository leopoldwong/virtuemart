<?php
/**
 *
 * Description
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved by the author.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id$
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the model framework
jimport( 'joomla.application.component.model');

if(!class_exists('VmModel'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmmodel.php');

/**
 * Model for VirtueMart Product Files
 *
 * @package		VirtueMart
 */
class VirtueMartModelMedia extends VmModel {

	/**
	 * constructs a VmModel
	 * setMainTable defines the maintable of the model
	 * @author Max Milbers
	 */
	function __construct() {
		parent::__construct('virtuemart_media_id');
		$this->setMainTable('medias');

	}

	/**
	 * Gets a single media by virtuemart_media_id
	 * .
	 * @param string $type
	 * @param string $mime mime type of file, use for exampel image
	 * @return mediaobject
	 */
	function getFile($type=0,$mime=0){

		if(empty($this->_db)) $this->_db = JFactory::getDBO();

		$data = $this->getTable('medias');
		$data->load((int)$this->_id);

		if (!class_exists('VmMediaHandler')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'mediahandler.php');

		$media = VmMediaHandler::createMedia($data,$type,$mime);

		return $media;

	}

	/**
	 * Kind of getFiles, it creates a bunch of image objects by an array of virtuemart_media_id
	 *
	 * @author Max Milbers
	 * @param unknown_type $virtuemart_media_id
	 * @param unknown_type $type
	 * @param unknown_type $mime
	 */
	function createMediaByIds($virtuemart_media_ids,$type='',$mime=''){

		if (!class_exists('VmMediaHandler')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'mediahandler.php');

		$medias = array();
		if(!empty($virtuemart_media_ids)){
			if(!is_array($virtuemart_media_ids)) $virtuemart_media_ids = explode(',',$virtuemart_media_ids);

			//Lets delete empty ids
			//$virtuemart_media_ids = array_diff($virtuemart_media_ids,array('0',''));

			$data = $this->getTable('medias');
			foreach($virtuemart_media_ids as $virtuemart_media_id){
				if(is_object($virtuemart_media_id)){
					$id = $virtuemart_media_id->virtuemart_media_id;
				} else {
					$id = $virtuemart_media_id;
				}
				if(!empty($id)){
					$data->load((int)$id);
					$file_type 	= empty($data->file_type)? $type:$data->file_type;
					$mime		= empty($data->file_mimetype)? $mime:$data->file_mimetype;
					$media = VmMediaHandler::createMedia($data,$file_type,$mime);
					if(is_object($virtuemart_media_id) && !empty($virtuemart_media_id->product_name)) $media->product_name = $virtuemart_media_id->product_name;
					$medias[] = $media;
				}
				/*	    		//$id = is_object($virtuemart_media_id)? $virtuemart_media_id->virtuemart_media_id:$virtuemart_media_id;
				 $data->load((int)$id);
				$media = VmMediaHandler::createMedia($data,$data->file_type,$mime);
				if(is_object($virtuemart_media_id) && !empty($virtuemart_media_id->product_name)) $media->product_name = $virtuemart_media_id->product_name;
				$medias[] = $media;*/
			}
		}

		if(empty($medias)){
			$data = $this->getTable('medias');
			$data->load(0);
			$medias[] = VmMediaHandler::createMedia($data,$type,$mime);
		}

		return $medias;
	}

	/**
	* Retireve a list of files from the database. This is meant only for backend use
	*
	* @author Max Milbers
	* @param boolean $onlyPublished True to only retreive the published files, false otherwise
	* @param boolean $noLimit True if no record count limit is used, false otherwise
	* @return object List of media objects
	*/

	function getFiles($onlyPublished=false, $noLimit=false,  $count=false, $where=array()){

		$this->_noLimit = $noLimit;

		if(empty($this->_db)) $this->_db = JFactory::getDBO();
		$vendorId = 1; //TODO set to logged user or requested vendorId, not easy later
		$query = '';

		$selectFields = array();

		$joinTables = array();
		$joinedTables = '';
		$whereItems= array();
		$groupBy ='';

		$virtuemart_product_id = JRequest::getInt('virtuemart_product_id',0);
		$cat_id = JRequest::getInt('virtuemart_category_id',0);


		if(!empty($virtuemart_product_id)){
			$mainTable = '`#__virtuemart_product_medias`';
			$selectFields[] = ' `#__virtuemart_medias`.`virtuemart_media_id` as virtuemart_media_id ';
			$joinTables[] = 'LEFT JOIN `#__virtuemart_medias` ON `#__virtuemart_medias`.`virtuemart_media_id`=`#__virtuemart_product_medias`.`virtuemart_media_id` ';
			$whereItems[] = '`virtuemart_product_id` = "'.$virtuemart_product_id.'"';
		}

		else if(!empty($cat_id)){
			$mainTable = '`#__virtuemart_product_medias`';
			$selectFields[] = ' `#__virtuemart_medias`.`virtuemart_media_id` as virtuemart_media_id';
			$joinTables[] = 'LEFT JOIN `#__virtuemart_medias` ON `#__virtuemart_medias`.`virtuemart_media_id`=`#__virtuemart_category_medias`.`virtuemart_media_id` ';
			$whereItems[] = '`virtuemart_category_id` = "'.$cat_id.'"';
		}

		else {
			$mainTable = '`#__virtuemart_medias`';
			$selectFields[] = ' `virtuemart_media_id` ';

			if(!class_exists('Permissions')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'permissions.php');
			if(!Permissions::getInstance()->check('admin') ){
				$whereItems[] = '(`virtuemart_vendor_id` = "'.(int)$vendorId.'" OR `shared`="1")';
			}

		}

		if ($onlyPublished) {
			$whereItems[] = '`#__virtuemart_medias`.`published` = 1';
		}

		if ($search = JRequest::getWord('searchMedia', false)){
			$search = '"%' . $this->_db->getEscaped( $search, true ) . '%"' ;
			$where[] = '`file_title` LIKE '.$search;
		}

		if (!empty($where)) $whereItems = array_merge($whereItems,$where);


		if(count($where)>0){
			$whereString = ' WHERE '.implode(' AND ', $whereItems );
		} else {
			$whereString = ' ';
		}

		$orderField = '';
		$orderBy = '';
		if ( JRequest::getCmd('view') == 'media') {
			$orderBy = $this->_getOrdering();
		}
//		$orderBy = $this->_getOrdering($orderField,'asc');
//		$orderBy = $this->_getOrdering($orderField,'asc');
//		if ( 'product_quantity'==JRequest::getWord('filter_order')) {
//			$orderBy = '';
//		}

		if(!empty($orderField)){
			$selectFields[] = $orderField;
		}

		if(count($selectFields)>0){

			$select = implode(', ', $selectFields ).' FROM '.$mainTable;
			//$selectFindRows = 'SELECT COUNT(*) FROM '.$mainTable;
			if(count($joinTables)>0){
				foreach($joinTables as $table){
					$joinedTables .= $table;
				}
			}

		} else {
			$vmError('No select fields given in getRevenueSortListOrderQuery','No select fields given');
			return false;
		}

		$this->_data = $this->exeSortSearchListQuery(2, $select, $joinedTables, $whereString, $groupBy, $orderBy);

		if(empty($this->_data)){
			return array();
		}

		if( !is_array($this->_data)){
			$this->_data = explode(',',$this->_data);
		}

		$this->_data = $this->createMediaByIds($this->_data);
		return $this->_data;



	}

	/**
	 * This function stores a media and updates then the refered table
	 *
	 * @author Max Milbers
	 * @author Patrick Kohl
	 * @param array $data Data from a from
	 * @param string $type type of the media  category,product,manufacturer,shop, ...
	 */
	function storeMedia($data,$type){

		JRequest::checkToken() or jexit( 'Invalid Token, while trying to save media' );

		//the active media id is not empty, so there should be something done with it
		if( (!empty($data['active_media_id']) && !empty($data['virtuemart_media_id']) ) || $data['media_action']=='upload'){

			$oldIds = $data['virtuemart_media_id'];
			$data['file_type'] = $type;
			$data['virtuemart_media_id'] = (int)$data['active_media_id'];

			$this -> setId($data['virtuemart_media_id']);

			$virtuemart_media_id = $this->store($data,$type);

			//added by Mike,   Mike why did you add this? This function storeMedia is extremely nasty
			$this->setId($virtuemart_media_id);

			if(!empty($oldIds)){
				if(!is_array($oldIds)) $oldIds = array($oldIds);
				$virtuemart_media_ids = array_merge( (array)$virtuemart_media_id,$oldIds);
				$data['virtuemart_media_id'] = array_unique($virtuemart_media_ids);
			} else {
				$data['virtuemart_media_id'] = $virtuemart_media_id;
			}

		}

		//set the relations
		$table = $this->getTable($type.'_medias');
		// Bind the form fields to the country table
		$data = $table->bindChecknStore($data);
		$errors = $table->getErrors();
		foreach($errors as $error){
			$this->setError($error);
		}

		return $this->_id;

	}

	/**
	 * Store an entry of a mediaItem, this means in end effect every media file in the shop
	 * images, videos, pdf, zips, exe, ...
	 *
	 * @author Max Milbers
	 */
	public function store($data,$type) {

		//if(empty($data['media_action'])) return $table->virtuemart_media_id;
		if (!class_exists('VmMediaHandler')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'mediahandler.php');

		$table = $this->getTable('medias');
		//		if($table->checkDataContainsTableFields($data,array('file_url','media_action','media_attributes','file_is_product_image','virtuemart_media_id','virtuemart_vendor_id'))){
		$table->bind($data);
		$data = VmMediaHandler::prepareStoreMedia($table,$data,$type); //this does not store the media, it process the actions and prepares data

		// workarround for media published and product published two fields in one form.
		if ($data['media_published'])
		$data['published'] = $data['media_published'];
		else
		$data['published'] = 0;

		$table->bindChecknStore($data);
		$errors = $table->getErrors();
		foreach($errors as $error){
			$this->setError('store medias '.$error);
		}

		return $table->virtuemart_media_id;
	}

	public function attachImages($objects,$type,$mime=''){
		if(!empty($objects)){
			if(!is_array($objects)) $objects = array($objects);
			foreach($objects as $object){

				if(empty($object->virtuemart_media_id)) $virtuemart_media_id = null; else $virtuemart_media_id = $object->virtuemart_media_id;
				$object->images = $this->createMediaByIds($virtuemart_media_id,$type,$mime);

			}
		}
	}

}
// pure php no closing tag
