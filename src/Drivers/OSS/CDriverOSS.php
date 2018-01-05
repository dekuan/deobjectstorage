<?php

namespace dekuan\deobjectstorage\driver;


use dekuan\deobjectstorage\CDeObjectStorageBase;
use dekuan\deobjectstorage\CDeObjectStorageConst;
use dekuan\deobjectstorage\CDeObjectStorageErrCode;
use dekuan\deobjectstorage\IDeObjectStorage;

use dekuan\vdata\CConst;
use dekuan\delib\CLib;

use OSS\Core\OssException;
use OSS\OssClient;



/**
 *	Class CDriverOSS
 *	@package dekuan\deobjectstorage
 */
class CDriverOSS extends CDeObjectStorageBase implements IDeObjectStorage
{
	//	...
	private $m_arrOssConfig			= null;
	private $m_sOssDomain			= null;


	public function __construct( $arrConfig )
	{
		$this->_parseConfig( $arrConfig );
	}
	public function __destruct()
	{
	}

	/**
	 * 	upload via file
	 * 
	 *	@param	array	$arrInput
	 *	@param	string	$sKey
	 *	@param	array	$arrReturnValue
	 *	@return int
	 */
	public function uploadByFile( $arrInput, $sKey, & $arrReturnValue = null )
	{
		//
		//	arrInput	- [in] parameters
		//				[
		//					'file'	=> '/full/path/to/file',
		//					'type'	=> self::OBJECT_TYPE_IMAGE, self::OBJECT_TYPE_FILE
		//				]
		//	sKey		- [in] key/filename to be stored by
		//	arrReturnValue	- [out/opt] result info
		//	RETURN		- error code
		//
		if ( ! CLib::IsArrayWithKeys( $arrInput, 'file' ) ||
			! CLib::IsExistingString( $arrInput[ 'file' ] ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOAD_BY_FILE_PARAM_FFN;
		}

		$nRet		= CDeObjectStorageErrCode::ERROR_UPLOAD_BY_FILE_FAILED;
		$sObjectFile	= CLib::GetVal( $arrInput, 'file', false, null );
		$nObjectType	= CLib::GetVal( $arrInput, 'type', true, CDeObjectStorageConst::OBJECT_TYPE_IMAGE );

		if ( file_exists( $sObjectFile ) )
		{
			//
			//	try to push local file to oss
			//
			$arrOssInfo			= null;
			$nCallPushLocalFileToOss	= $this->_uploadObject( $sObjectFile, $nObjectType, $sKey, $arrOssInfo );
			if ( CConst::ERROR_SUCCESS == $nCallPushLocalFileToOss )
			{
				$nRet = CConst::ERROR_SUCCESS;
				$arrReturnValue = $arrOssInfo;
			}
			else
			{
				$nRet = $nCallPushLocalFileToOss;
			}
		}
		else
		{
			$nRet = CDeObjectStorageErrCode::ERROR_UPLOAD_BY_FILE_NOT_EXISTS;
		}

		return $nRet;
	}

	/**
	 * 	upload via url
	 * 
	 *	@param	array	$arrInput
	 *	@param	string	$sKey
	 *	@param	array	$arrReturnValue
	 *	@return int
	 */
	public function uploadByUrl( $arrInput, $sKey, & $arrReturnValue = null )
	{
		//
		//	arrInput	- [in] parameters
		//				[
		//					'url'	=> 'url of source image',
		//					'type'	=> self::OBJECT_TYPE_IMAGE, self::OBJECT_TYPE_FILE
		//				]
		//	$sKey		- [in] key/filename to be stored by
		//	arrReturnValue	- [out/opt] result info
		//	RETURN		- error code
		//

		if ( ! CLib::IsArrayWithKeys( $arrInput, 'url' ) ||
			! CLib::IsExistingString( $arrInput['url'] ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOAD_BY_URL_PARAM_URL;
		}

		//	...
		$nRet		= CDeObjectStorageErrCode::ERROR_UPLOAD_BY_URL_FAILED;
		$sObjectUrl	= CLib::GetVal( $arrInput, 'url', false, null );
		$nObjectType	= CLib::GetVal( $arrInput, 'type', true, CDeObjectStorageConst::OBJECT_TYPE_IMAGE );
		$nTimeout	= 60;

		//
		//	first, we try to download the object file to local
		//
		$sDownloadedFFN		= '';
		$nCallDownloadFile	= CDriverOSSHelperNetwork::downloadFile( $sObjectUrl, null, $nTimeout, $sDownloadedFFN );
		if ( CConst::ERROR_SUCCESS == $nCallDownloadFile &&
			CLib::IsExistingString( $sDownloadedFFN ) )
		{
			$nRet = $this->uploadByFile
			(
				[
					'file'	=> $sDownloadedFFN,
					'type'	=> $nObjectType,
				],
				$sKey,
				$arrReturnValue
			);

			//
			//	remove local file
			//
			@ unlink( $sDownloadedFFN );
		}
		else
		{
			$nRet = CDeObjectStorageErrCode::ERROR_UPLOAD_BY_URL_DOWNLOAD_FILE;
		}

		return $nRet;
	}

	/**
	 *	@param	string	$sKey
	 *	@return	bool
	 */
	public function isExistObject( $sKey )
	{
		return false;

//		if ( ! CLib::IsExistingString( $sKey ) )
//		{
//			return false;
//		}
//
//		//	...
//		$bRet = false;
//
//		//	...
//		$nErrCode	= CDeObjectStorageErrCode::ERROR_IS_EXIST_OBJECT_FAILED;
//		$arrConfig	= $this->m_arrOssConfig;
//
//		if ( is_array( $arrConfig ) )
//		{
//			$arrConfig[ 'filename' ] = $sKey;
//			$bExists	= false;
//			$nErrCode	= COSSOperate::doesObjectExists( $arrConfig, $bExists );
//			if ( CConst::ERROR_SUCCESS == $nErrCode )
//			{
//				$bRet = $bExists;
//			}
//		}
//
//		return $bRet;
	}


	////////////////////////////////////////////////////////////////////////////////
	//	Private
	//

	/**
	 *	@param	array	$arrConfig
	 *	@return int
	 */
	private function _parseConfig( $arrConfig )
	{
		if ( ! $this->_isValidConfig( $arrConfig ) )
		{
			return CDeObjectStorageErrCode::ERROR_PARSE_CONFIG_INVALID_CONFIG;
		}

		//
		//	all configuration for oss uploading
		//
		$this->m_arrOssConfig	= $arrConfig;

		//
		//	the domain url for visiting avatar
		//	for example:
		//		'avatar.dekuan.org'
		//
		$this->m_sOssDomain	= CLib::GetValEx( $arrConfig, 'access_url', CLib::VARTYPE_STRING, '' );

		assert( CLib::IsArrayWithKeys( $this->m_arrOssConfig ) );
		assert( CLib::IsExistingString( $this->m_sOssDomain ) );

		//	...
		return CConst::ERROR_SUCCESS;
	}

	/**
	 * 	check if it's a valid config
	 * 
	 *	@param	array	$arrConfig
	 *	@return bool
	 */
	private function _isValidConfig( $arrConfig )
	{
		return CLib::IsArrayWithKeys
		(
			$arrConfig,
			[
				'access_key_id',
				'access_key_secret',
				'bucket_name',
				'bucket_url',
				'http_timeout',
				'tcp_connect_timeout',
				'file_field',
				'access_url'
			]
		);
	}

	/**
	 *	upload object
	 * 
	 *	@param	string	$sLocalFullFilename
	 *	@param	int	$nObjectType
	 *	@param	string	$sKey
	 *	@param	array	$arrReturnValue
	 *	@return int
	 */
	private function _uploadObject( $sLocalFullFilename, $nObjectType, $sKey, & $arrReturnValue = null )
	{
		//
		//	upload object by its type
		//
		if ( CDeObjectStorageConst::OBJECT_TYPE_IMAGE == $nObjectType )
		{
			//	1, image
			return $this->_uploadImage( $sLocalFullFilename, $sKey, $arrReturnValue );	
		}
		else
		{
			//	2, other type of file
			return $this->_uploadImage( $sLocalFullFilename, $sKey, $arrReturnValue );
		}
	}

	/**
	 *	@param	string	$sLocalFullFilename
	 *	@param	string	$sKey
	 *	@param	array	$arrReturnValue
	 *	@return int
	 */
	private function _uploadImage( $sLocalFullFilename, $sKey, & $arrReturnValue = null )
	{
		//
		//	sLocalFullFilename	- [in] string, the full filename of a local image
		//	sKey			- [in] string, the key for oss to store the file
		//	arrReturnValue		- [out/opt] return info
		//	RETURN			- error code
		//
		if ( ! CLib::IsExistingString( $sLocalFullFilename, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOAD_IMAGE_PARAM_LOCAL_FFN;
		}
		if ( ! CLib::IsExistingString( $sKey, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOAD_IMAGE_PARAM_KEY;
		}
		if ( ! file_exists( $sLocalFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOAD_IMAGE_LOCAL_FFN_NOT_EXIST;
		}

		//	...
		$nRet	= CDeObjectStorageErrCode::ERROR_UPLOAD_IMAGE_FAILED;

		//
		//	try to convert the image to jpeg format
		//
		$nCallConvert = CDriverOSSHelperImage::convertImageToJpeg
		(
			$sLocalFullFilename,
			null,
			CDeObjectStorageConst::DEFAULT_JPEG_QUALITY
		);
		if ( CConst::ERROR_SUCCESS == $nCallConvert )
		{
			$nCallCheckImage = CDriverOSSHelperImage::checkImage( $sLocalFullFilename );
			if ( CConst::ERROR_SUCCESS ==  $nCallCheckImage )
			{
				$nCallUploadToOSS = $this->_uploadFileToOss( $sKey, $sLocalFullFilename );
				if ( 0 == $nCallUploadToOSS )
				{
					$arrImgInfo = [];
					$nCallBuildImageInfo = $this->_buildImageInfo( $sKey, $sLocalFullFilename, $arrImgInfo );
					if ( CConst::ERROR_SUCCESS == $nCallBuildImageInfo )
					{
						if ( CLib::IsArrayWithKeys( $arrImgInfo ) )
						{
							$arrReturnValue	= $arrImgInfo;
							$nRet = CConst::ERROR_SUCCESS;
						}
						else
						{
							$nRet = CDeObjectStorageErrCode::ERROR_UPLOAD_IMAGE_INVALID_IMAGE_INFO;
						}
					}
					else
					{
						$nRet = $nCallBuildImageInfo;
					}
				}
				else
				{
					//	nCallUploadToOSS
					//$nRet = CDeObjectStorageErrCode::ERROR_UPLOAD_IMAGE_UPLOAD_FILE_TO_OSS;
					$nRet = $nCallUploadToOSS;
				}
			}
			else
			{
				//	failed by calling _checkImage
				$nRet = $nCallCheckImage;
			}
		}
		else
		{
			$nRet = $nCallConvert;
		}

		return $nRet;
	}

	/**
	 *	@return	null | OssClient
	 */
	private function _createOssClientInstance()
	{
		if ( ! $this->_isValidConfig( $this->m_arrOssConfig ) )
		{
			return null;
		}

		//	...
		$oRet	= null;

		//	...
		$sAccessKeyId		= CLib::GetValEx( $this->m_arrOssConfig, 'access_key_id', CLib::VARTYPE_STRING, '' );
		$sAccessKeySecret	= CLib::GetValEx( $this->m_arrOssConfig, 'access_key_secret', CLib::VARTYPE_STRING, '' );
		$sEndPoint		= CLib::GetValEx( $this->m_arrOssConfig, 'bucket_url', CLib::VARTYPE_STRING, '' );
		$nTimeOut		= CLib::GetValEx( $this->m_arrOssConfig, 'http_timeout', CLib::VARTYPE_NUMERIC, 0 );
		$nConnectTimeout	= CLib::GetValEx( $this->m_arrOssConfig, 'tcp_connect_timeout', CLib::VARTYPE_NUMERIC, 0 );

		//	...
		$oRet = new OssClient( $sAccessKeyId, $sAccessKeySecret, $sEndPoint );
		$oRet->setTimeout( $nTimeOut );
		$oRet->setConnectTimeout( $nConnectTimeout );

		return $oRet;
	}

	/**
	 *	@param	string	$sKey
	 *	@param	string	$sLocalFullFilename
	 *	@return int
	 */
	private function _uploadFileToOss( $sKey, $sLocalFullFilename )
	{
		if ( ! CLib::IsExistingString( $sKey, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOAD_FILE_TO_OSS_PARAM_KEY;
		}
		if ( ! CLib::IsExistingString( $sLocalFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOAD_FILE_TO_OSS_PARAM_LOCAL_FFN;
		}
		if ( ! file_exists( $sLocalFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOAD_FILE_TO_OSS_LOCAL_FFN_NOT_EXIST;
		}

		//	...
		$sBucketName		= CLib::GetValEx( $this->m_arrOssConfig, 'bucket_name', CLib::VARTYPE_STRING, '' );
		if ( ! CLib::IsExistingString( $sBucketName ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOAD_FILE_TO_OSS_INVALID_BUCKETNAME;
		}

		//	...
		$nRet		= CDeObjectStorageErrCode::ERROR_UPLOAD_FILE_TO_OSS_FAILED;

		//	...
		$oOssClient	= $this->_createOssClientInstance();
		if ( $oOssClient &&
			$oOssClient instanceof OssClient )
		{
			try
			{
				//
				//	try to upload
				//
				$infoRtn = $oOssClient->uploadFile( $sBucketName, $sKey, $sLocalFullFilename );

				//
				//	check the result
				//
				if ( CLib::IsArrayWithKeys( $infoRtn, 'info' ) &&
					CLib::IsArrayWithKeys( $infoRtn[ 'info' ], [ 'http_code', 'size_upload' ] ) &&
					is_numeric( $infoRtn[ 'info' ][ 'http_code' ] ) &&
					200 == $infoRtn[ 'info' ][ 'http_code' ] &&
					is_numeric( $infoRtn[ 'info' ][ 'size_upload' ] ) &&
					$infoRtn[ 'info' ][ 'size_upload' ] > 0 )
				{
					$nRet = CConst::ERROR_SUCCESS;
				}
				else
				{
					$nRet = CDeObjectStorageErrCode::ERROR_UPLOAD_FILE_TO_OSS_FAILED_UPLOAD;
				}
			}
			catch( OssException $e )
			{
				$nRet = CDeObjectStorageErrCode::ERROR_UPLOAD_FILE_TO_OSS_EXCEPTION;
			}
		}
		else
		{
			$nRet = CDeObjectStorageErrCode::ERROR_UPLOAD_FILE_TO_OSS_FAILED_CREATE_CLIENT;
		}

		return $nRet;
	}

	/**
	 *	@param	string	$sKey
	 *	@param	string	$sLocalFullFilename
	 *	@param	array	$arrReturnValue
	 *	@return int
	 */
	private function _buildImageInfo( $sKey, $sLocalFullFilename, & $arrReturnValue = null )
	{
		if ( ! CLib::IsExistingString( $sKey, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_BUILD_IMAGE_INFO_PARAM_KEY;
		}
		if ( ! CLib::IsExistingString( $sLocalFullFilename, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_BUILD_IMAGE_INFO_PARAM_LOCAL_FFN;
		}
		if ( ! file_exists( $sLocalFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_BUILD_IMAGE_INFO_PARAM_LOCAL_FFN_NOT_EXIST;
		}

		//	...
		$nRet		= CDeObjectStorageErrCode::ERROR_BUILD_IMAGE_INFO_FAILED;
		$sExtension	= CDriverOSSHelperImage::getImageExtension( $sLocalFullFilename );
		if ( CLib::IsExistingString( $sExtension ) )
		{
			$arrImgInfo = @ getimagesize( $sLocalFullFilename );
			if ( CLib::IsArrayWithKeys( $arrImgInfo, [ 0, 1, 2, 'mime' ] ) )
			{
				$sImageUrl	= sprintf( "%s/%s", $this->m_sOssDomain, $sKey );
				$arrReturnValue	=
				[
					'key'		=> pathinfo( $sKey, PATHINFO_BASENAME ),
					'ext'		=> $sExtension,
					'url'		=> $sImageUrl,
					'width'		=> $arrImgInfo[ 0 ],
					'height'	=> $arrImgInfo[ 1 ],
					'mime'		=> $arrImgInfo[ 'mime' ],
				];

				//	...
				$nRet = CConst::ERROR_SUCCESS;
			}
		}
		else
		{
			$nRet = CDeObjectStorageErrCode::ERROR_BUILD_IMAGE_INFO_GET_IMAGE_EXTENSION;
		}

		return $nRet;
	}














}