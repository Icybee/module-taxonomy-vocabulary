<?php

namespace Icybee\Modules\Taxonomy\Vocabulary\Block\ManageBlock;

use ICanBoogie\Module\Descriptor;
use Icybee\Block\ManageBlock\Column;

/**
 * Representation of the `scope` column.
 */
class ScopeColumn extends Column
{
	public function render_cell($record)
	{
		$app = \ICanBoogie\app();

		$scope = $this->manager->module
			->model('scopes')
			->select('constructor')
			->where('vocabulary_id = ?', $record->vocabulary_id)
			->all(\PDO::FETCH_COLUMN);

		if ($scope)
		{
			$context = $app->site->path;
			// TODO-20150310: use a route
			foreach ($scope as &$constructor)
			{
				$constructor = '<a href="' . $context . '/admin/' . $constructor . '">'
					. $this->t($app->modules->descriptors[$constructor][Descriptor::TITLE])
					. '</a>';
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
