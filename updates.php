<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Taxonomy\Vocabulary;

use ICanBoogie\Updater\Update;

/**
 * - Renames column `siteid` as `site_id`.
 *
 * @module taxonomy.vocabulary
 */
class Update20150908 extends Update
{
	public function update_column_site_id()
	{
		$this->module->model
			->assert_has_column('siteid')
			->rename_column('siteid', 'site_id');
	}
}
