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
	const PURGE = 'purge';
	const NULL = 'null';

	protected static $strategy;
	protected static $database;

	protected static $_tables = array();
	protected static $_bound = FALSE;

	protected static $_started = FALSE;

	public static function start($strategy = 'null', $database = 'default')
	{
		$allowed_strategies = array(Jamaker_Cleaner::TRUNCATE, Jamaker_Cleaner::DELETE, Jamaker_Cleaner::TRANSACTION, Jamaker_Cleaner::NULL, Jamaker_Cleaner::PURGE);

		if ( ! in_array($strategy, $allowed_strategies))
			throw new Kohana_Exception('Strategy ":strategy" is not allowed, must be one of :strategies', array(':strategy' => $strategy, ':strategies' => join(', ', $allowed_strategies)));

		Jamaker_Cleaner::$strategy = $strategy;
		Jamaker_Cleaner::$database = $database;

		if (Jamaker_Cleaner::$strategy == Jamaker_Cleaner::TRANSACTION)
		{
			Database::instance(Jamaker_Cleaner::$database)->begin();
		}
		elseif ( ! Jamaker_Cleaner::$_bound)
		{
			Jam::global_bind('builder.after_insert', 'Jamaker_Cleaner::log_table');
			Jamaker_Cleaner::$_bound = TRUE;
		}

		Jamaker_Cleaner::started(TRUE);
	}

	public static function started($started = NULL)
	{
		if ($started !== NULL)
		{
			Jamaker_Cleaner::$_started = $started;
		}
		
		return Jamaker_Cleaner::$_started;
	}

	public static function started_insist()
	{
		if ( ! Jamaker_Cleaner::$_started)
			throw new Kohana_Exception('Jamaker Cleaner has not been started, plase start it with Jamaker_Cleaner::start()');

		return TRUE;
	}

	public static function log_table($builder)
	{
		$table = Arr::path($builder->inspect('from'), '0.0');

		if ( ! in_array($table, Jamaker_Cleaner::$_tables))
		{
			Jamaker_Cleaner::$_tables[] = $table;
		}
	}

	public static function save()
	{
		Jamaker_Cleaner::started_insist();

		if (Jamaker_Cleaner::$strategy == Jamaker_Cleaner::TRANSACTION)
		{
			Database::instance(Jamaker_Cleaner::$database)->commit();
		}
		else
		{
			Jamaker_Cleaner::$_tables = array();
		}
	}

	public static function clean_all()
	{
		$all_tables = Database::instance(Jamaker_Cleaner::$database)->list_tables();
		foreach ($all_tables as $table) 
		{
			if ($table != 'schema_version')
			{
				DB::query(NULL, "TRUNCATE `$table`")->execute(Jamaker_Cleaner::$database);
			}
		}
	}

	public static function clean()
	{
		Jamaker_Cleaner::started_insist();

		switch (Jamaker_Cleaner::$strategy) 
		{
			case Jamaker_Cleaner::TRANSACTION:
				Database::instance(Jamaker_Cleaner::$database)->rollback();
			break;

			case Jamaker_Cleaner::TRUNCATE:
				foreach (Jamaker_Cleaner::$_tables as $table) 
				{
					DB::query(NULL, "TRUNCATE `$table`")->execute(Jamaker_Cleaner::$database);
				}
			break;
			
			case Jamaker_Cleaner::DELETE:
				foreach (Jamaker_Cleaner::$_tables as $table) 
				{
					DB::query(NULL, "DELETE FROM `$table`")->execute(Jamaker_Cleaner::$database);
				}
			break;

			case Jamaker_Cleaner::PURGE:
				Jamaker_Cleaner::clean_all();
			break;
		}
		Jamaker_Cleaner::$_tables = array();
	}

} // End Role Model