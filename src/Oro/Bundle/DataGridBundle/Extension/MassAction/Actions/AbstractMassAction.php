<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction;

class AbstractMassAction extends AbstractAction implements MassActionInterface
{
    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = 'mass';
        }

        if (!empty($options['icon'])) {
            $options['launcherOptions'] = [
                'iconClassName' => 'fa-' . $options['icon']
            ];
            unset($options['icon']);
        }

        return parent::setOptions($options);
    }
}
