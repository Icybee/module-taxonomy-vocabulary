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

use Icybee\Block\ManageBlock\Column;
use Icybee\Block\ManageBlock\EditDecorator;
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
		$vid = $record->vocabulary_id;
		$terms = $record->model->models['taxonomy.terms']
			->select('term')
			->filter_by_vocabulary_id($vid)
			->order('term.weight, term')
			->all(\PDO::FETCH_COLUMN);

		$order_link = null;

		if ($terms)
		{
			$last = array_pop($terms);

			$includes = $terms
				? $this->t('Including: !list and !last', [ '!list' => \ICanBoogie\shorten(implode(', ', $terms), 128, 1), '!last' => $last ])
				: $this->t('Including: !entry', [ '!entry' => $last ]);

			$order_url = $this->app->url_for("admin:{$this->manager->module->id}:order", [ 'vocabulary_id' => $vid ]);

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
