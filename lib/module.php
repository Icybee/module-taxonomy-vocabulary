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

use ICanBoogie\I18n;
use ICanBoogie\Operation;

class Module extends \Icybee\Module
{
	const OPERATION_ORDER = 'order';

	protected function block_order($vid)
	{
		$app = $this->app;
		$document = $app->document;

		$document->js->add(DIR . 'public/order.js');
		$document->css->add(DIR . 'public/order.css');

		$terms = $app->models['taxonomy.terms']->filter_by_vid($vid)->order('term.weight, vtid')->all;

		$rc  = '<form id="taxonomy-order" method="post">';
		$rc .= '<input type="hidden" name="' . Operation::NAME . '" value="' . self::OPERATION_ORDER . '" />';
		$rc .= '<input type="hidden" name="' . Operation::DESTINATION . '" value="' . $this . '" />';
		$rc .= '<input type="hidden" name="' . Operation::KEY . '" value="' . $vid . '" />';
		$rc .= '<ol>';

		foreach ($terms as $term)
		{
			$rc .= '<li>';
			$rc .= '<input type="hidden" name="terms[' . $term->vtid . ']" value="' . $term->weight . '" />';
			$rc .= \ICanBoogie\escape($term->term);
			$rc .= '</li>';
		}

		$rc .= '</ol>';

		$rc .= '<div class="actions">';
		$rc .= '<button class="save">' . $app->translate('label.save') . '</button>';
		$rc .= '</div>';

		$rc .= '</form>';

		return $rc;
	}
}
