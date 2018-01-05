<?php

namespace dekuan\deobjectstorage\driver\oss;

use dekuan\deobjectstorage\CDeObjectStorageConst;
use dekuan\delib\CLib;
use dekuan\deobjectstorage\CDeObjectStorageErrCode;
use dekuan\vdata\CConst;


/**
 *	Class CDriverOSSLibImage
 *	@package dekuan\deobjectstorage\driver
 */
class CDriverOSSLibImage
{
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
			return CDeObjectStorageErrCode::ERROR_CONVERTIMAGETOJPEG_PARAM_SRC_FFN;
		}
		if ( ! is_numeric( $nQuality ) || $nQuality < 0 || $nQuality > 100 )
		{
			return CDeObjectStorageErrCode::ERROR_CONVERTIMAGETOJPEG_PARAM_QUALITY;
		}

		//	...
		$nRet = CDeObjectStorageErrCode::ERROR_CONVERTIMAGETOJPEG_FAILED;

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
						$nRet = CDeObjectStorageErrCode::ERROR_CONVERTIMAGETOJPEG_SAVE;
					}

					@imagedestroy( $hHandle );
					$hHandle = null;
				}
				else
				{
					$nRet = CDeObjectStorageErrCode::ERROR_CONVERTIMAGETOJPEG_LOAD_IMAGE;
				}
			}
			else
			{
				$nRet = CDeObjectStorageErrCode::ERROR_CONVERTIMAGETOJPEG_GET_IMAGE_TYPE;
			}
		}
		catch ( \Exception $e )
		{
			$nRet = CDeObjectStorageErrCode::ERROR_CONVERTIMAGETOJPEG_EXCEPTION;
		}

		return $nRet;
	}



}