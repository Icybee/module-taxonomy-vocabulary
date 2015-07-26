<?php

namespace Icybee\Modules\Taxonomy\Vocabulary;

use ICanBoogie\HTTP\Request;
use Icybee\Routing\RouteMaker as Make;

return Make::admin('taxonomy.vocabulary', Routing\VocabularyAdminController::class, [

	'only' => [ 'index', 'create', 'edit', 'order' ],
	'actions' => [

		'order' => [ '/{name}/{id}/order', Request::METHOD_ANY ]

	]

]);
