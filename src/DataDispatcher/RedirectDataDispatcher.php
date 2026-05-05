<?php

namespace DigitalMarketingFramework\Distributor\Redirect\DataDispatcher;

use DigitalMarketingFramework\Core\Context\ContextAwareInterface;
use DigitalMarketingFramework\Core\Context\ContextAwareTrait;
use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\Model\Data\Value\ValueInterface;
use DigitalMarketingFramework\Distributor\Core\DataDispatcher\DataDispatcher;

class RedirectDataDispatcher extends DataDispatcher implements ContextAwareInterface
{
    use ContextAwareTrait;

    public const KEY_URL = 'url';

    /**
     * @param array<string,string|ValueInterface> $data
     */
    public function send(array $data): void
    {
        if (!isset($this->context)) {
            throw new DigitalMarketingFrameworkException('RedirectDataDispatcher requires a context to set the response redirect.');
        }

        if (!$this->context->isResponsive()) {
            throw new DigitalMarketingFrameworkException('RedirectDataDispatcher requires a responsive context to set the response redirect.');
        }

        $url = (string)($data[static::KEY_URL] ?? '');
        if ($url === '') {
            throw new DigitalMarketingFrameworkException('RedirectDataDispatcher received an empty URL.');
        }

        $this->context->setResponseRedirect($url);
    }
}
