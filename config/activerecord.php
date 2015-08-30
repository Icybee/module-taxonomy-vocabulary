<?php

namespace Icybee\Modules\Taxonomy\Vocabulary;

return [

	'facets' => [

		'taxonomy.vocabulary' => [

			'vocabulary' => Facets\VocabularyCriterion::class,
			'scope' => Facets\ScopeCriterion::class

		]
	]
];
