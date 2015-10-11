<?php

namespace Icybee\Modules\Taxonomy\Vocabulary;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\Module\Descriptor;

return [

	Descriptor::CATEGORY => 'organize',
	Descriptor::DESCRIPTION => 'Manage vocabulary',
	Descriptor::MODELS => [

		'primary' => [

			Model::SCHEMA => [

				'vocabulary_id' => 'serial',
				'site_id' => 'foreign',
				'vocabulary' => 'varchar',
				'vocabularyslug' => [ 'varchar', 80, 'indexed' => true ],
				'is_tags' => 'boolean',
				'is_multiple' => 'boolean',
				'is_required' => 'boolean',

				/**
				 * Specify the weight of the element used to edit this vosabulary
				 * in the altered edit block of the constructor.
				 */

				'weight' => [ 'integer', 'unsigned' => true ]

			]
		],

		'scopes' => [

			Model::ACTIVERECORD_CLASS => 'ICanBoogie\ActiveRecord',
			Model::CLASSNAME => 'ICanBoogie\ActiveRecord\Model',
			Model::SCHEMA => [

				'vocabulary_id' => [ 'foreign', 'primary' => true ],
				'constructor' => [ 'varchar', 64, 'primary' => true ]

			]
		]
	],

	Descriptor::NS => __NAMESPACE__,
	Descriptor::TITLE => "Vocabulary"

];
