<?php

namespace dekuan\deobjectstorage\driver\oss;

use dekuan\delib\CLib;
use dekuan\deobjectstorage\CDeObjectStorageErrCode;

use dekuan\vdata\CConst;
use OSS\Core\OssException;
use OSS\OssClient;



/**
 *	Class CDriverOSSUploader
 *	@package dekuan\deobjectstorage\driver\oss
 */
class CDriverOSSUploader
{
	private $m_arrConfig	= null;


	public function __construct( $arrConfig )
	{
		assert( CLib::IsArrayWithKeys( $arrConfig ) );

		//	...
		$this->m_arrConfig	= $arrConfig;
	}
	public function __destruct()
	{
	}


	/**
	 * 	upload file to OSS
	 *
	 *	@param	string	$sKey
	 *	@param	string	$sLocalFullFilename
	 *	@return int
	 */
	public function uploadFileToOss( $sKey, $sLocalFullFilename )
	{
		if ( ! CLib::IsExistingString( $sKey, true ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADFILETOOSS_PARAM_KEY;
		}
		if ( ! CLib::IsExistingString( $sLocalFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADFILETOOSS_PARAM_LOCAL_FFN;
		}
		if ( ! file_exists( $sLocalFullFilename ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADFILETOOSS_LOCAL_FFN_NOT_EXIST;
		}

		//	...
		$sBucketName	= CLib::GetValEx( $this->m_arrConfig, 'bucket_name', CLib::VARTYPE_STRING, '' );
		if ( ! CLib::IsExistingString( $sBucketName ) )
		{
			return CDeObjectStorageErrCode::ERROR_UPLOADFILETOOSS_INVALID_BUCKET_NAME;
		}

		//	...
		$nRet		= CDeObjectStorageErrCode::ERROR_UPLOADFILETOOSS_FAILED;
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
					$nRet = CDeObjectStorageErrCode::ERROR_UPLOADFILETOOSS_FAILED_UPLOAD;
				}
			}
			catch( OssException $e )
			{
				$nRet = CDeObjectStorageErrCode::ERROR_UPLOADFILETOOSS_EXCEPTION;
			}
		}
		else
		{
			$nRet = CDeObjectStorageErrCode::ERROR_UPLOADFILETOOSS_FAILED_CREATE_CLIENT;
		}

		return $nRet;
	}



	/**
	 *	@return	null | OssClient
	 */
	private function _createOssClientInstance()
	{
		if ( ! CLib::IsArrayWithKeys( $this->m_arrConfig ) )
		{
			return null;
		}

		//	...
		$oRet	= null;

		//	...
		$sAccessKeyId		= CLib::GetValEx( $this->m_arrConfig, 'access_key_id', CLib::VARTYPE_STRING, '' );
		$sAccessKeySecret	= CLib::GetValEx( $this->m_arrConfig, 'access_key_secret', CLib::VARTYPE_STRING, '' );
		$sEndPoint		= CLib::GetValEx( $this->m_arrConfig, 'bucket_url', CLib::VARTYPE_STRING, '' );
		$nTimeOut		= CLib::GetValEx( $this->m_arrConfig, 'http_timeout', CLib::VARTYPE_NUMERIC, 0 );
		$nConnectTimeout	= CLib::GetValEx( $this->m_arrConfig, 'tcp_connect_timeout', CLib::VARTYPE_NUMERIC, 0 );

		//	...
		$oRet = new OssClient( $sAccessKeyId, $sAccessKeySecret, $sEndPoint );
		$oRet->setTimeout( $nTimeOut );
		$oRet->setConnectTimeout( $nConnectTimeout );

		return $oRet;
	}


}