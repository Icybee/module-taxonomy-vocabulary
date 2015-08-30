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

use ICanBoogie\Modules;

use Icybee\Binding\PrototypedBindings;
use Icybee\Modules\Nodes\Module as NodesModule;

class SaveOperation extends \ICanBoogie\Module\Operation\SaveOperation
{
	use PrototypedBindings;

	protected function lazy_get_properties()
	{
		$request = $this->request;
		$properties = parent::lazy_get_properties();

		if ($request['scope'])
		{
			$properties['scope'] = $request['scope'];
		}

		$app = $this->app;

		if (!$this->key || !$app->user->has_permission(NodesModule::PERMISSION_MODIFY_BELONGING_SITE))
		{
			$properties['siteid'] = $app->site_id;
		}

		return $properties;
	}
}
