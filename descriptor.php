<?php

namespace Icybee\Modules\Taxonomy\Vocabulary;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\Module\Descriptor;

return array
(
	Descriptor::CATEGORY => 'organize',
	Descriptor::DESCRIPTION => 'Manage vocabulary',
	Descriptor::MODELS => array
	(
		'primary' => array
		(
			Model::T_SCHEMA => array
			(
				'fields' => array
				(
					'vid' => 'serial',
					'siteid' => 'foreign',
					'vocabulary' => 'varchar',
					'vocabularyslug' => array('varchar', 80, 'indexed' => true),
					'is_tags' => 'boolean',
					'is_multiple' => 'boolean',
					'is_required' => 'boolean',

					/**
					 * Specify the weight of the element used to edit this vosabulary
					 * in the altered edit block of the constructor.
					 */

					'weight' => array('integer', 'unsigned' => true)
				)
			)
		),

		'scopes' => array
		(
			Model::ACTIVERECORD_CLASS => 'ICanBoogie\ActiveRecord',
			Model::CLASSNAME => 'ICanBoogie\ActiveRecord\Model',
			Model::T_SCHEMA => array
			(
				'fields' => array
				(
					'vid' => array('foreign', 'primary' => true),
					'constructor' => array('varchar', 64, 'primary' => true)
				)
			)
		)
	),

	Descriptor::NS => __NAMESPACE__,
	Descriptor::REQUIRES => array
	(
// 		'taxonomy.terms' => '1.x'
	),

	Descriptor::TITLE => 'Vocabulary',
	Descriptor::VERSION => '1.0'
);