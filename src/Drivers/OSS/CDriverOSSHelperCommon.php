<?php

namespace dekuan\deobjectstorage\driver;

use dekuan\delib\CLib;
use dekuan\deobjectstorage\CDeObjectStorageConst;
use dekuan\deobjectstorage\CDeObjectStorageErrCode;
use dekuan\vdata\CConst;


/**
 *	Class CDriverOSSHelperCommon
 *	@package dekuan\deobjectstorage\driver
 */
class CDriverOSSHelperCommon
{
	/**
	 *	@param	string	$sSpecifiedFilename
	 *	@param	string	$sExtension
	 *	@param	string	$sReturnValue
	 *	@return	int
	 */
	static function getUploadFilename( $sSpecifiedFilename, $sExtension = CDeObjectStorageConst::DEFAULT_FILE_EXT, & $sReturnValue = null )
	{
		if ( CLib::IsExistingString( $sSpecifiedFilename, true ) &&
			! CDriverOSSHelperCommon::isValidFilename( $sSpecifiedFilename ) )
		{
			//	invalid filename, so we stop it
			return CDeObjectStorageErrCode::ERROR_GET_UPLOAD_NAME_PARAM_SPECIFIED_FILENAME;
		}
		if ( CLib::IsExistingString( $sExtension, true ) &&
			! CDriverOSSHelperCommon::isAllowedExtension( $sExtension ) )
		{
			return CDeObjectStorageErrCode::ERROR_GET_UPLOAD_NAME_PARAM_EXTENSION;
		}

		if ( ! CLib::IsExistingString( $sSpecifiedFilename, true ) )
		{
			//
			//	null or empty,
			//	so, we create a random filename for it
			//
			$sRandom		= sprintf( "%s-%d%d", microtime(), time(), rand( 10000, 99999 ) );
			$sSpecifiedFilename	= strtolower( trim( md5( $sRandom ) ) );
		}

		$sReturnValue = sprintf( "%s.%s", trim( $sSpecifiedFilename ), $sExtension );
		return CConst::ERROR_SUCCESS;
	}

	/**
	 *	@param	string	$sExtension
	 *	@return bool
	 */
	static function isAllowedExtension( $sExtension )
	{
		$arrAllowedExtension	= array_values( CDeObjectStorageConst::ALLOWED_IMAGE_TYPE );
		return ( CLib::IsExistingString( $sExtension, true ) &&
			in_array( $sExtension, $arrAllowedExtension ) );
	}
	
	
	/***
	 *	@param	string	$sStr
	 *	@return	bool
	 */
	static function isValidFilename( $sStr )
	{
		//
		//	sStr	- [in] string
		//	RETURN	- true / false
		//
		$bRet = false;
		$sStdChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_";

		if ( CLib::IsExistingString( $sStr, true ) )
		{
			$sStr		= trim( $sStr );
			$nStrLength	= strlen( $sStr );
			$nErrorCount	= 0;
			for ( $i = 0; $i < $nStrLength; $i ++ )
			{
				$cChr = substr( $sStr, $i, 1 );
				if ( ! strstr( $sStdChars, $cChr ) )
				{
					$nErrorCount ++;
					break;
				}
			}

			//	...
			$bRet = ( 0 == $nErrorCount ? true : false );
		}

		return $bRet;
	}
}