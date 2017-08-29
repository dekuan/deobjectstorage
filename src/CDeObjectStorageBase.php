<?php
namespace dekuan\deobjectstorage;


/**
 * Created by PhpStorm.
 * User: xing
 * Date: August 29, 2017
 */
class CDeObjectStorageBase
{
	protected static $g_arrInstances	= [];

	public function __construct()
	{
	}
	public function __destruct()
	{
	}

	final public static function getInstance()
	{
		$oRet		= null;
		$sClassName	= get_called_class();

		if ( false !== $sClassName )
		{
			if ( ! isset( self::$g_arrInstances[ $sClassName ] ) )
			{
				$oRet = self::$g_arrInstances[ $sClassName ] = new $sClassName();
			}
			else
			{
				$oRet = self::$g_arrInstances[ $sClassName ];
			}
		}

		return $oRet;
	}

	final private function __clone()
	{
	}
}