<?php

namespace Icybee\Modules\Taxonomy\Vocabulary\Routing;

use Icybee\Routing\AdminController;

class VocabularyAdminController extends AdminController
{
	protected function action_order($vid)
	{
		$this->view->content = $this->module->getBlock('order', $vid);
		$this->view['block_name'] = 'order';
	}
}
