<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Taxonomy\Vocabulary\Block;

use ICanBoogie\I18n;
use ICanBoogie\Module\Descriptor;

use Brickrouge\Element;
use Brickrouge\Form;
use Brickrouge\Widget;

use Icybee\Modules\Nodes\TitleSlugCombo;
use Icybee\Modules\Taxonomy\Vocabulary\Vocabulary;

class EditBlock extends \Icybee\Block\EditBlock
{
	static protected function add_assets(\Brickrouge\Document $document)
	{
		parent::add_assets($document);

		$document->css->add(\Icybee\Modules\Taxonomy\Vocabulary\DIR . 'public/admin.css');
	}

	protected function lazy_get_attributes()
	{
		return \ICanBoogie\array_merge_recursive
		(
			parent::lazy_get_attributes(), array
			(
				Element::GROUPS => array
				(
					'settings' => array
					(
						'title' => 'Options',
						'weight' => 100
					)
				)
			)
		);
	}

	protected function lazy_get_children()
	{
		return array_merge
		(
			parent::lazy_get_children(), array
			(
				Vocabulary::VOCABULARY => new TitleSlugCombo
				(
					array
					(
						Form::LABEL => 'title',
						Element::REQUIRED => true
					)
				),

				Vocabulary::SCOPE => $this->get_control__scope(),

				Vocabulary::IS_TAGS => new Element
				(
					Element::TYPE_CHECKBOX, array
					(

						Element::LABEL => 'is_tags',
						Element::GROUP => 'settings',
						Element::DESCRIPTION => 'is_tags'
					)
				),

				Vocabulary::IS_MULTIPLE => new Element
				(
					Element::TYPE_CHECKBOX, array
					(
						Element::LABEL => 'Multiple appartenance',
						Element::GROUP => 'settings',
						Element::DESCRIPTION => "Les enregistrements peuvent appartenir à
						plusieurs terms du vocabulaire (c'est toujours le cas pour les
						<em>étiquettes</em>)"
					)
				),

				Vocabulary::IS_REQUIRED => new Element
				(
					Element::TYPE_CHECKBOX, array
					(
						Element::LABEL => 'Requis',
						Element::GROUP => 'settings',
						Element::DESCRIPTION => 'Au moins un terme de ce vocabulaire doit être
						sélectionné.'
					)
				),

				Vocabulary::SITE_ID => $this->get_control__site()
			)
		);
	}

	protected function get_control__scope()
	{
		$scope_options = array();
		$modules = $this->app->modules;

		foreach ($modules->descriptors as $module_id => $descriptor)
		{
			if ($module_id == 'nodes' || !isset($modules[$module_id]))
			{
				continue;
			}

			$is_instance = $modules->is_inheriting($module_id, 'nodes');

			if (!$is_instance)
			{
				continue;
			}

			$scope_options[$module_id] = $this->t($module_id, [], [

				'scope' => 'module_title',
				'default' => $descriptor[Descriptor::TITLE]

			]);
		}

		uasort($scope_options, 'ICanBoogie\unaccent_compare_ci');

		$scope_value = null;
		$vid = $this->values[Vocabulary::VOCABULARY_ID];

		if ($vid)
		{
			$scope_value = $this->module->model('scopes')->select('constructor, 1')->filter_by_vocabulary_id($vid)->pairs;

			$this->values[Vocabulary::SCOPE] = $scope_value;
		}

		return new Element(Element::TYPE_CHECKBOX_GROUP,[

			Form::LABEL => 'scope',
			Element::OPTIONS => $scope_options,
			Element::REQUIRED => true,

			'class' => 'list combo',
			'value' => $scope_value

		]);
	}

	protected function get_control__site()
	{
		$app = $this->app;

		if (!$app->user->has_permission(\Icybee\Modules\Nodes\Module::PERMISSION_MODIFY_BELONGING_SITE))
		{
			return;
		}

		// TODO-20100906: this should be added by the "sites" modules using the alter event.

		return new Element
		(
			'select', array
			(
				Form::LABEL => 'site_id',
				Element::OPTIONS => array
				(
					null => ''
				)
				+ $app->models['sites']->select('site_id, IF(admin_title != "", admin_title, concat(title, ":", language))')->order('admin_title, title')->pairs,

				Element::DEFAULT_VALUE => $app->site_id,
				Element::GROUP => 'admin',
				Element::DESCRIPTION => 'site_id'
			)
		);
	}
}
