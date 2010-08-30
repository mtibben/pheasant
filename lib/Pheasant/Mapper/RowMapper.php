<?php

namespace Pheasant\Mapper;

use Pheasant;
use Pheasant\Collection;
use Pheasant\Query\Query;
use Pheasant\Finder\Finder;

/**
 * A generic mapper for mapping domain objects to rows in a table
 */
class RowMapper extends AbstractMapper implements Finder
{
	private $_table;
	private $_tableName;
	private $_pheasant;
	private $_connection;

	/**
	 * Constructor
	 */
	public function __construct($table, $connection=null, $pheasant=null)
	{
		$this->_pheasant = $pheasant ?: Pheasant::instance();
		$this->_connection = $connection ?: $this->_pheasant->connection();
		$this->_tableName = $table;
	}

	/**
	 * Returns a table instance
	 */
	private function table()
	{
		if(!isset($this->_table))
			$this->_table = $this->_connection->table($this->_tableName);

		return $this->_table;
	}

	/**
	 * Generates a sequence for a property
	 * @return int
	 */
	private function sequence($property)
	{
		$sequence = $property->type->sequence;

		// generate if needed
		if(!is_string($sequence))
			$sequence = sprintf("%s_%s_seq",
				$this->_tableName, $property->name);

		return $this->_connection->sequencePool()->next($sequence);
	}

	/**
	 * @see AbstractMapper::insert()
	 */
	protected function insert($object)
	{
		$schema = $object->schema();

		// generate any sequences that need generating
		foreach($object->identity() as $key=>$property)
		{
			if($property->options->sequence)
				$object->set($key, $this->sequence($property));
		}

		$result = $this->table()->insert($object->changes());

		// check for auto-increment
		foreach($schema->properties() as $key=>$property)
		{
			if($property->options->auto_increment)
				$object->{$key} = $result->lastInsertId();
		}
	}

	/**
	 * @see AbstractMapper::update()
	 */
	protected function update($object, $changes)
	{
		$schema = $object->schema();
		$result = $this->table()->update($changes,
			$object->identity()->toArray());

		// check for auto-increment
		foreach($object->identity() as $key=>$property)
		{
			if($property->options->auto_increment)
				$object->{$key} = $result->lastInsertId();
		}
	}

	/* (non-phpdoc)
	 * @see Mapper::query()
	 */
	public function query($sql=null, $params=array())
	{
		$query = new Query();
		$query->from($this->_tableName);

		// add optional where clause
		if($sql) $query->where($sql, $params);

		return $query;
	}

	/* (non-phpdoc)
	 * @see Mapper::find()
	 */
	public function find($class, $sql=null, $params=array())
	{
		var_dump(func_get_args());

		return new Collection($class, $this->query($sql, $params));
	}
}