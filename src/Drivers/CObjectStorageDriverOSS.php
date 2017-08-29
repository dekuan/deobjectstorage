<?php
namespace dekuan\deobjectstorage;

use dekuan\vdata\CConst;
use dekuan\delib\CLib;

use OSS\Core\OssException;
use OSS\OssClient;


/**
 * Created by PhpStorm.
 * User: xing
 * Date: August 29, 2017
 */
class CObjectStorageDriverOSS extends CDeObjectStorageBase implements IDeObjectStorage
{
	const OBJECT_TYPE_IMAGE			= 1;	//	image, default
	const OBJECT_TYPE_FILE			= 2;	//	other type of files

	const DEFAULT_FILE_EXT			= 'jpg';
	const DEFAULT_JPEG_QUALITY		= 80;
	const MAX_UPLOAD_FILE_SIZE		= 5 * 1024 * 1024;	//	5M, maximum size of file in bytes allowed to be uploaded
	const ALLOWED_IMAGE_TYPE		=
		[
			IMAGETYPE_GIF	=> 'gif',
			IMAGETYPE_JPEG	=> 'jpg',
			IMAGETYPE_PNG	=> 'png',
			IMAGETYPE_BMP	=> 'bmp',
		];

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
		$nObjectType	= CLib::GetVal( $arrInput, 'type', true, self::OBJECT_TYPE_IMAGE );

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
		$nObjectType	= CLib::GetVal( $arrInput, 'type', true, self::OBJECT_TYPE_IMAGE );
		$nTimeout	= 60;

		//
		//	first, we try to download the object file to local
		//
		$sDownloadedFFN		= '';
		$nCallDownloadFile	= $this->_downloadFile( $sObjectUrl, null, $nTimeout, $sDownloadedFFN );
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
	private function _isValidConfig( $arrConfig )
	{
		return CLib::IsArrayWithKeys
		(
			$arrConfig,
			[ 'access_key_id', 'access_key_secret', 'bucket_name', 'bucket_url', 'http_timeout', 'tcp_connect_timeout', 'file_field', 'access_url' ]
		);
	}

