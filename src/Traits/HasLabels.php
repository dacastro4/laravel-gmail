<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Traits;

use Google_Service_Gmail;

trait HasLabels
{
    /**
     * List the labels in the user's mailbox.
     *
     *
     * @return \Google\Service\Gmail\ListLabelsResponse
     */
    public function labelsList(string $userEmail): \Google\Service\Gmail\ListLabelsResponse
    {
        $service = new Google_Service_Gmail($this);

        return $service->users_labels->listUsersLabels($userEmail);
    }

    /**
     * Create new label by name.
     *
     *
     * @return \Google\Service\Gmail\Label
     */
    public function createLabel(string $userEmail, \Google_Service_Gmail_Label $label): \Google\Service\Gmail\Label
    {
        $service = new Google_Service_Gmail($this);

        return $service->users_labels->create($userEmail, $label);
    }

    /**
     * first or create label in the user's mailbox.
     *
     * @param  $nLabel
     * @return \Google\Service\Gmail\Label
     */
    public function firstOrCreateLabel(string $userEmail, \Google_Service_Gmail_Label $newLabel): \Google\Service\Gmail\Label
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
}
