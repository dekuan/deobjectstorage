<?php
namespace dekuan\deobjectstorage;

use dekuan\delib\CLib;


/**
 *	Class CDeObjectStorage
 *	@package dekuan\deobjectstorage
 */
class CDeObjectStorage extends CDeObjectStorageBase implements IDeObjectStorage
{
	private $m_oInstanceClass	= null;
	private $m_arrDrivers		= null;


	/**
	 *	CDeObjectStorage constructor.
	 *	@param	string	$sDriverName
	 *	@param	array	$arrConfig
	 */
	public function __construct( $sDriverName, $arrConfig )
	{
		assert( CLib::IsExistingString( $sDriverName, true ) );
		assert( CLib::IsArrayWithKeys( $arrConfig ) );

		//
		//	driver list
		//
		$this->m_arrDrivers =
		[
			'oss'	=> driver\oss\CDriverOSS::class,
		];

		//
		//	init driver
		//
		$this->_initDriver( $sDriverName, $arrConfig );
	}
	public function __destruct()
	{
	}


	/**
	 *	@param	array	$arrInput
	 *	@param	string	$sKey
	 *	@param	array	$arrReturnValue
	 *	@return	int
	 */
	public function uploadByFile( $arrInput, $sKey, Array & $arrReturnValue = null )
	{
		if ( null == $this->m_oInstanceClass )
		{
			return CDeObjectStorageErrCode::ERROR_DRIVER;
		}

		return $this->m_oInstanceClass->uploadByFile( $arrInput, $sKey, $arrReturnValue );
	}

	/**
	 *	@param	array	$arrInput
	 *	@param	string	$sKey
	 *	@param	array	$arrReturnValue
	 *	@return	int
	 */
	public function uploadByUrl( $arrInput, $sKey, Array & $arrReturnValue = null )
	{
		if ( null == $this->m_oInstanceClass )
		{
			return CDeObjectStorageErrCode::ERROR_DRIVER;
		}

		return $this->m_oInstanceClass->uploadByUrl( $arrInput, $sKey, $arrReturnValue );
	}

	/**
	 *	@param	string	$sKey
	 *	@return int
	 */
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

	
	/**
	 *	initialize drivers
	 * 
	 *	@param	string	$sDriverName
	 *	@param	array	$arrConfig
	 */
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