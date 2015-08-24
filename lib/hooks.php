<?php

namespace Icybee\Modules\Taxonomy\Vocabulary;

use ICanBoogie\Event;
use ICanBoogie\I18n;

use Brickrouge\Element;
use Brickrouge\Form;
use Brickrouge\Text;

use Icybee\Modules\Pages\BreadcrumbElement;
//use Icybee\Modules\Views\ActiveRecordProvider;
use Icybee\Modules\Views\Collection as ViewsCollection;
//use Icybee\Modules\Views\Provider;
use Icybee\Modules\Nodes\Node;

class Hooks
{
	static private $vocabularies_cache;

//	static public function get_term(\ICanBoogie\Prototyped\PropertyEvent $event, \Icybee\Modules\Nodes\Node $target)
	static public function get_term(\Icybee\Modules\Nodes\Node $target, $property)
	{
		$constructor = $target->constructor;
		$models = $target->model->models;

		if (isset(self::$vocabularies_cache[$constructor]))
		{
			$vocabularies = self::$vocabularies_cache[$constructor];

			if ($vocabularies === false)
			{
				return;
			}
		}
		else
		{
			self::$vocabularies_cache[$constructor] = $vocabularies = $models['taxonomy.vocabulary']
			->join(':taxonomy.vocabulary/scopes')
			->where('siteid = 0 OR siteid = ?', $target->siteid)
			->filter_by_constructor((string) $constructor)
			->order('siteid DESC')
			->all;
		}

		if (!$vocabularies)
		{
			return;
		}

		/* @var $vocabulary Vocabulary */

		$vocabulary = null;

		foreach ($vocabularies as $v)
		{
			if ($property != $v->vocabularyslug)
			{
				continue;
			}

			$vocabulary = $v;

			break;
		}

		#
		# The property doesn't match the slug of any vocabulary associated with the record type.
		#
		if (!$vocabulary)
		{
			return;
		}

		$prototype = $target->prototype;
		$terms_model = $models['taxonomy.terms'];
		$getters = [];

		foreach ($vocabularies as $vocabulary)
		{
			$slug = $vocabulary->to_slug();

			$prototype["lazy_get_$slug"] = $getters[$slug] = function(Node $node) use($terms_model, $vocabulary) {

				static $vtid_by_nid;

				if (!$vtid_by_nid)
				{
					$vtid_by_nid = $terms_model
					->select('nid, vtid')
					->join(':taxonomy.terms/nodes')
					->filter_by_vid($vocabulary->vid)
					->all(\PDO::FETCH_GROUP | \PDO::FETCH_COLUMN);

					#
					# Warming up ActiveRecord's cache with terms.
					#

					$vtids = [];

					foreach ($vtid_by_nid as $v)
					{
						$vtids = array_merge($vtids, $v);
					}

					$vtids = array_unique($vtids);

					$terms_model->find($vtids);
				}

				$nid = $node->nid;

				if (empty($vtid_by_nid[$nid]))
				{
					// TODO-20140921: is_required => create a fake "uncategorized" instance

					return;
				}

				$terms = $terms_model->find($vtid_by_nid[$nid]);

				return ($vocabulary->is_multiple || $vocabulary->is_tags) ? $terms : reset($terms);

			};
		}

		return $getters[$property]($target);
	}

