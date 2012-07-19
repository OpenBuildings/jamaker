<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Cleanup database
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jamaker_Cleaner {

	const TRUNCATE = 'truncate';
	const DELETE = 'delete';
	const TRANSACTION = 'transaction';
	const NULL = 'null';

	protected static $_strategy;
	protected static $_database;

	protected static $_started = FALSE;
	protected static $_saved = FALSE;

	public static function start($strategy = 'null', $database = 'default')
	{
		$allowed_strategies = array(Jamaker_Cleaner::TRUNCATE, Jamaker_Cleaner::DELETE, Jamaker_Cleaner::TRANSACTION, Jamaker_Cleaner::NULL);

		if ( ! in_array($strategy, $allowed_strategies))
			throw new Kohana_Exception('Strategy ":strategy" is not allowed, must be one of :strategies', array(':strategy' => $strategy, ':strategies' => join(', ', $allowed_strategies)));

		Jamaker_Cleaner::$_strategy = $strategy;
		Jamaker_Cleaner::$_database = $database;

		if (Jamaker_Cleaner::$_strategy == Jamaker_Cleaner::TRANSACTION)
		{
			Database::instance(Jamaker_Cleaner::$_database)->begin();
		}

		Jamaker_Cleaner::started(TRUE);
		Jamaker_Cleaner::saved(FALSE);
	}

	public static function started($started = NULL)
	{
		if ($started !== NULL)
		{
			Jamaker_Cleaner::$_started = $started;
		}
		
		return Jamaker_Cleaner::$_started;
	}

	public static function saved($saved = NULL)
	{
		if ($saved !== NULL)
		{
			Jamaker_Cleaner::$_saved = $saved;
		}
		
		return Jamaker_Cleaner::$_saved;
	}


	public static function started_insist()
	{
		if ( ! Jamaker_Cleaner::$_started)
			throw new Kohana_Exception('Jamaker Cleaner has not been started, plase start it with Jamaker_Cleaner::start()');

		return TRUE;
	}

	public static function save()
	{
		Jamaker_Cleaner::started_insist();
		Jamaker_Cleaner::saved(TRUE);


		if (Jamaker_Cleaner::$_strategy == Jamaker_Cleaner::TRANSACTION)
		{
			Database::instance(Jamaker_Cleaner::$_database)->commit();
		}
	}

	public static function tables()
	{
		$tables = Database::instance(Jamaker_Cleaner::$_database)->list_tables();

		if (($key = array_search('schema_version', $tables)) !== FALSE)
		{
			unset($tables[$key]);
		}

		return $tables;
	}

	public static function purge()
	{
		Jamaker_Cleaner::$_saved = FALSE;

		foreach (Jamaker_Cleaner::tables() as $table) 
		{
			DB::query(NULL, "TRUNCATE `$table`")->execute(Jamaker_Cleaner::$_database);
		}
	}

	public static function clean()
	{
		Jamaker_Cleaner::started_insist();

		switch (Jamaker_Cleaner::$_strategy) 
		{
			case Jamaker_Cleaner::TRANSACTION:
				Database::instance(Jamaker_Cleaner::$_database)->rollback();
			break;

			case Jamaker_Cleaner::TRUNCATE:
				if ( ! Jamaker_Cleaner::saved())
				{
					foreach (Jamaker_Cleaner::tables() as $table) 
					{
						DB::query(NULL, "TRUNCATE `$table`")->execute(Jamaker_Cleaner::$_database);
					}
				}
			break;
			
			case Jamaker_Cleaner::DELETE:
				if ( ! Jamaker_Cleaner::saved())
				{
					foreach (Jamaker_Cleaner::tables() as $table) 
					{
						DB::query(NULL, "DELETE FROM `$table`")->execute(Jamaker_Cleaner::$_database);
					}
				}
			break;
		}
		Jamaker_Cleaner::saved(FALSE);
	}

} // End Role Model