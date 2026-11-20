<?php

/**
 * @package     OpenConsent
 * @copyright   (C) 2026 vidual
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Vidual\Plugin\System\OpenConsent\Extension\OpenConsent;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $config = (array) PluginHelper::getPlugin('system', 'openconsent');

                // Joomla 5.4+ takes only the config; 5.0-5.3 need the dispatcher first
                // (passing it to 5.4+ still works but is deprecated).
                if (version_compare(JVERSION, '5.4', '>=')) {
                    $plugin = new OpenConsent($config);
                } else {
                    $plugin = new OpenConsent($container->get(DispatcherInterface::class), $config);
                }

                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
