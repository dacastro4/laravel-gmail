<?php

namespace Dacastro4\LaravelGmail\Traits;

use Google_Service_Gmail;

trait HasLabels
{
	/**
	 * List the labels in the user's mailbox.
	 *
	 * @param $userEmail
	 *
	 * @return \Google\Service\Gmail\ListLabelsResponse
	 */
	public function labelsList($userEmail)
	{
		$service = new Google_Service_Gmail($this);

		return $service->users_labels->listUsersLabels($userEmail);
	}

	/**
	 * Create new label by name.
	 *
	 * @param $userEmail
	 * @param $label
	 *
	 * @return \Google\Service\Gmail\Label
	 */
	public function createLabel($userEmail, $label)
	{
		$service = new Google_Service_Gmail($this);

		return $service->users_labels->create($userEmail, $label);
	}

	/**
	 * first or create label in the user's mailbox.
	 *
	 * @param $userEmail
	 * @param $nLabel
	 * @return \Google\Service\Gmail\Label
	 */
	public function firstOrCreateLabel($userEmail, $newLabel)
	{
		$labels = $this->labelsList($userEmail);

		foreach ($labels->getLabels() as $existLabel) {
			if ($existLabel->getName() == $newLabel->getName()) {
				return $existLabel;
			}
		}

		$service = new Google_Service_Gmail($this);

		return $service->users_labels->create($userEmail, $newLabel);
	}

	/**
	 * List the labels in the user's mailbox.
	 *
	 * @param $userEmail
	 *
	 * @return \Google\Service\Gmail\Label
	 */
	public function getLabel($userEmail, $id)
	{
		$service = new Google_Service_Gmail($this);

		return $service->users_labels->get($userEmail, $id);
	}
}