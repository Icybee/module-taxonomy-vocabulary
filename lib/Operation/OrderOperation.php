<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Taxonomy\Vocabulary\Operation;

use ICanBoogie\Errors;
use ICanBoogie\Operation;

use Icybee\Binding\ObjectBindings;

class OrderOperation extends Operation
{
	use ObjectBindings;

	protected function get_controls()
	{
		return array
		(
			self::CONTROL_OWNERSHIP => true
		)

		+ parent::get_controls();
	}

	protected function validate(Errors $errors)
	{
		return !empty($this->request['terms']);
	}

	protected function process()
	{
		$w = 0;
		$weights = array();
		$update = $this->app->models['taxonomy.terms']->prepare('UPDATE {self} SET weight = ? WHERE vtid = ?');

		foreach ($this->request['terms'] as $vtid => $dummy)
		{
			$update->execute(array($w, $vtid));
			$weights[$vtid] = $w++;
		}

		return true;
	}
}