	static public function on_nodes_editblock_alter_children(Event $event, \Icybee\Modules\Nodes\EditBlock $block)
	{
		$app = self::app();

		$document = $app->document;

		$document->css->add(DIR . 'public/support.css');
		$document->js->add(DIR . 'public/support.js');

		$vocabularies = $app->models['taxonomy.vocabulary']
		->join('INNER JOIN {self}__scopes USING(vid)')
		->where('constructor = ? AND (siteid = 0 OR siteid = ?)', (string) $event->module, $app->site_id)
		->order('weight')
		->all;

		// TODO-20101104: use Brickrouge\Form::VALUES instead of setting the 'values' of the elements.
		// -> because 'properties' are ignored, and that's bad.

		$terms_model = $app->models['taxonomy.terms'];
		$nodes_model = $app->models['taxonomy.terms/nodes'];

		$nid = $event->key;
		$identifier_base = 'vocabulary[vid]';
		$children = &$event->children;

		foreach ($vocabularies as $vocabulary)
		{
			$vid = $vocabulary->vid;;

			$identifier = $identifier_base . '[' . $vid . ']';

			if ($vocabulary->is_multiple)
			{
				$options = $terms_model->select('term, count(nid)')
				->join('inner join {self}__nodes using(vtid)')
				->filter_by_vid($vid)
				->group('term')->order('term')->pairs;

				$value = $nodes_model->select('term')
				->filter_by_vid_and_nid($vid, $nid)
				->order('term')
				->all(\PDO::FETCH_COLUMN);
				$value = implode(', ', $value);

				$label = $vocabulary->vocabulary;

				$children[] = new Element
				(
					'div', array
					(
						Form::LABEL => $label,

						Element::GROUP => 'organize',
						Element::WEIGHT => 100,

						Element::CHILDREN => array
						(
							new Text
							(
								array
								(
									'value' => $value,
									'name' => $identifier
								)
							),

							new CloudElement
							(
								'ul', array
								(
									Element::OPTIONS => $options,
									'class' => 'cloud'
								)
							)
						),

						'class' => 'taxonomy-tags widget-bordered'
					)
				);
			}
			else
			{
				$options = $terms_model->select('term.vtid, term')->filter_by_vid($vid)->order('term')->pairs;

				if (!$options)
				{
					//continue;
				}

				$value = $nodes_model->select('term_node.vtid')->filter_by_vid_and_nid($vid, $nid)->order('term')->rc;

				$edit_url = $app->site->path . '/admin/taxonomy.vocabulary/' . $vocabulary->vid . '/edit';
				$children[$identifier] = new Element
				(
					'select', array
					(
						Form::LABEL => $vocabulary->vocabulary,
						Element::GROUP => 'organize',
						Element::OPTIONS => array(null => '') + $options,
						Element::REQUIRED => $vocabulary->is_required,
						Element::INLINE_HELP => '<a href="' . $edit_url . '">' . $app->translate('Edit the vocabulary <q>!vocabulary</q>', array('!vocabulary' => $vocabulary->vocabulary)) . '</a>.',

						'value' => $value
					)
				);
			}
		}

		// FIXME: There is no class to create a _tags_ element. They are created using a collection
		// of objects in a div, so the key is a numeric, not an identifier.

		$event->attributes[Element::GROUPS]['organize'] = array
		(
			'title' => 'Organization',
			'weight' => 500
		);
	}

	static public function on_node_save(\ICanBoogie\Operation\ProcessEvent $event, \Icybee\Modules\Nodes\SaveOperation $target)
	{
		$app = \ICanBoogie\app();

		$name = 'vocabulary';
		$request = $event->request;
		$vocabularies = $request[$name];

		if (!$vocabularies)
		{
			return;
		}

		$nid = $event->rc['key'];
		$vocabularies = $vocabularies['vid'];

		#
		# on supprime toutes les liaisons pour cette node
		#

		$vocabulary_model = $app->models['taxonomy.vocabulary'];
		$terms_model = $app->models['taxonomy.terms'];
		$nodes_model = $app->models['taxonomy.terms/nodes'];

		$nodes_model->where('nid = ?', $nid)->delete();

		#
		# on crée maintenant les nouvelles liaisons
		#

		foreach ($vocabularies as $vid => $values)
		{
			if (!$values)
			{
				continue;
			}

			$vocabulary = $vocabulary_model[$vid];

			if ($vocabulary->is_tags)
			{
				#
				# because tags are provided as a string with coma separated terms,
				# we need to get/created terms id before we can update the links between
				# terms and nodes
				#

				$terms = explode(',', $values);
				$terms = array_map('trim', $terms);

				$values = array();

				foreach ($terms as $term)
				{
					$vtid = $terms_model->select('vtid')->where('vid = ? and term = ?', $vid, $term)->rc;

					// FIXME-20090127: only users with 'create tags' permissions should be allowed to create tags

					if (!$vtid)
					{
						$vtid = $terms_model->save
						(
							array
							(
								'vid' => $vid,
								'term' => $term
							)
						);
					}

					$values[] = $vtid;
				}
			}

			foreach ((array) $values as $vtid)
			{
				$nodes_model->insert
				(
					array
					(
						'vtid' => $vtid,
						'nid' => $nid
					),

					array
					(
						'ignore' => true
					)
				);
			}
		}
	}

