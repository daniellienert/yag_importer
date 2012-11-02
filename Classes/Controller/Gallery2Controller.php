<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Daniel Lienert <daniel@lienert.cc>
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 *
 *
 * @package yag_importer
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 *
 */
class Tx_YagImporter_Controller_Gallery2Controller extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @var array
	 */
	protected $importerConfig = array();


	public function initializeAction() {
		$this->importerConfig = Tx_PtExtbase_State_Session_Storage_SessionAdapter::getInstance()->read('yagImportConfig');
	}


	/**
	 * @param Tx_Extbase_MVC_View_ViewInterface $view
	 */
	public function initializeView(Tx_Extbase_MVC_View_ViewInterface $view) {
		parent::initializeView($view);
		$this->view->assign('importerConfig', $this->importerConfig);
	}


	/**
	 * action show
	 *
	 * @return void
	 */
	public function showAction() {

	}


	/**
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * @param string $databaseName
	 */
	public function saveDatabaseCredentialsAction($host, $user, $password, $databaseName) {

		$this->importerConfig['host'] = $host;
		$this->importerConfig['user'] = $user;
		$this->importerConfig['password'] = $password;
		$this->importerConfig['databaseName'] = $databaseName;
		Tx_PtExtbase_State_Session_Storage_SessionAdapter::getInstance()->store('yagImportConfig', $this->importerConfig);

		try {
			$this->buildImporter();
		} catch(Exception $e){
			$this->flashMessageContainer->add($e->getMessage(), 'Database Connection could not be established', t3lib_FlashMessage::ERROR);
			$this->forward('show');
		}

		$this->flashMessageContainer->add('Database Connection successfully established.');
		$this->forward('selectRoot');
	}



	public function selectRootAction() {

		$importer = $this->buildImporter();
		$importer->buildStructure();

		$this->view->assign('fullTree', $importer->getTreeselectOptions());
	}


	/**
	 * @param string $importRootPath
	 * @param int $importRootUid
	 * @param int $targetPid
	 */
	public function saveImportConfigurationAction($importRootPath, $importRootUid, $targetPid) {

		if(!is_dir($importRootPath)) {
			$this->flashMessageContainer->add('Directory ' . $importRootPath . ' not found.', '', t3lib_FlashMessage::ERROR);
			$this->forward('selectRoot');
		}

		$this->importerConfig['importRootPath'] = $importRootPath;
		$this->importerConfig['importRootUid'] = $importRootUid;
		$this->importerConfig['targetPid'] = $targetPid;
		Tx_PtExtbase_State_Session_Storage_SessionAdapter::getInstance()->store('yagImportConfig', $this->importerConfig);

		$this->forward('preview');
	}




	public function previewAction() {

		$importer = $this->buildImporter();
		$importer->buildStructure($this->importerConfig['importRootUid']);

		$this->view->assign('plainStructure', $importer->getPlainStructure());
	}



	public function importAction() {

	}


	/**
	 * @return object|Tx_YagImporter_Domain_Importer_Gallery2
	 */
	protected function buildImporter() {

		$dbConfiguration = new Tx_PtExtlist_Domain_Configuration_DataBackend_DataSource_DatabaseDataSourceConfiguration(
			array(
				'host' => $this->importerConfig['host'],
				'username' => $this->importerConfig['user'],
				'password' => $this->importerConfig['password'],
				'databaseName' => $this->importerConfig['databaseName'],
				'port' => 3306
			)
		);

		$dataSource = Tx_PtExtlist_Domain_DataBackend_DataSource_MysqlDataSourceFactory::createInstance('Tx_PtExtlist_Domain_DataBackend_DataSource_MySqlDataSource', $dbConfiguration);

		$importer = $this->objectManager->get('Tx_YagImporter_Domain_Importer_Gallery2'); /** @var $importer Tx_YagImporter_Domain_Importer_Gallery2 */
		$importer->_injectDatSource($dataSource);
		$importer->setImportConfiguration($this->importerConfig);

		return $importer;
	}


}
?>