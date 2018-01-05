<?php

namespace dekuan\deobjectstorage\driver\oss;

use dekuan\delib\CLib;
use dekuan\deobjectstorage\CDeObjectStorageConst;
use dekuan\deobjectstorage\CDeObjectStorageErrCode;
use dekuan\vdata\CConst;


/**
 *	Class CDriverOSSLibNetwork
 *	@package dekuan\deobjectstorage\driver
 */
class CDriverOSSLibNetwork
{
	/**
	 *	@param	string	$sUrl			[in]
	 *	@param	string	$sSpecifiedFilename	[in/opt]
	 *	@param	int	$nTimeout		[in/opt] timeout in seconds
	 *	@param	string	$sReturnValue		[out/opt] full filename while downloaded successfully
	 *	@return int				error code
	 */
	static function downloadFile( $sUrl, $sSpecifiedFilename = null, $nTimeout = 5, & $sReturnValue = null )
	{
		if ( ! CLib::IsExistingString( $sUrl ) )
		{
			return CDeObjectStorageErrCode::ERROR_DOWNLOADFILE_PARAM_URL;
		}

		$nRet		= CDeObjectStorageErrCode::ERROR_DOWNLOADFILE_FAILED;
		$sReturnValue	= '';

		$sUploadFullFilename		= '';
		$nCallGetUploadFullFilename	= CDriverOSSLibFs::getUploadFFN
		(
			$sSpecifiedFilename,
			CDeObjectStorageConst::DEFAULT_FILE_EXT,
			$sUploadFullFilename
		);
		if ( CConst::ERROR_SUCCESS == $nCallGetUploadFullFilename )
		{
			$sUrl = str_replace( " ", "%20", $sUrl );
			if ( function_exists( 'curl_init' ) )
			{
				$ch = curl_init();
				if ( is_resource( $ch ) )
				{
					curl_setopt( $ch, CURLOPT_URL, $sUrl );
					curl_setopt( $ch, CURLOPT_TIMEOUT, $nTimeout );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );

					//	...
					$vContent	= curl_exec( $ch );
					$nStatus	= curl_getinfo( $ch, CURLINFO_HTTP_CODE );
					if ( 200 == $nStatus &&
						! curl_error( $ch ) )
					{
						if ( @ file_put_contents( $sUploadFullFilename, $vContent ) )
						{
							$nRet		= CConst::ERROR_SUCCESS;
							$sReturnValue	= $sUploadFullFilename;
						}
						else
						{
							$nRet = CDeObjectStorageErrCode::ERROR_DOWNLOADFILE_SAVE;
						}
					}
					else
					{
						$nRet = CDeObjectStorageErrCode::ERROR_DOWNLOADFILE_CURL_STATUS;
					}

					curl_close( $ch );
					$ch = null;
				}
				else
				{
					$nRet = CDeObjectStorageErrCode::ERROR_DOWNLOADFILE_CURL_INIT;
				}
			}
			else
			{
				$arrOpts =
					[
						'http' =>
							[
								'method'	=> 'GET',
								'header'	=> '',
								'timeout'	=> $nTimeout
							]
					];
				$oContext	= stream_context_create( $arrOpts );
				if ( is_resource( $oContext ) )
				{
					if ( @ copy( $sUrl, $sUploadFullFilename, $oContext ) )
					{
						$nRet		= CConst::ERROR_SUCCESS;
						$sReturnValue	= $sUploadFullFilename;
					}
					else
					{
						$nRet = CDeObjectStorageErrCode::ERROR_DOWNLOADFILE_COPY_FILE;
					}
				}
				else
				{
					$nRet = CDeObjectStorageErrCode::ERROR_DOWNLOADFILE_STREAM_CONTEXT_CREATE;
				}
			}
		}

		return $nRet;
	}
}