<?php

namespace Pheasant\Tests\Examples;

use \Pheasant\DomainObject;
use \Pheasant\Types;
use \Pheasant\Mapper\RowMapper;

class Animal extends DomainObject
{
	public static function initialize($builder, $pheasant)
	{
		$pheasant
			->register(__CLASS__, new RowMapper('animal'));

		$builder
			->properties(array(
				'id' => new Types\Integer(11, 'primary auto_increment'),
				'type' => new Types\String(255, 'required default=llama'),
			));
	}
}
