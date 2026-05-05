<?php

namespace DigitalMarketingFramework\Distributor\Redirect;

use DigitalMarketingFramework\Core\Initialization;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use DigitalMarketingFramework\Distributor\Core\DataDispatcher\DataDispatcherInterface;
use DigitalMarketingFramework\Distributor\Core\Route\OutboundRouteInterface;
use DigitalMarketingFramework\Distributor\Redirect\DataDispatcher\RedirectDataDispatcher;
use DigitalMarketingFramework\Distributor\Redirect\Route\RedirectOutboundRoute;

class DistributorRedirectInitialization extends Initialization
{
    protected const PLUGINS = [
        RegistryDomain::DISTRIBUTOR => [
            OutboundRouteInterface::class => [
                RedirectOutboundRoute::class,
            ],
            DataDispatcherInterface::class => [
                RedirectDataDispatcher::class,
            ],
        ],
    ];

    protected const SCHEMA_MIGRATIONS = [];

    public function __construct(string $packageAlias = '')
    {
        parent::__construct(
            'distributor-redirect',
            '1.0.0',
            $packageAlias,
        );
    }
}
