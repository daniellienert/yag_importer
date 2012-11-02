<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daniel
 * Date: 30.10.12
 * Time: 11:21
 * To change this template use File | Settings | File Templates.
 */
class Tx_YagImporter_Domain_Importer_Gallery2 {

	/**
	 * @var Tx_PtExtlist_Domain_DataBackend_DataSource_MySqlDataSource
	 */
	protected $dataSource;

	/**
	 * @var array
	 */
	protected $plainStructure = array();


	/**
	 * @var array
	 */
	protected $recursiveStructure;


	/**
	 * @var array
	 */
	protected $importConfiguration;



	public function import() {

	}


	protected function addGalleryIfNotExists($g2Entity) {

	}


	protected function addAlbum($g2Entity) {

	}


	protected function importImages() {
		
	}



	/**
	 * @param Tx_PtExtlist_Domain_DataBackend_DataSource_MySqlDataSource $dataSource
	 */
	public function _injectDatSource(Tx_PtExtlist_Domain_DataBackend_DataSource_MySqlDataSource $dataSource) {
		$this->dataSource = $dataSource;
	}


	/**
	 * @param int $rootUid
	 */
	public function buildStructure($rootUid = 0) {
		if(count $this->plainStructure == 0) {
			$structure = array();
			$this->buildStructureRecursive($structure, $rootUid);

			$this->recursiveStructure = $structure;
		}
	}


	/**
	 * @return array
	 */
	public function getTreeSelectOptions() {
		$options = array();

		foreach($this->plainStructure as $key => $entry) {
			$options[$key] = str_pad('', $entry['lvl'], '-') . ' ' . $entry['title'];
		}

		return $options;
	}



	/**
	 * @param $structure
	 * @param $parentId
	 * @param string $parentTitle
	 * @param int $level
	 */
	protected function buildStructureRecursive(&$structure, $parentId, $parentTitle = '', $level = 0) {

		$items = $this->selectChildItems($parentId, TRUE);
		foreach($items as $item) {
			$structure[$item['g_id']] = $item;

			switch ($level) {
				case 0:
					$title = $item['g_title'];
					$yagGalleryName = $item['g_title'];
					$path =  $item['g_pathComponent'];
				break;

				case 1:
					$title = $item['g_title'];
					$yagGalleryName = $this->plainStructure[$parentId]['yagGalleryName'];
					$yagAlbumName = $title;
					$path =  $this->plainStructure[$parentId]['path'] . '/' . $item['g_pathComponent'];
				break;

				default:
					$title = $parentTitle . ' / ' . $item['g_title'];
					$yagGalleryName = $this->plainStructure[$parentId]['yagGalleryName'];
					$yagAlbumName = $title;
					$path =  $this->plainStructure[$parentId]['path'] . '/' . $item['g_pathComponent'];
			}

			$this->plainStructure[$item['g_id']] = array(
				'lvl' => $level,
				'title' => $title,
				'item' => $item,
				'yagGalleryName' => $yagGalleryName,
				'yagAlbumName' => $yagAlbumName,
				'path' => $path,
			);

			$this->buildStructureRecursive($structure[$item['g_id']], $item['g_id'], $title, $level+1);
		}

	}


	/**
	 * @param $parentId
	 * @param boolean $isAlbum
	 * @return array
	 */
	protected function selectChildItems($parentId, $isAlbum = TRUE) {
		$statement = "
			SELECT entity.g_id, g_title, g_description, IF(album.g_id, 1,0) as is_album, g_pathComponent,

			(SELECT COUNT(*)
			FROM `g2_Item` int_item
			INNER JOIN `g2_ChildEntity` int_entity ON int_entity.g_id = int_item.g_id
			INNER JOIN `g2_DataItem` int_dataitem ON int_dataitem.g_id = int_item.g_id
			WHERE int_entity.g_parentId = entity.g_id
			AND int_dataitem.g_mimeType = 'image/jpeg'
			) as imageCount


			FROM `g2_ChildEntity` entity
			INNER JOIN g2_Item item ON entity.g_id = item.g_id
			LEFT JOIN `g2_AlbumItem` album ON album.g_id = item.g_id
			LEFT JOIN `g2_FileSystemEntity` systemEntity ON systemEntity.g_id = item.g_id
			WHERE g_parentId = %s
		";

		if($isAlbum === TRUE) {
			$statement .= ' AND album.g_id > 0';
		}

		$statement = sprintf($statement, $parentId);
		return $this->dataSource->executeQuery($statement)->fetchAll();
	}





	/**
	 * @param $parentId
	 * @return array
	 */
	public function selectImagesByParent($parentId) {

		$statement = "SELECT  item.g_id, g_title, g_description, g_pathComponent
					FROM `g2_Item` item
					INNER JOIN `g2_ChildEntity` entity ON entity.g_id = item.g_id
					INNER JOIN `g2_DataItem` dataitem ON dataitem.g_id = item.g_id
					INNER JOIN `g2_FileSystemEntity` systemEntity ON systemEntity.g_id = item.g_id
					WHERE entity.g_parentId = 440
					AND dataitem.g_mimeType = 'image/jpeg'";

		$statement = sprintf($statement, $parentId);

		return $this->dataSource->executeQuery($statement)->fetchAll();
	}



	/**
	 * @return array
	 */
	public function getPlainStructure() {
		return $this->plainStructure;
	}

	/**
	 * @return array
	 */
	public function getRecursiveStructure() {
		return $this->recursiveStructure;
	}

	/**
	 * @param array $importConfiguration
	 */
	public function setImportConfiguration($importConfiguration) {
		$this->importConfiguration = $importConfiguration;
	}

	/**
	 * @return array
	 */
	public function getImportConfiguration() {
		return $this->importConfiguration;
	}

}
