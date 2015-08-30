<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Taxonomy\Vocabulary\Block;

use Icybee\Modules\Taxonomy\Vocabulary\Vocabulary;

class ManageBlock extends \Icybee\Block\ManageBlock
{
	/**
	 * Adds the following columns:
	 *
	 * - `vocabulary`
	 * - `scope`
	 */
	protected function get_available_columns()
	{
		return array_merge(parent::get_available_columns(), [

			Vocabulary::VOCABULARY => ManageBlock\VocabularyColumn::class,
			Vocabulary::SCOPE => ManageBlock\ScopeColumn::class

		]);
	}
}
