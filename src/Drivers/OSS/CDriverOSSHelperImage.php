<?php

namespace dekuan\deobjectstorage\driver;

use dekuan\deobjectstorage\CDeObjectStorageConst;
use dekuan\delib\CLib;
use dekuan\deobjectstorage\CDeObjectStorageErrCode;
use dekuan\vdata\CConst;


/**
 *	Class CDriverOSSHelperImage
 *	@package dekuan\deobjectstorage\driver
 */
class CDriverOSSHelperImage
{
	/**
	 *	@param	string	$sFullFilename
	 *	@return int
	 */
	static function checkImage( $sFullFilename )
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

		if ( filesize( $sFullFilename ) < CDeObjectStorageConst::MAX_UPLOAD_FILE_SIZE )
		{
			if ( CDriverOSSHelperImage::isAllowedImageTypeByFullFilename( $sFullFilename ) )
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
	
	/**
	 *	@param	string	$sSrcFullFilename
	 *	@param	string	$sDstFullFilename
	 *	@param	int	$nQuality
	 *	@return int
	 */
	static function convertImageToJpeg( $sSrcFullFilename, $sDstFullFilename = null, $nQuality = 80 )
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

	/**
	 *	@param	string	$sFullFilename
	 *	@return	string
	 */
	static function getImageExtension( $sFullFilename )
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
				$sRet = CDriverOSSHelperImage::getImageExtensionByType( @ exif_imagetype( $sFullFilename ) );
			}
		}

		return $sRet;
	}

	/**
	 *	@param	int	$nImageType
	 *	@return	string
	 */
	static function getImageExtensionByType( $nImageType )
	{
		$sRet	= '';

		if ( is_numeric( $nImageType ) )
		{
			if ( array_key_exists( $nImageType, CDeObjectStorageConst::ALLOWED_IMAGE_TYPE ) )
			{
				$sRet = CDeObjectStorageConst::ALLOWED_IMAGE_TYPE[ $nImageType ];
			}
		}

		return $sRet;
	}
	
	/**
	 *	@param	string	$sFullFilename
	 *	@return bool
	 */
	static function isAllowedImageTypeByFullFilename( $sFullFilename )
	{
		return ( CLib::IsExistingString( $sFullFilename ) &&
			file_exists( $sFullFilename ) &&
			self::isAllowedImageType( @ exif_imagetype( $sFullFilename ) ) );
	}
	
	/**
	 *	@param	int	$nImageType
	 *	@return bool
	 */
	static function isAllowedImageType( $nImageType )
	{
		return ( is_numeric( $nImageType ) &&
			array_key_exists( $nImageType, CDeObjectStorageConst::ALLOWED_IMAGE_TYPE ) );
	}



}