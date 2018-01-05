<?php

namespace dekuan\deobjectstorage\driver\oss;

use dekuan\deobjectstorage\CDeObjectStorageConst;
use dekuan\delib\CLib;
use dekuan\deobjectstorage\CDeObjectStorageErrCode;
use dekuan\vdata\CConst;


/**
 *	Class CDriverOSSLibVideo
 *	@package dekuan\deobjectstorage\driver
 */
class CDriverOSSLibVideo
{
	/**
	 *	@param	string	$sFullFilename
	 *	@return int
	 */
	static function checkVideo( $sFullFilename )
	{
		if ( ! CLib::IsExistingString( $sFullFilename, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_CHECKVIDEO_PARAM_FFN;
		}
		if ( ! file_exists( $sFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_CHECKVIDEO_LOCAL_FILE_NOT_EXIST;
		}

		//	...
		$nRet = CDeObjectStorageErrCode::ERROR_CHECKVIDEO_FAILED;

		if ( filesize( $sFullFilename ) < CDeObjectStorageConst::DEFAULT_MAX_UPLOAD_FILE_SIZE )
		{
			if ( CDriverOSSLibFs::isAllowedMimeTypeByFullFilename( $sFullFilename, CDeObjectStorageConst::DEFAULT_ALLOWED_VIDEO_MIME_TYPE_LIST ) )
			{
				$nRet = CConst::ERROR_SUCCESS;
			}
			else
			{
				$nRet = CDeObjectStorageErrCode::ERROR_CHECKVIDEO_INVALID_FILE_TYPE;
			}
		}
		else
		{
			$nRet = CDeObjectStorageErrCode::ERROR_CHECKVIDEO_MAX_UPLOAD_FILE_SIZE;
		}

		return $nRet;
	}


}