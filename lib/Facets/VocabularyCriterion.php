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

use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\Facets\Criterion\BasicCriterion;

class VocabularyCriterion extends BasicCriterion
{
	public function alter_query_with_value(Query $query, $value)
	{
		if (is_numeric($value))
		{
			return $query->filter_by_vocabulary_id($value);
		}

		return $query->filter_by_vocabulary_slug($value);
	}
}
