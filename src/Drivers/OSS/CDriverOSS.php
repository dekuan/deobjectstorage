<?php

namespace dekuan\deobjectstorage\driver\oss;


use dekuan\deobjectstorage\CDeObjectStorageBase;
use dekuan\deobjectstorage\CDeObjectStorageConst;
use dekuan\deobjectstorage\CDeObjectStorageErrCode;
use dekuan\deobjectstorage\IDeObjectStorage;

use dekuan\vdata\CConst;
use dekuan\delib\CLib;



/**
 *	Class CDriverOSS
 *	@package dekuan\deobjectstorage
 */
class CDriverOSS extends CDeObjectStorageBase implements IDeObjectStorage
{
	//	...
	private $m_arrConfig			= null;
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
	 *	@param	array	$arrInput		[in] parameters
	 *	@param	string	$sKey			[in] key/filename to be stored by
	 *	@param	array	$arrReturnValue		[out/opt] result info
	 *	@return int				error code
	 */
	public function uploadByFile( $arrInput, $sKey, Array & $arrReturnValue = null )
	{
		//
		//	arrInput	- [
		//				'file'	=> '/full/path/to/file.xx',
		//				'type'	=> self::OBJECT_TYPE_IMAGE, self::OBJECT_TYPE_FILE
		//			]
		//
		if ( ! CLib::IsArrayWithKeys( $arrInput, 'file' ) ||
			! CLib::IsExistingString( $arrInput[ 'file' ] ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADBYFILE_PARAM_FFN;
		}

		//	...
		$nRet		= CDeObjectStorageErrCode::ERROR_UPLOADBYFILE_FAILED;
		$sObjectFile	= CLib::GetVal( $arrInput, 'file', false, null );

		if ( file_exists( $sObjectFile ) )
		{
			//
			//	try to push local file to oss
			//
			$arrOssInfo			= null;
			$nCallPushLocalFileToOss	= $this->_uploadObject( $arrInput, $sKey, $arrOssInfo );
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
			$nRet = CDeObjectStorageErrCode::ERROR_UPLOADBYFILE_NOT_EXISTS;
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
	public function uploadByUrl( $arrInput, $sKey, Array & $arrReturnValue = null )
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
			! CLib::IsExistingString( $arrInput[ 'url' ] ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADBYURL_PARAM_URL;
		}

		//	...
		$nRet		= CDeObjectStorageErrCode::ERROR_UPLOADBYURL_FAILED;
		$sObjectUrl	= CLib::GetVal( $arrInput, 'url', false, null );
		$nTimeout	= 60;

		//
		//	first, we try to download the object file to local
		//
		$sDownloadedFFN		= '';
		$nCallDownloadFile	= CDriverOSSLibNetwork::downloadFile( $sObjectUrl, null, $nTimeout, $sDownloadedFFN );
		if ( CConst::ERROR_SUCCESS == $nCallDownloadFile )
		{
			if ( CLib::IsExistingString( $sDownloadedFFN ) )
			{
				$arrInput[ 'file' ]	= $sDownloadedFFN;
				$nRet = $this->uploadByFile
				(
					$arrInput,
					$sKey,
					$arrReturnValue
				);				
			}
			else
			{
				$nRet = CDeObjectStorageErrCode::ERROR_UPLOADBYURL_DOWNLOAD_FILE;
			}

			//
			//	remove local file
			//
			@ unlink( $sDownloadedFFN );
		}
		else
		{
			$nRet = $nCallDownloadFile;
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
//		$nErrCode	= CDeObjectStorageErrCode::ERROR_ISEXISTOBJECT_FAILED;
//		$arrConfig	= $this->m_arrConfig;
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
			return CDeObjectStorageErrCode::ERROR_PARSECONFIG_INVALID_CONFIG;
		}

		//
		//	all configuration for oss uploading
		//
		$this->m_arrConfig	= $arrConfig;

		//
		//	the domain url for visiting avatar
		//	for example:
		//		'avatar.dekuan.org'
		//
		$this->m_sOssDomain	= CLib::GetValEx( $arrConfig, 'access_url', CLib::VARTYPE_STRING, '' );

		assert( CLib::IsArrayWithKeys( $this->m_arrConfig ) );
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
		//
		//	arrConfig
		//		access_key_id
		//		access_key_secret
		//		bucket_name
		//		bucket_url
		//		http_timeout
		//		tcp_connect_timeout
		//		file_field
		//		access_url
		//		max_upload_file_size
		//		allowed_mime_type_list
		//
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
	 *	@param	array	$arrInput
	 *	@param	string	$sKey
	 *	@param	array	$arrReturnValue
	 *	@return int
	 */
	private function _uploadObject( $arrInput, $sKey, Array & $arrReturnValue = null )
	{
		if ( ! CLib::IsArrayWithKeys( $arrInput, 'file' ) ||
			! CLib::IsExistingString( $arrInput[ 'file' ] ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADOBJECT_PARAM_ARRINPUT;
		}

		//	...
		$nRet			= CDeObjectStorageErrCode::ERROR_UPLOADOBJECT_FAILED;
		$sLocalFullFilename	= CLib::GetVal( $arrInput, 'file', false, null );
		$nObjectType		= CLib::GetVal( $arrInput, 'type', true, CDeObjectStorageConst::OBJECT_TYPE_IMAGE );

		//	...
		$nCallCheckLimitation	= $this->_checkLimitation( $nObjectType, $sLocalFullFilename );
		if ( CConst::ERROR_SUCCESS ==  $nCallCheckLimitation )
		{
			//
			//	upload object by its type
			//
			if ( CDeObjectStorageConst::OBJECT_TYPE_IMAGE == $nObjectType )
			{
				//	1, image
				$nRet = $this->_uploadObjectOfImage( $sLocalFullFilename, $sKey, $arrInput, $arrReturnValue );
			}
			else
			{
				//	2, other type of common file
				$nRet = $this->_uploadObjectOfCommonFile( $sLocalFullFilename, $sKey, $arrInput, $arrReturnValue );
			}
		}
		else
		{
			//	failed by calling _checkLimitation
			$nRet = $nCallCheckLimitation;
		}

		return $nRet;
	}

	/**
	 * 	upload object with type of image
	 *
	 *	@param	string	$sLocalFullFilename	[in] the full filename of a local image
	 *	@param	string	$sKey			[in] the key for oss to store the file
	 *	@param	array	$arrInput		[in] options
	 *	@param	array	$arrReturnValue		[out/opt] return info
	 *	@return int	error code
	 */
	private function _uploadObjectOfImage( $sLocalFullFilename, $sKey, Array $arrInput = null, Array & $arrReturnValue = null )
	{
		if ( ! CLib::IsExistingString( $sLocalFullFilename, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFIMAGE_PARAM_LOCAL_FFN;
		}
		if ( ! CLib::IsExistingString( $sKey, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFIMAGE_PARAM_KEY;
		}
		if ( ! file_exists( $sLocalFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFIMAGE_LOCAL_FFN_NOT_EXIST;
		}

		//	...
		$nRet	= CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFIMAGE_FAILED;

		//
		//	try to convert the image to jpeg format
		//
		$sLocalNewFullFilename	= sprintf( "%s.dst", $sLocalFullFilename );
		$nCallConvert		= CDriverOSSLibImage::convertImageToJpeg
		(
			$sLocalFullFilename,
			$sLocalNewFullFilename,
			CDeObjectStorageConst::DEFAULT_JPEG_QUALITY
		);
		if ( CConst::ERROR_SUCCESS == $nCallConvert )
		{
			//	...
			$cOSSUploader		= new CDriverOSSUploader( $this->m_arrConfig );
			$nCallUploadToOSS	= $cOSSUploader->uploadFileToOss( $sKey, $sLocalNewFullFilename );
			if ( 0 == $nCallUploadToOSS )
			{
				$arrImgInfo		= [];
				$nCallBuildImageInfo	= $this->_buildResultInfo( $sKey, $sLocalNewFullFilename, $arrImgInfo );
				if ( CConst::ERROR_SUCCESS == $nCallBuildImageInfo )
				{
					if ( CLib::IsArrayWithKeys( $arrImgInfo ) )
					{
						$arrReturnValue	= $arrImgInfo;
						$nRet = CConst::ERROR_SUCCESS;
					}
					else
					{
						$nRet = CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFIMAGE_INVALID_IMAGE_INFO;
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
				$nRet = $nCallUploadToOSS;
			}

			//	...
			@ unlink( $sLocalNewFullFilename );
			$sLocalNewFullFilename = null;
		}
		else
		{
			$nRet = $nCallConvert;
		}

		return $nRet;
	}

	/**
	 * 	upload object with type of common file
	 *
	 *	@param	string	$sLocalFullFilename	[in] the full filename of a local image
	 *	@param	string	$sKey			[in] the key for oss to store the file
	 *	@param	array	$arrInput		[in] options
	 *	@param	array	$arrReturnValue		[out/opt] return info
	 *	@return int	error code
	 */
	private function _uploadObjectOfCommonFile( $sLocalFullFilename, $sKey, Array $arrInput = null, Array & $arrReturnValue = null )
	{
		if ( ! CLib::IsExistingString( $sLocalFullFilename, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFCOMMONFILE_PARAM_LOCAL_FFN;
		}
		if ( ! CLib::IsExistingString( $sKey, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFCOMMONFILE_PARAM_KEY;
		}
		if ( ! file_exists( $sLocalFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFCOMMONFILE_LOCAL_FFN_NOT_EXIST;
		}

		//	...
		$nRet	= CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFCOMMONFILE_FAILED;

		//	...
		$cOSSUploader		= new CDriverOSSUploader( $this->m_arrConfig );
		$nCallUploadToOSS	= $cOSSUploader->uploadFileToOss( $sKey, $sLocalFullFilename );
		if ( 0 == $nCallUploadToOSS )
		{
			$arrImgInfo		= [];
			$nCallBuildResultInfo	= $this->_buildResultInfo( $sKey, $sLocalFullFilename, $arrImgInfo );
			if ( CConst::ERROR_SUCCESS == $nCallBuildResultInfo )
			{
				if ( CLib::IsArrayWithKeys( $arrImgInfo ) )
				{
					$arrReturnValue	= $arrImgInfo;
					$nRet = CConst::ERROR_SUCCESS;
				}
				else
				{
					$nRet = CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFCOMMONFILE_INVALID_IMAGE_INFO;
				}
			}
			else
			{
				$nRet = $nCallBuildResultInfo;
			}
		}
		else
		{
			//	nCallUploadToOSS
			//$nRet = CDeObjectStorageErrCode::ERROR_UPLOADOBJECTOFCOMMONFILE_UPLOAD_FILE_TO_OSS;
			$nRet = $nCallUploadToOSS;
		}

		return $nRet;
	}


	/**
	 *	@param	int	$nObjectType
	 *	@param	string	$sFullFilename
	 *	@return int
	 */
	private function _checkLimitation( $nObjectType, $sFullFilename )
	{
		if ( ! CLib::IsExistingString( $sFullFilename, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_CHECKLIMITATION_PARAM_FFN;
		}
		if ( ! file_exists( $sFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_CHECKLIMITATION_LOCAL_FILE_NOT_EXIST;
		}

		//	...
		$nRet = CDeObjectStorageErrCode::ERROR_CHECKLIMITATION_FAILED;

		//	...
		$nMaxUploadFileSize	= CLib::IsArrayWithKeys( $this->m_arrConfig, 'max_upload_file_size' ) ? intval( $this->m_arrConfig[ 'max_upload_file_size' ] ) : CDeObjectStorageConst::DEFAULT_MAX_UPLOAD_FILE_SIZE;
		$arrAllowedMimeTypeList	= CLib::IsArrayWithKeys( $this->m_arrConfig, 'allowed_mime_type_list' ) ? $this->m_arrConfig[ 'allowed_mime_type_list' ] : null;

		if ( null == $arrAllowedMimeTypeList )
		{
			if ( CDeObjectStorageConst::OBJECT_TYPE_VIDEO == $nObjectType )
			{
				$arrAllowedMimeTypeList = CDeObjectStorageConst::DEFAULT_ALLOWED_VIDEO_MIME_TYPE_LIST;
			}
			else if ( CDeObjectStorageConst::OBJECT_TYPE_AUDIO == $nObjectType )
			{
				$arrAllowedMimeTypeList = CDeObjectStorageConst::DEFAULT_ALLOWED_AUDIO_MIME_TYPE_LIST;
			}
			else
			{
				$arrAllowedMimeTypeList = CDeObjectStorageConst::DEFAULT_ALLOWED_IMAGE_MIME_TYPE_LIST;
			}
		}

		//	...
		if ( filesize( $sFullFilename ) < $nMaxUploadFileSize )
		{
			if ( CDriverOSSLibFs::isAllowedMimeTypeByFullFilename( $sFullFilename, $arrAllowedMimeTypeList ) )
			{
				$nRet = CConst::ERROR_SUCCESS;
			}
			else
			{
				$nRet = CDeObjectStorageErrCode::ERROR_CHECKLIMITATION_INVALID_FILE_TYPE;
			}
		}
		else
		{
			$nRet = CDeObjectStorageErrCode::ERROR_CHECKLIMITATION_MAX_UPLOAD_FILE_SIZE;
		}

		return $nRet;
	}

	/**
	 *	@param	string	$sKey
	 *	@param	string	$sLocalFullFilename
	 *	@param	array	$arrReturnValue
	 *	@return int
	 */
	private function _buildResultInfo( $sKey, $sLocalFullFilename, & $arrReturnValue = null )
	{
		if ( ! CLib::IsExistingString( $sKey, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_BUILDRESULTINFO_PARAM_KEY;
		}
		if ( ! CLib::IsExistingString( $sLocalFullFilename, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_BUILDRESULTINFO_PARAM_LOCAL_FFN;
		}
		if ( ! file_exists( $sLocalFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_BUILDRESULTINFO_PARAM_LOCAL_FFN_NOT_EXIST;
		}

		//	...
		$nRet		= CDeObjectStorageErrCode::ERROR_BUILDRESULTINFO_FAILED;
		$nFileSize	= @ filesize( $sLocalFullFilename );
		$MimeType	= CDriverOSSLibFs::getFileMimeContentType( $sLocalFullFilename );
		$sExtension	= CDriverOSSLibFs::getFileExtension( $sLocalFullFilename, $sKey );
		if ( CLib::IsExistingString( $MimeType ) &&
			CLib::IsExistingString( $sExtension ) )
		{
			//	...
			$nRet = CConst::ERROR_SUCCESS;

			//
			//	basic info
			//
			$sImageUrl	= sprintf( "%s/%s", rtrim( $this->m_sOssDomain, "/\\" ), $sKey );
			$arrReturnValue	=
				[
					'key'		=> pathinfo( $sKey, PATHINFO_BASENAME ),
					'url'		=> $sImageUrl,
					'ext'		=> $sExtension,
					'size'		=> $nFileSize,
					'mime'		=> $MimeType,
					'width'		=> 0,
					'height'	=> 0,
				];

			//
			//	image info
			//			
			$arrImgInfo = @ getimagesize( $sLocalFullFilename );
			if ( CLib::IsArrayWithKeys( $arrImgInfo, [ 0, 1, 2, 'mime' ] ) )
			{
				$arrReturnValue[ 'width' ]	= $arrImgInfo[ 0 ];
				$arrReturnValue[ 'height' ]	= $arrImgInfo[ 1 ];
			}
		}
		else
		{
			$nRet = CDeObjectStorageErrCode::ERROR_BUILDRESULTINFO_GET_IMAGE_EXTENSION;
		}

		return $nRet;
	}
	


}