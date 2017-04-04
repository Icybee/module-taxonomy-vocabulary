<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Taxonomy\Vocabulary\Block\ManageBlock;

use function ICanBoogie\app;
use ICanBoogie\Module\Descriptor;

use Brickrouge\A;

use Icybee\Block\ManageBlock\Column;
use Icybee\Modules\Taxonomy\Vocabulary\Vocabulary;

/**
 * Representation of the `scope` column.
 */
class ScopeColumn extends Column
{
	/**
	 * @param Vocabulary $record
	 *
	 * @inheritdoc
	 */
	public function render_cell($record)
	{
		$app = app();

		$scope = $this->manager->module
			->model('scopes')
			->select('constructor')
			->where('vocabulary_id = ?', $record->vocabulary_id)
			->all(\PDO::FETCH_COLUMN);

		if ($scope)
		{
			foreach ($scope as &$constructor)
			{
				$constructor = new A($this->t($app->modules->descriptors[$constructor][Descriptor::TITLE]),
					$app->url_for("admin:$constructor:index"));
			}

			$last = array_pop($scope);

			$includes = $scope
				? $this->t(':list and :last', [ ':list' => \ICanBoogie\shorten(implode(', ', $scope), 128, 1), ':last' => $last ])
				: $this->t(':one', [ ':one' => $last ]);
		}
		else
		{
			$includes = '<em>Aucune portée</em>';
		}

		return '<span class="small">' . $includes . '</span>';
	}
}
