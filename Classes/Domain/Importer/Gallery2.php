<?php


class Tx_YagImporter_Domain_Importer_Gallery2 {

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;


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


	/**
	 * @var Tx_Yag_Domain_Repository_GalleryRepository
	 */
	protected $galleryRepository;


	/**
	 * @var Tx_Yag_Domain_Repository_AlbumRepository
	 */
	protected $albumRepository;


	/**
	 * @param Tx_Yag_Domain_Repository_GalleryRepository $galleryRepository
	 */
	public function injectGalleryRepository(Tx_Yag_Domain_Repository_GalleryRepository $galleryRepository) {
		$this->galleryRepository = $galleryRepository;
	}


	/**
	 * @param Tx_Yag_Domain_Repository_AlbumRepository $albumRepository
	 */
	public function injectAlbumRepository(Tx_Yag_Domain_Repository_AlbumRepository $albumRepository) {
		$this->albumRepository = $albumRepository;
	}


	/**
	 * @param Tx_Extbase_Object_ObjectManager $objectManager
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}


	public function init() {
		$pidDetector = $this->objectManager->get('Tx_Yag_Utility_PidDetector'); /** @var $pidDetector Tx_Yag_Utility_PidDetector */
		$pidDetector->setPids(array($this->importConfiguration['targetPid']));
	}


	public function import() {
		$this->buildStructure($this->importConfiguration['importRootUid']);
		$this->importAlbum($this->plainStructure[440]);
	}


	protected function importAlbum($g2Entity) {
		$gallery = $this->selectOrCreateGallery($g2Entity);

		if($g2Entity['yagAlbumName']) {
			$album = $this->addAlbum($g2Entity);
			$album->setGallery($gallery);
			$gallery->addAlbum($album);
		}

	}


	/**
	 * @param $g2Entity
	 * @return object|Tx_Yag_Domain_Model_Gallery
	 */
	protected function selectOrCreateGallery($g2Entity) {
		$gallery = $this->galleryRepository->findOneByName($g2Entity['yagGalleryName']);

		if(!$gallery instanceof Tx_Yag_Domain_Model_Gallery) {
			$gallery = $this->objectManager->get('Tx_Yag_Domain_Model_Gallery'); /** @var Tx_Yag_Domain_Model_Gallery $gallery */
			$gallery->setName($g2Entity['yagGalleryName']);
			$gallery->setDescription($g2Entity['yagGalleryDescription']);
			$gallery->setDate(new DateTime('@'.$g2Entity['yagGalleryDate']));
			$gallery->setPid($this->importConfiguration['targetPid']);
		}

		return $gallery;
	}


	/**
	 * @param array $g2Entity
	 * @return object|Tx_Yag_Domain_Model_Album
	 */
	protected function addAlbum(array $g2Entity) {
		$album = $this->objectManager->get('Tx_Yag_Domain_Model_Album'); /** @var Tx_Yag_Domain_Model_Album $album */
		$album->setDate(new DateTime('@'.$g2Entity['item']['g_originationTimestamp']));
		$album->setName($g2Entity['yagAlbumName']);
		$album->setDescription($g2Entity['item']['g_description']);
		$album->setPid($this->importConfiguration['targetPid']);

		return $album;
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
		if(count($this->plainStructure) == 0) {
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
					$yagGalleryDate = $item['g_originationTimestamp'];
					$yagGalleryDescription = $item['g_description'];

					$path =  $item['g_pathComponent'];
				break;

				case 1:
					$title = $item['g_title'];

					$yagGalleryName = $this->plainStructure[$parentId]['yagGalleryName'];
					$yagGalleryDate = $this->plainStructure[$parentId]['yagGalleryDate'];
					$yagGalleryDescription = $this->plainStructure[$parentId]['yagGalleryDescription'];

					$yagAlbumName = $title;
					$path =  $this->plainStructure[$parentId]['path'] . '/' . $item['g_pathComponent'];
				break;

				default:
					$title = $parentTitle . ' / ' . $item['g_title'];

					$yagGalleryName = $this->plainStructure[$parentId]['yagGalleryName'];
					$yagGalleryDate = $this->plainStructure[$parentId]['yagGalleryDate'];
					$yagGalleryDescription = $this->plainStructure[$parentId]['yagGalleryDescription'];

					$yagAlbumName = $title;
					$path =  $this->plainStructure[$parentId]['path'] . '/' . $item['g_pathComponent'];
			}

			$this->plainStructure[$item['g_id']] = array(
				'lvl' => $level,
				'title' => $title,
				'item' => $item,
				'yagGalleryName' => $yagGalleryName,
				'yagGalleryDescription' => $yagGalleryDescription,
				'yagGalleryDate' => $yagGalleryDate,
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
			g_originationTimestamp,

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
