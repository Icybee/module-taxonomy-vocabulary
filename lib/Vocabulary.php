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

use ICanBoogie\ActiveRecord;
use ICanBoogie\Routing\ToSlug;
use Icybee\Modules\Taxonomy\Terms\Term;

class Vocabulary extends ActiveRecord implements ToSlug
{
	const MODEL_ID = 'taxonomy.vocabulary';

	const VOCABULARY_ID = 'vocabulary_id';
	const SITE_ID = 'site_id';
	const VOCABULARY = 'vocabulary';
	const VOCABULARY_SLUG = 'vocabulary_slug';
	const IS_TAGS = 'is_tags';
	const IS_MULTIPLE = 'is_multiple';
	const IS_REQUIRED = 'is_required';
	const WEIGHT = 'weight';
	const SCOPE = 'scope';

	/**
	 * Identifier of the vocabulary
	 *
	 * @var int
	 */
	public $vocabulary_id;

	/**
	 * Identifier of the site the vocabulary is attached to.
	 *
	 * This value maybe 0, indicating that the vocabulary is not attached to any site.
	 *
	 * @var int
	 */
	public $site_id;

	/**
	 * Name of the vocabulary.
	 *
	 * @var string
	 */
	public $vocabulary;

	/**
	 * Version of the {@vocabulary} property that can be used in URLs. Written in lowercase, it
	 * contains only unaccented letters, numbers and hyphens.
	 *
	 * @var string
	 */
	public $vocabulary_slug;

	/**
	 * Can terms be defined as coma-separated values ?
	 *
	 * @var bool
	 */
	public $is_tags = false;

	/**
	 * Can multiple terms be associated to a node ?
	 *
	 * @var bool
	 */
	public $is_multiple = false;

	/**
	 * Is the vocabulary required for the associated scope ?
	 *
	 * @var bool
	 */
	public $is_required = false;

	/**
	 * Weight of the vocabulary relative to other vocabulary.
	 *
	 * @var int
	 */
	public $weight = 0;

	/**
	 * @inheritdoc
	 */
	public function create_validation_rules()
	{
		return [

			self::VOCABULARY => 'required',
			self::VOCABULARY_SLUG => 'required',

		];
	}

	/**
	 * Removes the `scope` and `terms` properties.
	 */
	public function __sleep()
	{
		$properties = parent::__sleep();

		$properties = array_flip($properties);

		unset($properties['scope']);
		unset($properties['terms']);

		return array_flip($properties);
	}

	public function to_slug()
	{
		return $this->vocabulary_slug;
	}

	/**
	 * Returns the scope of the vocabulary, that is the constructors to which the vocabulary is
	 * associated.
	 *
	 * @return array[]string
	 */
	protected function lazy_get_scope()
	{
		return $this->model->models['taxonomy.vocabulary/scopes']
			->select('constructor')
			->filter_by_vocabulary_id($this->vocabulary_id)
			->all(\PDO::FETCH_COLUMN);
	}

	/**
	 * Returns the terms associated to this vocabulary, ordered by weight.
	 *
	 * @return array[]Term
	 */
	protected function lazy_get_terms()
	{
		$model = $this->model->models['taxonomy.terms'];

		return $model->select('term.*')
			->filter_by_vocabulary_id($this->vocabulary_id)
			->order('weight')
			->all(\PDO::FETCH_CLASS, Term::class, [ $model ]);
	}
}
