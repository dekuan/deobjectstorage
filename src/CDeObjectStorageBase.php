<?php
namespace dekuan\deobjectstorage;


/**
 *	Class CDeObjectStorageBase
 *	@package dekuan\deobjectstorage
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

	/**
	 *	@return mixed|null
	 */
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