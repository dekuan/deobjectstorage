<?php
namespace dekuan\deobjectstorage;


/**
 * Created by PhpStorm.
 * User: xing
 * Date: August 29, 2017
 */
interface IDeObjectStorage
{
	public function uploadByFile( $arrInput, $sKey, & $arrReturnValue = null );
	public function uploadByUrl( $arrInput, $sKey, & $arrReturnValue = null );
	public function isExistImage( $sKey );

}