	/*
	static public function on_collect_views(ViewsCollection\CollectEvent $event, ViewsCollection $target)
	{
		global $core;

		$vocabulary = $core->models['taxonomy.vocabulary']->all;
		$collection = &$event->collection;

		foreach ($vocabulary as $v)
		{
			$scope = $v->scope;
			$vocabulary_name = $v->vocabulary;
			$vocabulary_slug = $v->vocabularyslug;

			foreach ($scope as $constructor)
			{
				$view_home = $constructor . '/home';
				$view_home = isset($collection[$view_home]) ? $collection[$view_home] : null;

				$view_list = $constructor . '/list';
				$view_list = isset($collection[$view_list]) ? $collection[$view_list] : null;

				if ($view_home)
				{
					$collection["$constructor/vocabulary/$vocabulary_slug/vocabulary-home"] = array
					(
						'title' => 'Home for vocabulary %name',
						'title args' => array('name' => $v->vocabulary),
						'taxonomy vocabulary' => $v
					)

					+ $view_home;
				}

				if ($view_list)
				{
					$collection["$constructor/vocabulary/$vocabulary_slug/list"] = array
					(
						'title' => 'Records list, in vocabulary %vocabulary and a term',
						'title args' => array('vocabulary' => $vocabulary_name),
						'taxonomy vocabulary' => $v
					)

					+ $view_list;
				}

				foreach ($v->terms as $term)
				{
					$term_name = $term->term;
					$term_slug = $term->termslug;

					if ($view_home)
					{
						$collection["$constructor/vocabulary/$vocabulary_slug/$term_slug/home"] = array
						(
							'title' => 'Records home, in vocabulary %vocabulary and term %term',
							'title args' => array('vocabulary' => $vocabulary_name, 'term' => $term_name),
							'taxonomy vocabulary' => $v,
							'taxonomy term' => $term,
						)

						+ $view_home;
					}

					if ($view_list)
					{
						$collection["$constructor/vocabulary/$vocabulary_slug/$term_slug/list"] = array
						(
							'title' => 'Records list, in vocabulary %vocabulary and term %term',
							'title args' => array('vocabulary' => $vocabulary_name, 'term' => $term_name),
							'taxonomy vocabulary' => $v,
							'taxonomy term' => $term
						)

						+ $view_list;
					}
				}
			}
		}
	}

	static public function on_alter_provider_query(\Icybee\Modules\Views\ActiveRecordProvider\AlterQueryEvent $event, \Icybee\Modules\Views\ActiveRecordProvider $provider)
	{
		global $core;

// 		var_dump($event->view);

		$options = $event->view->options;

		if (isset($options['taxonomy vocabulary']) && isset($options['taxonomy term']))
		{
			return self::for_vocabulary_and_term($event, $provider, $options, $options['taxonomy vocabulary'], $options['taxonomy term']);
		}

		if (empty($event->view->options['taxonomy vocabulary']))
		{
			return;
		}

		$vocabulary = $event->view->options['taxonomy vocabulary'];
		$condition = $vocabulary->vocabularyslug . 'slug';

		#
		# FIXME-20121226: It has to be known that the conditions is `<vocabularyslug>slug`.
		#
		# is condition is required by "in vocabulary and a term", but we don't check that, which
		# can cause problems when the pattern of the page is incorrect e.g. "tagslug" instead of
		# "tagsslug"
		#

		if (empty($event->conditions[$condition]))
		{
			# show all by category ?

			$event->view->range['limit'] = null; // cancel limit TODO-20120403: this should be improved.

			$core->events->attach(array(__CLASS__, 'on_alter_provider_result'));

			return;
		}

		$condition_value = $event->conditions[$condition];

		$term = $core->models['taxonomy.terms']->where('vid = ? AND termslug = ?', array($vocabulary->vid, $condition_value))->order('term.weight')->one;

		$core->events->attach(function(ActiveRecordProvider\AlterContextEvent $event, ActiveRecordProvider $target) use($term) {

			$event->context['term'] = $term;

		});

		$event->query->where('nid IN (SELECT nid FROM {prefix}taxonomy_terms
		INNER JOIN {prefix}taxonomy_terms__nodes USING(vtid) WHERE vtid = ?)', $term ? $term->vtid : 0);

		#

		global $core;

		$page = isset($core->request->context->page) ? $core->request->context->page : null;

		if ($page && $term)
		{
			$page->title = \ICanBoogie\format($page->title, array(':term' => $term->term));
		}
	}

	static public function on_alter_provider_result(\Icybee\Modules\Views\ActiveRecordProvider\AlterResultEvent $event, \Icybee\Modules\Views\ActiveRecordProvider $provider)
	{
		global $core;

		$vocabulary = $event->view->options['taxonomy vocabulary'];

		$ids = '';
		$records_by_id = array();

		foreach ($event->result as $record)
		{
			if (!($record instanceof \Icybee\Modules\Nodes\Node))
			{
				/*
				 * we return them as [ term: [], nodes: []]
				 *
				 * check double event ?
				 *
				 * http://demo.icybee.localhost/articles/category/
				 *
				trigger_error(\ICanBoogie\format('Expected instance of <q>Icybee\Modules\Nodes\Node</q> given: \1', array($record)));

				var_dump($event); exit;
				* /

				continue;
			}

			$nid = $record->nid;
			$ids .= ',' . $nid;
			$records_by_id[$nid] = $record;
		}

		if (!$ids)
		{
			return;
		}

		$ids = substr($ids, 1);

		/*
		$ids_by_names = $core->models['taxonomy.terms/nodes']
		->join(':nodes')
		->select('term, nid')
		->order('term.weight, term.term')
		->where('vid = ? AND nid IN(' . $ids . ')', $vocabulary->vid)
		->all(\PDO::FETCH_GROUP | \PDO::FETCH_COLUMN);

		var_dump($ids_by_names);

		$result = array();

		foreach ($ids_by_names as $name => $ids)
		{
			$ids = array_flip($ids);

			foreach ($event->result as $record)
			{
				if (isset($ids[$record->nid]))
				{
					$result[$name][] = $record;
				}
			}
		}

		$event->result = $result;
		* /

		$ids_by_vtid = $core->models['taxonomy.terms/nodes']
		->join(':nodes')
		->select('vtid, nid')
		->order('term.weight, term.term')
		->where('vid = ? AND nid IN(' . $ids . ')', $vocabulary->vid)
		->all(\PDO::FETCH_GROUP | \PDO::FETCH_COLUMN);

		$terms = $core->models['taxonomy.terms']->find(array_keys($ids_by_vtid));

		$result = array();

		foreach ($ids_by_vtid as $vtid => $ids)
		{
			$result[$vtid]['term'] = $terms[$vtid];
			$result[$vtid]['nodes'] = array_intersect_key($records_by_id, array_combine($ids, $ids));
		}

		$event->result = $result;
	}

	static private function for_vocabulary_and_term(Event $event, Provider $provider, $options, \Icybee\Modules\Taxonomy\Vocabulary\Vocabulary $vocabulary, \Icybee\Modules\Taxonomy\Terms\Term $term)
	{
		$event->query->where('nid IN (SELECT nid FROM {prefix}taxonomy_terms
		INNER JOIN {prefix}taxonomy_terms__nodes USING(vtid) WHERE vtid = ?)', $term ? $term->vtid : 0);



		/*
		$core->events->attach
		(
			'Icybee\Modules\Pages\Page::render_title', function()
			{
				var_dump(func_get_args());
			}
		);
		* /
	}
	*/

	/**
	 * Replaces `${term.<name>}` patterns—where `<name>` if the name of a term—found in
	 * breadcrumb labels by the corresponding term name.
	 *
	 * @param BreadcrumbElement\BeforeRenderInnerHTMLEvent $event
	 * @param BreadcrumbElement $target
	 */
	static public function before_breadcrumb_render_inner_html(BreadcrumbElement\BeforeRenderInnerHTMLEvent $event, BreadcrumbElement $target)
	{
		$context = \ICanBoogie\app()->request->context;

		if (empty($context->node))
		{
			return;
		}

		$node = $context->node;
		$replace = function($match) use ($node) {

			list(, $term) = $match;

			return $node->$term;

		};

		foreach ($event->slices as &$slice)
		{
			$slice['label'] = preg_replace_callback('/\$\{term.([^\}]+)\}/', $replace, $slice['label']);
		}
	}

	/**
	 * @return \ICanBoogie\Core|\Icybee\Binding\CoreBindings
	 */
	static private function app()
	{
		return \ICanBoogie\app();
	}
}
