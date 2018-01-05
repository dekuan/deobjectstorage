<?php

namespace dekuan\deobjectstorage;


/**
 *	Interface IDeObjectStorage
 *	@package dekuan\deobjectstorage
 */
interface IDeObjectStorage
{
	public function uploadByFile( $arrInput, $sKey, Array & $arrReturnValue = null );
	public function uploadByUrl( $arrInput, $sKey, Array & $arrReturnValue = null );
	public function isExistObject( $sKey );
}