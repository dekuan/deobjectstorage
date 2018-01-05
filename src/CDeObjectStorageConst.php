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

	//	...
	const DEFAULT_MAX_UPLOAD_FILE_SIZE	= 5 * 1024 * 1024;	//	5M, maximum size of file in bytes allowed to be uploaded


	//
	//	image mime type
	//
	const DEFAULT_ALLOWED_IMAGE_MIME_TYPE_LIST	=
		[
			"image/x-ms-bmp",		//	bmp
			"image/gif",			//	gif
			"image/jpeg",			//	jpg
			"image/png",			//	png
			"application/octet-stream",	//	wbm
		];

	//
	//	video mime type
	//
	const DEFAULT_ALLOWED_VIDEO_MIME_TYPE_LIST	=
		[
			'video/mp4'
		];

	//
	//	audio mime type
	//
	const DEFAULT_ALLOWED_AUDIO_MIME_TYPE_LIST	=
		[
			'audio/mpeg'
		];


}