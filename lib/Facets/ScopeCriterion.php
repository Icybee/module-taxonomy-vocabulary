<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Taxonomy\Vocabulary\Facets;

use ICanBoogie\ActiveRecord;
use ICanBoogie\Facets\Criterion;
use ICanBoogie\ActiveRecord\Query;

class ScopeCriterion extends Criterion
{
	public function alter_query_with_value(Query $query, $value)
	{
		if (!$value)
		{
			return $query;
		}

		$scope_query = $query
			->model
			->models['taxonomy.vocabulary/scopes']
			->select('constructor')
			->where('{alias}.vid = vid');

		return $query->and("? IN($scope_query)", $value);
	}
}
