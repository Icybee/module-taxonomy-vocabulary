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

use Brickrouge\Button;
use Brickrouge\Element;
use Brickrouge\Form;
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

		$terms = $app->models['taxonomy.terms']->filter_by_vid($vid)->order('term.weight, term_id')->all;

		$rc = '<ol>';

		foreach ($terms as $term)
		{
			$rc .= '<li>';
			$rc .= '<input type="hidden" name="terms[' . $term->vtid . ']" value="' . $term->weight . '" />';
			$rc .= \ICanBoogie\escape($term->term);
			$rc .= '</li>';
		}

		$rc .= '</ol>';

		return new Form([

			Form::ACTIONS => [

				new Button('Save', [ 'type' => 'submit', 'class' => 'btn btn-primary' ])

			],

			Form::HIDDENS => [

				Operation::NAME => self::OPERATION_ORDER,
				Operation::DESTINATION => $this,
				Operation::KEY => $vid

			],

			Element::INNER_HTML => $rc,

			'id' => 'taxonomy-order'

		]);
	}
}
