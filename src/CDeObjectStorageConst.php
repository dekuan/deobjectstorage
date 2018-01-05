<?php

namespace dekuan\deobjectstorage;


/**
 *	Class CDeObjectStorageConst
 *	@package dekuan\deobjectstorage
 */
class CDeObjectStorageConst
{
	const OBJECT_TYPE_FILE			= 1;	//	files
	const OBJECT_TYPE_IMAGE			= 2;	//	image, default
	const OBJECT_TYPE_VIDEO			= 3;	//	video
	const OBJECT_TYPE_AUDIO			= 4;	//	image, default

	//	...
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

}