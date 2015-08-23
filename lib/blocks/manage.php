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

class ManageBlock extends \Icybee\ManageBlock
{
	/**
	 * Adds the following columns:
	 *
	 * - `vocabulary`
	 * - `scope`
	 */
	protected function get_available_columns()
	{
		return array_merge(parent::get_available_columns(), array
		(
			Vocabulary::VOCABULARY => __CLASS__ . '\VocabularyColumn',
			Vocabulary::SCOPE => __CLASS__ . '\ScopeColumn'
		));
	}
}

namespace Icybee\Modules\Taxonomy\Vocabulary\ManageBlock;

use ICanBoogie\I18n;
use ICanBoogie\Module\Descriptor;

use Icybee\ManageBlock\Column;
use Icybee\ManageBlock\EditDecorator;
use Icybee\Modules\Taxonomy\Vocabulary\Vocabulary;

/**
 * Representation of the `vocabulary` column.
 */
class VocabularyColumn extends Column
{
	/**
	 * @param Vocabulary $record
	 *
	 * @inheritdoc
	 */
	public function render_cell($record)
	{
		$vid = $record->vid;
		$terms = $record->model->models['taxonomy.terms']
		->select('term')
		->filter_by_vid($vid)
		->order('term.weight, term')
		->all(\PDO::FETCH_COLUMN);

		$order_link = null;

		if ($terms)
		{
			$last = array_pop($terms);

			$includes = $terms
				? $this->t('Including: !list and !last', [ '!list' => \ICanBoogie\shorten(implode(', ', $terms), 128, 1), '!last' => $last ])
				: $this->t('Including: !entry', [ '!entry' => $last ]);

			$order_url = $this->app->url_for("admin:{$this->manager->module->id}:order", [ 'vid' => $vid ]);

			$order_link = <<<EOT
<a href="$order_url">Order the terms</a>
EOT;
		}
		else
		{
			$includes = '<em class="light">The vocabulary is empty</em>';
		}

		if ($order_link)
		{
			$order_link = " &ndash; {$order_link}";
		}

		return new EditDecorator($record->vocabulary, $record) . <<<EOT
<br /><span class="small">{$includes}{$order_link}</span>
EOT;
	}
}

/**
 * Representation of the `scope` column.
 */
class ScopeColumn extends Column
{
	public function render_cell($record)
	{
		$app = \ICanBoogie\app();

		$scope = $this->manager->module->model('scopes')
		->select('constructor')
		->where('vid = ?', $record->vid)
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
