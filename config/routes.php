<?php

namespace Icybee\Modules\Taxonomy\Vocabulary;

use ICanBoogie\HTTP\Request;
use Icybee\Routing\RouteMaker as Make;

return Make::admin('taxonomy.vocabulary', Routing\VocabularyAdminController::class, [

	'id_name' => 'vocabulary_id',
	'except' => 'config',
	'actions' => [

		'order' => [ '/{name}/{id}/order', Request::METHOD_ANY ]

	]

]);
