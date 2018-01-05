<?php

namespace dekuan\deobjectstorage\driver\oss;

use dekuan\delib\CLib;
use dekuan\deobjectstorage\CDeObjectStorageConst;
use dekuan\deobjectstorage\CDeObjectStorageErrCode;
use dekuan\vdata\CConst;


/**
 *	Class CDriverOSSLibFs
 *	@package dekuan\deobjectstorage\driver
 */
class CDriverOSSLibFs
{
	/**
	 *	@param	string	$sFullFilename
	 *	@param	array	$arrMimeTypeList
	 *	@return bool
	 */
	static function isAllowedMimeTypeByFullFilename( $sFullFilename, $arrMimeTypeList = CDeObjectStorageConst::DEFAULT_ALLOWED_IMAGE_MIME_TYPE_LIST )
	{
		if ( ! CLib::IsExistingString( $sFullFilename ) ||
			! file_exists( $sFullFilename ) )
		{
			return false;
		}
		if ( ! CLib::IsArrayWithKeys( $arrMimeTypeList ) )
		{
			return false;
		}

		//
		//	mime_content_type
		//	return values:
		//		"video/mp4"
		//		"audio/mpeg"
		//		"image/jpeg"
		//
		$bRet		= false;
		$sMimeType	= @mime_content_type( $sFullFilename );

		if ( CLib::IsExistingString( $sMimeType ) )
		{
			$bRet = in_array( $sMimeType, $arrMimeTypeList );
		}

		return $bRet;
	}
	
	
	/**
	 *	@param	string	$sFullFilename
	 *	@return string | null
	 */
	static function getFileMimeContentType( $sFullFilename )
	{
		if ( ! CLib::IsExistingString( $sFullFilename ) ||
			! file_exists( $sFullFilename ) )
		{
			return null;
		}

		//
		//	mime_content_type
		//	return values:
		//		"video/mp4"
		//		"audio/mpeg"
		//		"image/jpeg"
		//
		$sRet		= null;
		$sMimeType	= @mime_content_type( $sFullFilename );
		if ( CLib::IsExistingString( $sMimeType ) )
		{
			$sRet = $sMimeType;
		}

		return $sRet;
	}

	/**
	 *	@param	string	$sFullFilename
	 *	@param	string	$sKey
	 *	@return	string
	 */
	static function getFileExtension( $sFullFilename, $sKey = null )
	{
		if ( ! is_string( $sFullFilename ) || empty( $sFullFilename ) )
		{
			return null;
		}

		$sRet	= null;
		$arrPI	= pathinfo( CLib::IsExistingString( $sKey ) ? $sKey : $sFullFilename );
		if ( CLib::IsArrayWithKeys( $arrPI, 'extension' ) &&
			CLib::IsExistingString( $arrPI[ 'extension' ], true ) )
		{
			//
			//	get extension from full filename
			//
			$sRet = $arrPI[ 'extension' ];
		}
		else
		{
			$sRet = self::getFileExtensionByMimeType( $sFullFilename );
		}

		return $sRet;
	}
	
	/**
	 *	@param	string	$sFullFilename
	 *	@return string | null
	 */
	static function getFileExtensionByMimeType( $sFullFilename )
	{
		if ( ! CLib::IsExistingString( $sFullFilename ) ||
			! file_exists( $sFullFilename ) )
		{
			return null;
		}

		//
		//	mime_content_type
		//	return values:
		//		"video/mp4"
		//		"audio/mpeg"
		//		"image/jpeg"
		//
		$sRet		= null;
		$sMimeType	= self::getFileMimeContentType( $sFullFilename );
		if ( CLib::IsExistingString( $sMimeType ) )
		{
			$arrMimeType	= explode( '/', $sMimeType );
			if ( is_array( $arrMimeType ) && count( $arrMimeType ) >= 2 )
			{
				$sRet = $arrMimeType[ 1 ];
			}
		}

		return $sRet;
	}


	/**
	 *	@param	string	$sSpecifiedFilename
	 *	@param	string	$sExtension
	 *	@param	string	$sReturnValue
	 *	@return int
	 */
	static function getUploadFFN( $sSpecifiedFilename = null, $sExtension = CDeObjectStorageConst::DEFAULT_FILE_EXT, & $sReturnValue = null )
	{
		$nRet			= CDeObjectStorageErrCode::ERROR_GETUPLOADFFN_FAILED;
		$sReturnValue		= '';
		$sUploadDir		= CDriverOSSLibFs::getUploadDir();
		$sUploadFilename	= '';
		$nCallGetUploadFilename	= CDriverOSSLibCommon::getUploadFilename( $sSpecifiedFilename, $sExtension, $sUploadFilename );
		if ( CConst::ERROR_SUCCESS == $nCallGetUploadFilename )
		{
			if ( CLib::IsExistingString( $sUploadDir ) && is_dir( $sUploadDir ) )
			{
				if ( CLib::IsExistingString( $sUploadFilename ) )
				{
					$nRet = CConst::ERROR_SUCCESS;
					$sReturnValue = sprintf( "%s/%s", rtrim( $sUploadDir, "\r\n\t\\/" ), $sUploadFilename );
				}
				else
				{
					$nRet = CDeObjectStorageErrCode::ERROR_GETUPLOADFFN_GET_FILENAME;
				}
			}
			else
			{
				$nRet = CDeObjectStorageErrCode::ERROR_GETUPLOADFFN_INVALID_DIR;
			}
		}
		else
		{
			$nRet = $nCallGetUploadFilename;
		}

		return $nRet;
	}
	
	/**
	 *	@return string
	 */
	static function getUploadDir()
	{
		$sRet	= '';

		//	...
		//$sStorageDir	= storage_path();
		$sStorageDir	= getcwd();
		if ( is_dir( $sStorageDir ) )
		{
			//	...
			$sTempDir = sprintf( "%s/temp/uploader/", rtrim( $sStorageDir, "\r\n\t\\/" ) );
			if ( ! is_dir( $sTempDir ) )
			{
				mkdir( $sTempDir, 0755, true );
				chmod( $sTempDir, 0777 );
			}

			//	...
			if ( is_dir( $sTempDir ) )
			{
				$sRet = $sTempDir;
			}
		}

		return $sRet;
	}
}