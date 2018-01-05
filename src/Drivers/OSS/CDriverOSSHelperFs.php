<?php

namespace dekuan\deobjectstorage\driver;
use dekuan\deobjectstorage\CDeObjectStorageConst;
use dekuan\deobjectstorage\CDeObjectStorageErrCode;
use dekuan\vdata\CConst;


/**
 *	Class CDriverOSSHelperFs
 *	@package dekuan\deobjectstorage\driver
 */
class CDriverOSSHelperFs
{
	/**
	 *	@param	string	$sSpecifiedFilename
	 *	@param	string	$sExtension
	 *	@param	string	$sReturnValue
	 *	@return int
	 */
	static function getUploadFFN( $sSpecifiedFilename = null, $sExtension = CDeObjectStorageConst::DEFAULT_FILE_EXT, & $sReturnValue = null )
	{
		$nRet			= CDeObjectStorageErrCode::ERROR_GET_UPLOAD_FFN_FAILED;
		$sReturnValue		= '';
		$sUploadDir		= CDriverOSSHelperFs::getUploadDir();
		$sUploadFilename	= '';
		$nCallGetUploadFilename	= CDriverOSSHelperCommon::getUploadFilename( $sSpecifiedFilename, $sExtension, $sUploadFilename );
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
					$nRet = CDeObjectStorageErrCode::ERROR_GET_UPLOAD_FFN_GET_FILENAME;
				}
			}
			else
			{
				$nRet = CDeObjectStorageErrCode::ERROR_GET_UPLOAD_FFN_INVALID_DIR;
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