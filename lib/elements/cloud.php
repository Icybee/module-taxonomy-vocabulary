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

use Brickrouge\Element;
use Brickrouge\ElementIsEmpty;

class CloudElement extends Element
{
	const T_LEVELS = '#cloud-levels';

	protected function render_inner_html()
	{
		$options = $this[self::OPTIONS];

		if (!$options)
		{
			throw new ElementIsEmpty;
		}

		$n = count($options);
		$max = max($options);
		$sum = array_sum($options);
		$med = $sum / $n;

		$levels = $this[self::T_LEVELS] ?: 8;
		$markup = $this->type == 'ul' ? 'li' : 'span';
		$rc = '';

		foreach ($options as $name => $usage)
		{
			$per = .5;

			if ($usage < $med)
			{
				$per = ($usage / $med) / 2;
			}
			else if ($usage > $med)
			{
				$per = ($usage / $max) / 2 + .5;
			}

			$level = ceil($levels * $per);

			$rc .= <<<EOT
<$markup class="tag$level">$name</$markup>

EOT;
		}

		return $rc;
	}
}
