<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Taxonomy\Vocabulary;

use ICanBoogie\ActiveRecord\Model;

class VocabularyModel extends Model
{
	public function save(array $properties, $key=null, array $options=array())
	{
		if (isset($properties['vocabulary']) && empty($properties['vocabulary_slug']))
		{
			$properties['vocabulary_slug'] = \Icybee\slugize($properties['vocabulary']);
		}

		if (isset($properties['vocabulary_slug']))
		{
			$properties['vocabulary_slug'] = \ICanBoogie\normalize($properties['vocabulary_slug']);
		}

		$key = parent::save($properties, $key, $options);

		if (!$key)
		{
			return $key;
		}

		$scope = array();

		if (isset($properties['scope']))
		{
			$insert = $this->prepare('INSERT IGNORE INTO {self}__scopes (vocabulary_id, constructor) VALUES(?, ?)');

			foreach ($properties['scope'] as $constructor => $ok)
			{
				$ok = filter_var($ok, FILTER_VALIDATE_BOOLEAN);

				if (!$ok)
				{
					continue;
				}

				$scope[] = $constructor;
				$insert->execute(array($key, $constructor));
			}
		}

		if ($scope)
		{
			$scope = array_map(array($this, 'quote'), $scope);

			$this->execute('DELETE FROM {self}__scopes WHERE vocabulary_id = ? AND constructor NOT IN(' . implode(',', $scope) . ')', array($key));
		}

		return $key;
	}

	public function delete($key)
	{
		$rc = parent::delete($key);

		if ($rc)
		{
			$this->execute('DELETE FROM {self}__scopes WHERE vocabulary_id = ?', array($key));
			$this->clearTerms($key);
		}

		return $rc;
	}

	protected function clearTerms($vid)
	{
		// TODO: use model delete() method instead, maybe put an event on 'taxonomy.vocabulary.delete'

		$model = $this->models['taxonomy.terms'];
		$model->execute('DELETE FROM {self}__nodes WHERE (SELECT vocabulary_id FROM {self} WHERE {self}__nodes.term_id = {self}.term_id) = ?', array($vid));
		$model->execute('DELETE FROM {self} WHERE vocabulary_id = ?', array($vid));
	}
}