	private function _uploadObject( $sLocalFullFilename, $nObjectType, $sKey, & $arrReturnValue = null )
	{
		//
		//	upload object by its type
		//
		if ( self::OBJECT_TYPE_IMAGE == $nObjectType )
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
		$nCallConvert = $this->_ConvertImageToJpeg( $sLocalFullFilename, null, self::DEFAULT_JPEG_QUALITY );
		if ( CConst::ERROR_SUCCESS == $nCallConvert )
		{
			$nCallCheckImage = $this->_checkImage( $sLocalFullFilename );
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
					$nRet = CDeObjectStorageErrCode::ERROR_UPLOAD_IMAGE_UPLOAD_FILE_TO_OSS;
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
		$sExtension	= $this->_getImageExtension( $sLocalFullFilename );
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

	private function _checkImage( $sFullFilename )
	{
		if ( ! CLib::IsExistingString( $sFullFilename, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_CHECK_IMAGE_PARAM_FFN;
		}
		if ( ! file_exists( $sFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_CHECK_IMAGE_LOCAL_FILE_NOT_EXIST;
		}

		//	...
		$nRet = CDeObjectStorageErrCode::ERROR_CHECK_IMAGE_FAILED;

		if ( filesize( $sFullFilename ) < self::MAX_UPLOAD_FILE_SIZE )
		{
			if ( $this->_isAllowedImageTypeByFullFilename( $sFullFilename ) )
			{
				$nRet = CConst::ERROR_SUCCESS;
			}
			else
			{
				$nRet = CDeObjectStorageErrCode::ERROR_CHECK_IMAGE_INVALID_FILE_TYPE;
			}
		}
		else
		{
			$nRet = CDeObjectStorageErrCode::ERROR_CHECK_IMAGE_MAX_UPLOAD_FILE_SIZE;
		}

		return $nRet;
	}

	private function _ConvertImageToJpeg( $sSrcFullFilename, $sDstFullFilename = null, $nQuality = 80 )
	{
		if ( ! CLib::IsExistingString( $sSrcFullFilename, true ) ||
			! file_exists( $sSrcFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_CONVERT_IMAGE_TO_JPEG_PARAM_SRC_FFN;
		}
		if ( ! is_numeric( $nQuality ) || $nQuality < 0 || $nQuality > 100 )
		{
			return CDeObjectStorageErrCode::ERROR_CONVERT_IMAGE_TO_JPEG_PARAM_QUALITY;
		}

		//	...
		$nRet = CDeObjectStorageErrCode::ERROR_CONVERT_IMAGE_TO_JPEG_FAILED;

		//
		//	if parameter sDestFullFilename is null or empty
		//	we'll overwrite the source file
		//
		if ( ! CLib::IsExistingString( $sDstFullFilename, true ) )
		{
			$sDstFullFilename = $sSrcFullFilename;
		}

		try
		{
			//
			//	return value by calling exif_imagetype
			//	--------------------------------------------------
			//	value	/	constant name
			//	1		IMAGETYPE_GIF
			//	2		IMAGETYPE_JPEG
			//	3		IMAGETYPE_PNG
			//	4		IMAGETYPE_SWF
			//	5		IMAGETYPE_PSD
			//	6		IMAGETYPE_BMP
			//	7		IMAGETYPE_TIFF_II (intel byte order)
			//	8		IMAGETYPE_TIFF_MM (motorola byte order)
			//	9		IMAGETYPE_JPC
			//	10		IMAGETYPE_JP2
			//	11		IMAGETYPE_JPX
			//	12		IMAGETYPE_JB2
			//	13		IMAGETYPE_SWC
			//	14		IMAGETYPE_IFF
			//	15		IMAGETYPE_WBMP
			//	16		IMAGETYPE_XBM
			//
			$nImageType	= @ exif_imagetype( $sSrcFullFilename );
			if ( false !== $nImageType )
			{
				$hHandle = null;
				switch ( $nImageType )
				{
					case IMAGETYPE_GIF:
						if ( function_exists( 'imagecreatefromgif' ) )
						{
							$hHandle = @ imagecreatefromgif( $sSrcFullFilename );
						}
						break;
					case IMAGETYPE_JPEG:
						if ( function_exists( 'imagecreatefromjpeg' ) )
						{
							$hHandle = @ imagecreatefromjpeg( $sSrcFullFilename );
						}
						break;
					case IMAGETYPE_PNG:
						if ( function_exists( 'imagecreatefrompng' ) )
						{
							$hHandle = @ imagecreatefrompng( $sSrcFullFilename );
						}
						break;
					case IMAGETYPE_BMP:
						if ( function_exists( 'imagecreatefrombmp' ) )
						{
							$hHandle = @ imagecreatefrombmp( $sSrcFullFilename );
						}
						break;
				}
				if ( is_resource( $hHandle ) )
				{
					//
					//	we convert all type of image to jpeg
					//
					if ( imagejpeg( $hHandle, $sDstFullFilename, $nQuality ) )
					{
						$nRet = CConst::ERROR_SUCCESS;
					}
					else
					{
						$nRet = CDeObjectStorageErrCode::ERROR_CONVERT_IMAGE_TO_JPEG_SAVE;
					}

					@imagedestroy( $hHandle );
					$hHandle = null;
				}
				else
				{
					$nRet = CDeObjectStorageErrCode::ERROR_CONVERT_IMAGE_TO_JPEG_LOAD_IMAGE;
				}
			}
			else
			{
				$nRet = CDeObjectStorageErrCode::ERROR_CONVERT_IMAGE_TO_JPEG_GET_IMAGE_TYPE;
			}
		}
		catch ( \Exception $e )
		{
			$nRet = CDeObjectStorageErrCode::ERROR_CONVERT_IMAGE_TO_JPEG_EXCEPTION;
		}

		return $nRet;
	}

	private function _downloadFile( $sUrl, $sSpecifiedFilename = null, $nTimeout = 5, & $sReturnValue = null )
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
		$nCallGetUploadFullFilename	= $this->_getUploadFFN( $sSpecifiedFilename, self::DEFAULT_FILE_EXT, $sUploadFullFilename );
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

	private function _getUploadFFN( $sSpecifiedFilename = null, $sExtension = self::DEFAULT_FILE_EXT, & $sReturnValue = null )
	{
		$nRet			= CDeObjectStorageErrCode::ERROR_GET_UPLOAD_FFN_FAILED;
		$sReturnValue		= '';
		$sUploadDir		= $this->_getUploadDir();
		$sUploadFilename	= '';
		$nCallGetUploadFilename	= $this->_getUploadFilename( $sSpecifiedFilename, $sExtension, $sUploadFilename );
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

	private function _getUploadDir()
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

	private function _getUploadFilename( $sSpecifiedFilename, $sExtension = self::DEFAULT_FILE_EXT, & $sReturnValue = null )
	{
		if ( CLib::IsExistingString( $sSpecifiedFilename, true ) &&
			! $this->_isValidFilename( $sSpecifiedFilename ) )
		{
			//	invalid filename, so we stop it
			return CDeObjectStorageErrCode::ERROR_GET_UPLOAD_NAME_PARAM_SPECIFIED_FILENAME;
		}
		if ( CLib::IsExistingString( $sExtension, true ) &&
			! $this->_isAllowedExtension( $sExtension ) )
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

	private function _getImageExtension( $sFullFilename )
	{
		if ( ! is_string( $sFullFilename ) || empty( $sFullFilename ) )
		{
			return '';
		}

		$sRet	= '';
		if ( file_exists( $sFullFilename ) )
		{
			$arrPI = pathinfo( $sFullFilename );
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
				//
				//	get extension by image type
				//
				$sRet = $this->_getImageExtensionByType( @ exif_imagetype( $sFullFilename ) );
			}
		}

		return $sRet;
	}

	private function _getImageExtensionByType( $nImageType )
	{
		$sRet	= '';

		if ( is_numeric( $nImageType ) )
		{
			if ( array_key_exists( $nImageType, self::ALLOWED_IMAGE_TYPE ) )
			{
				$sRet = self::ALLOWED_IMAGE_TYPE[ $nImageType ];
			}
		}

		return $sRet;
	}

	private function _isAllowedExtension( $sExtension )
	{
		$arrAllowedExtension	= array_values( self::ALLOWED_IMAGE_TYPE );
		return ( CLib::IsExistingString( $sExtension, true ) &&
			in_array( $sExtension, $arrAllowedExtension ) );
	}

	private function _isAllowedImageTypeByFullFilename( $sFullFilename )
	{
		return ( CLib::IsExistingString( $sFullFilename ) &&
			file_exists( $sFullFilename ) &&
			$this->_isAllowedImageType( @ exif_imagetype( $sFullFilename ) ) );
	}
	private function _isAllowedImageType( $nImageType )
	{
		return ( is_numeric( $nImageType ) &&
			array_key_exists( $nImageType, self::ALLOWED_IMAGE_TYPE ) );
	}

	private function _isValidFilename( $sStr )
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