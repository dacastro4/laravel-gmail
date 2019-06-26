<?php

namespace Dacastro4\LaravelGmail\Traits;

use Illuminate\Support\Collection;

trait HasParts
{
	/**
	 * Find all Parts of a message.
	 * Necessary to reset the $allParts Varibale.
	 *
	 * @param  collection  $partsContainer  . F.e. collect([$message->payload])
	 *
	 * @return Collection of all 'parts' flattened
	 */
	private function getAllParts($partsContainer)
	{
		return $this->iterateParts($partsContainer);
	}


	/**
	 * Recursive Method. Iterates through a collection,
	 * finding all 'parts'.
	 *
	 * @param  collection  $partsContainer
	 * @param  bool  $returnOnFirstFound
	 *
	 * @return Collection|boolean
	 */

	private function iterateParts($partsContainer, $returnOnFirstFound = false)
	{
		$allParts = [];
		$parts = $partsContainer->pluck('parts');

		if ($parts) {
			foreach ($parts as $part) {
				if ($part) {
					if ($returnOnFirstFound) {
						return true;
					}

					$allParts[] = $part;
					$part = collect($part);
					$this->iterateParts($part);
				}
			}

		}

		return (collect($allParts))->flatten();
	}
}
