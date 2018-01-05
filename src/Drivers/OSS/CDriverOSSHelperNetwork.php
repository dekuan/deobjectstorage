<?php

namespace dekuan\deobjectstorage\driver;


/**
 *	Class CDriverOSSHelperNetwork
 *	@package dekuan\deobjectstorage\driver
 */
class CDriverOSSHelperNetwork
{
	/**
	 *	@param	string	$sUrl
	 *	@param	string	$sSpecifiedFilename
	 *	@param	int	$nTimeout
	 *	@param	string	$sReturnValue
	 *	@return int
	 */
	static function downloadFile( $sUrl, $sSpecifiedFilename = null, $nTimeout = 5, & $sReturnValue = null )
	{
		//
		//	$sUrl			- [in]
		//	$sSpecifiedFilename	- [in/opt]
		//	$nTimeout		- [in/opt] timeout in seconds
		//	$sReturnValue		- [out/opt] full filename while downloaded successfully
		//	RETURN			- error code
		//

		if ( ! CLib::IsExistingString( $sUrl ) )
		{
			return CDeObjectStorageErrCode::ERROR_DOWNLOAD_FILE_PARAM_URL;
		}

		$nRet		= CDeObjectStorageErrCode::ERROR_DOWNLOAD_FILE_FAILED;
		$sReturnValue	= '';

		$sUploadFullFilename		= '';
		$nCallGetUploadFullFilename	= CDriverOSSHelperFs::getUploadFFN
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
							$nRet = CDeObjectStorageErrCode::ERROR_DOWNLOAD_FILE_SAVE;
						}
					}
					else
					{
						$nRet = CDeObjectStorageErrCode::ERROR_DOWNLOAD_FILE_CURL_STATUS;
					}

					curl_close( $ch );
					$ch = null;
				}
				else
				{
					$nRet = CDeObjectStorageErrCode::ERROR_DOWNLOAD_FILE_CURL_INIT;
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
						$nRet = CDeObjectStorageErrCode::ERROR_DOWNLOAD_FILE_COPY_FILE;
					}
				}
				else
				{
					$nRet = CDeObjectStorageErrCode::ERROR_DOWNLOAD_FILE_STREAM_CONTEXT_CREATE;
				}
			}
		}

		return $nRet;
	}
}