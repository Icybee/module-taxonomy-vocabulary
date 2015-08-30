<?php

namespace Icybee\Modules\Taxonomy\Vocabulary\Element;

use Brickrouge\Element;
use Brickrouge\Text;

/**
 * An element to pick, and add, tags.
 */
class TagsPicker extends Element
{
	/**
	 * @var Text
	 */
	private $selected;

	/**
	 * @var CloudElement
	 */
	private $cloud;

	/**
	 * @param array $attributes
	 */
	public function __construct(array $attributes = [])
	{
		parent::__construct('div', $attributes + [

			Element::CHILDREN => [

				$this->selected = new Text([


				]),

				$this->cloud = new CloudElement('ul', [

					'class' => 'cloud'

				])

			],

			'class' => 'taxonomy-tags widget-bordered'
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function offsetSet($attribute, $value)
	{
		switch ($attribute)
		{
			case Element::OPTIONS:

				$this->cloud[$attribute] = $value;

				break;

			case 'name':
			case 'value':
			case Element::DEFAULT_VALUE:

				$this->selected[$attribute] = $value;

				break;

		}

		parent::offsetSet($attribute, $value);
	}
}
