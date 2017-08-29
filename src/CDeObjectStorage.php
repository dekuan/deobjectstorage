<?php
namespace dekuan\deobjectstorage;

use dekuan\delib\CLib;


/**
 * Created by PhpStorm.
 * User: xing
 * Date: August 29, 2017
 */
class CDeObjectStorage extends CDeObjectStorageBase implements IDeObjectStorage
{
	private $m_oInstanceClass	= null;
	private $m_arrDrivers		= null;


	public function __construct( $sDriverName, $arrConfig )
	{
		assert( CLib::IsExistingString( $sDriverName, true ) );
		assert( CLib::IsArrayWithKeys( $arrConfig ) );

		//
		//	driver list
		//
		$this->m_arrDrivers =
		[
			'oss'	=> CObjectStorageDriverOSS::class,
		];

		//
		//	init driver
		//
		$this->_initDriver( $sDriverName, $arrConfig );
	}
	public function __destruct()
	{
	}


	public function uploadByFile( $arrInput, $sKey, & $arrReturnValue = null )
	{
		if ( null == $this->m_oInstanceClass )
		{
			return CDeObjectStorageErrCode::ERROR_DRIVER;
		}

		return $this->m_oInstanceClass->uploadByFile( $arrInput, $sKey, $arrReturnValue );
	}

	public function uploadByUrl( $arrInput, $sKey, & $arrReturnValue = null )
	{
		if ( null == $this->m_oInstanceClass )
		{
			return CDeObjectStorageErrCode::ERROR_DRIVER;
		}

		return $this->m_oInstanceClass->uploadByUrl( $arrInput, $sKey, $arrReturnValue );
	}

	public function isExistObject( $sKey )
	{
		if ( null == $this->m_oInstanceClass )
		{
			return CDeObjectStorageErrCode::ERROR_DRIVER;
		}

		return $this->m_oInstanceClass->isExistObject( $sKey );
	}


	////////////////////////////////////////////////////////////////////////////////
	//	Private
	//

	private function _initDriver( $sDriverName, $arrConfig )
	{
		if ( CLib::IsExistingString( $sDriverName ) &&
			CLib::IsArrayWithKeys( $this->m_arrDrivers, $sDriverName ) )
		{
			$sFullClassName	= $this->m_arrDrivers[ $sDriverName ];
			$this->m_oInstanceClass	= new $sFullClassName( $arrConfig );
		}
	}
}