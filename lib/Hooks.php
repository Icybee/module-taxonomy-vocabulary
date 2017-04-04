<?php

namespace Icybee\Modules\Taxonomy\Vocabulary;

use function ICanBoogie\app;
use ICanBoogie\Event;
use ICanBoogie\I18n;

use Brickrouge\Element;
use Brickrouge\Form;
use Brickrouge\Group;
use Brickrouge\Text;

use Icybee\Modules\Pages\BreadcrumbElement;
//use Icybee\Modules\Views\ActiveRecordProvider;
use Icybee\Modules\Taxonomy\Terms\Term;
use Icybee\Modules\Taxonomy\Vocabulary\Element\TagsPicker;
use Icybee\Modules\Views\Collection as ViewsCollection;
//use Icybee\Modules\Views\Provider;
use Icybee\Modules\Nodes\Node;

class Hooks
{
	static private $vocabularies_cache;

	/**
	 * @param Node $target
	 * @param string $property
	 *
	 * @return Term|null
	 */
	static public function get_term(\Icybee\Modules\Nodes\Node $target, $property)
	{
		$constructor = $target->constructor;
		$models = $target->model->models;

		if (isset(self::$vocabularies_cache[$constructor]))
		{
			$vocabularies = self::$vocabularies_cache[$constructor];

			if ($vocabularies === false)
			{
				return null;
			}
		}
		else
		{
			self::$vocabularies_cache[$constructor] = $vocabularies = $models['taxonomy.vocabulary']
			->join(':taxonomy.vocabulary/scopes')
			->where('site_id = 0 OR site_id = ?', $target->site_id)
			->filter_by_constructor((string) $constructor)
			->order('site_id DESC')
			->all;
		}

		if (!$vocabularies)
		{
			return null;
		}

		/* @var $vocabulary Vocabulary */

		$vocabulary = null;

		foreach ($vocabularies as $v)
		{
			if ($property != $v->vocabulary_slug)
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
			return null;
		}

		$prototype = $target->prototype;
		$terms_model = $models['taxonomy.terms'];
		$getters = [];

		foreach ($vocabularies as $vocabulary)
		{
			$slug = $vocabulary->to_slug();

			$prototype["lazy_get_$slug"] = $getters[$slug] = function(Node $node) use($terms_model, $vocabulary) {

				static $term_id_by_nid;

				if (!$term_id_by_nid)
				{
					$term_id_by_nid = $terms_model
					->select('nid, term_id')
					->join(':taxonomy.terms/nodes')
					->filter_by_vocabulary_id($vocabulary->vocabulary_id)
					->all(\PDO::FETCH_GROUP | \PDO::FETCH_COLUMN);

					#
					# Warming up ActiveRecord's cache with terms.
					#

					$term_id_list = [];

					foreach ($term_id_by_nid as $v)
					{
						$term_id_list = array_merge($term_id_list, $v);
					}

					$term_id_list = array_unique($term_id_list);

					$terms_model->find($term_id_list);
				}

				$nid = $node->nid;

				if (empty($term_id_by_nid[$nid]))
				{
					// TODO-20140921: is_required => create a fake "uncategorized" instance

					return null;
				}

				$terms = $terms_model->find($term_id_by_nid[$nid]);

				return ($vocabulary->is_multiple || $vocabulary->is_tags) ? $terms : reset($terms);

			};
		}

		return $getters[$property]($target);
	}

	static public function on_nodes_editblock_alter_children(Event $event, \Icybee\Modules\Nodes\Block\EditBlock $block)
	{
		$app = app();

		$document = $app->document;

		$document->css->add(DIR . 'public/support.css');
		$document->js->add(DIR . 'public/support.js');

		$vocabularies = $app->models['taxonomy.vocabulary']
		->join('INNER JOIN {self}__scopes USING(vocabulary_id)')
		->where('constructor = ? AND (site_id = 0 OR site_id = ?)', (string) $event->module, $app->site_id)
		->order('weight')
		->all;

		// TODO-20101104: use Brickrouge\Form::VALUES instead of setting the 'values' of the elements.
		// -> because 'properties' are ignored, and that's bad.

		$terms_model = $app->models['taxonomy.terms'];
		$nodes_model = $app->models['taxonomy.terms/nodes'];

		$nid = $event->key;
		$identifier_base = 'vocabulary[vocabulary_id]';
		$children = &$event->children;

		foreach ($vocabularies as $vocabulary)
		{
			$vocabulary_id = $vocabulary->vocabulary_id;

			$identifier = $identifier_base . '[' . $vocabulary_id . ']';

			if ($vocabulary->is_tags)
			{
				$options = $terms_model->select('term, count(nid)')
					->join(':taxonomy.terms/nodes', [ 'mode' => 'LEFT' ])
					->filter_by_vocabulary_id($vocabulary_id)
					->group('term')
					->order('term')
					->pairs;

				$value = $nodes_model->select('term')
					->filter_by_vocabulary_id_and_nid($vocabulary_id, $nid)
					->order('term')
					->all(\PDO::FETCH_COLUMN);

				$value = implode(', ', $value);

				$label = $vocabulary->vocabulary;

				$children[] = new TagsPicker([

					Group::LABEL => $label,

					Element::GROUP => 'organize',
					Element::WEIGHT => 100,
					Element::OPTIONS => $options,

					'name' => $identifier,
					'value' => $value

				]);
			}
			else
			{
				$options = $terms_model
					->select('term.term_id, term')
					->filter_by_vocabulary_id($vocabulary_id)
					->order('term')
					->pairs;

				if (!$options)
				{
					//continue;
				}

				$value = $nodes_model
					->select('term_node.term_id')
					->filter_by_vocabulary_id_and_nid($vocabulary_id, $nid)
					->order('term')
					->rc;

				$edit_url = $app->site->path . '/admin/taxonomy.vocabulary/' . $vocabulary->vocabulary_id . '/edit';
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

	static public function on_node_save(\ICanBoogie\Operation\ProcessEvent $event, \Icybee\Modules\Nodes\Operation\SaveOperation $target)
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
		$vocabularies = $vocabularies['vocabulary_id'];

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
					$term_id = $terms_model->select('term_id')->where('vocabulary_id = ? and term = ?', $vid, $term)->rc;

					// FIXME-20090127: only users with 'create tags' permissions should be allowed to create tags

					if (!$term_id)
					{
						$term_id = $terms_model->save
						(
							array
							(
								'vocabulary_id' => $vid,
								'term' => $term
							)
						);
					}

					$values[] = $term_id;
				}
			}

			foreach ((array) $values as $term_id)
			{
				$nodes_model->insert
				(
					array
					(
						'term_id' => $term_id,
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
}
