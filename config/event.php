<?php

namespace Icybee\Modules\Taxonomy\Vocabulary;

use Icybee;

$hooks = Hooks::class . '::';

return [

	Icybee\Modules\Nodes\Operation\SaveOperation::class . '::process' => $hooks . 'on_node_save',
	Icybee\Modules\Nodes\EditBlock::class . '::alter_children' => $hooks . 'on_nodes_editblock_alter_children',
	Icybee\Modules\Nodes\Node::class . '::property' => $hooks . 'get_term',
	Icybee\Modules\Pages\BreadcrumbElement::class . '::render_inner_html:before' => $hooks . 'before_breadcrumb_render_inner_html',
//    Icybee\Modules\Views\Collection::class . '::collect' => $hooks . 'on_collect_views',
//    Icybee\Modules\Views\Provider::class . '::alter_query' => $hooks . 'on_alter_provider_query',

];
