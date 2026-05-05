<?php

namespace DigitalMarketingFramework\Distributor\Redirect\Route;

use DigitalMarketingFramework\Core\DataProcessor\ValueSource\ConstantValueSource;
use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\Frontend\FrontendUriBuilderAwareInterface;
use DigitalMarketingFramework\Core\Frontend\FrontendUriBuilderAwareTrait;
use DigitalMarketingFramework\Core\Integration\IntegrationInfo;
use DigitalMarketingFramework\Core\Model\Data\Data;
use DigitalMarketingFramework\Core\Model\Data\DataInterface;
use DigitalMarketingFramework\Core\Model\Data\Value\ValueInterface;
use DigitalMarketingFramework\Core\SchemaDocument\RenderingDefinition\RenderingDefinitionInterface;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\ContainerSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\Custom\ValueSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\CustomSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\MapSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\SchemaInterface;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\StringSchema;
use DigitalMarketingFramework\Distributor\Core\DataDispatcher\DataDispatcherInterface;
use DigitalMarketingFramework\Distributor\Core\Model\Configuration\DistributorConfigurationInterface;
use DigitalMarketingFramework\Distributor\Core\Route\OutboundRoute;
use DigitalMarketingFramework\Distributor\Redirect\DataDispatcher\RedirectDataDispatcher;

class RedirectOutboundRoute extends OutboundRoute implements FrontendUriBuilderAwareInterface
{
    use FrontendUriBuilderAwareTrait;

    public const KEY_URL = 'url';

    public const KEY_PARAMETERS = 'parameters';

    public const KEY_FRAGMENT = 'fragment';

    public const DEFAULT_URL = '';

    public function async(): ?bool
    {
        return false;
    }

    public function enableStorage(): ?bool
    {
        return false;
    }

    public static function getDefaultIntegrationInfo(): IntegrationInfo
    {
        return new IntegrationInfo('system');
    }

    public static function getLabel(): ?string
    {
        return 'Redirect';
    }

    public static function getSchema(): SchemaInterface
    {
        /** @var ContainerSchema $schema */
        $schema = parent::getSchema();

        $schema->removeProperty(DistributorConfigurationInterface::KEY_ASYNC);
        $schema->removeProperty(DistributorConfigurationInterface::KEY_ENABLE_STORAGE);
        $schema->removeProperty(static::KEY_DATA);

        $urlSchema = new CustomSchema(ValueSchema::TYPE, ValueSchema::createStandardValueConfiguration('constant', [ConstantValueSource::KEY_VALUE => static::DEFAULT_URL]));
        $urlSchema->getRenderingDefinition()->setLabel('URL');
        $urlSchema->getRenderingDefinition()->setHint('Target URL. Supports CMS-specific schemes like t3:// (TYPO3) or entity:, internal:, route: (Drupal). Submission data tokens like {fieldName} can be substituted via the InsertData modifier.');
        $schema->addProperty(static::KEY_URL, $urlSchema);

        $parameterValueSchema = new CustomSchema(ValueSchema::TYPE, ValueSchema::createStandardValueConfiguration('constant', [ConstantValueSource::KEY_VALUE => '']));
        $parameterValueSchema->getRenderingDefinition()->setLabel('Value');
        $parameterNameSchema = new StringSchema('parameterName');
        $parameterNameSchema->getRenderingDefinition()->setLabel('Name');
        $parametersSchema = new MapSchema($parameterValueSchema, $parameterNameSchema);
        $parametersSchema->getRenderingDefinition()->setLabel('Parameters');
        $parametersSchema->getRenderingDefinition()->setNavigationItem(false);
        $parametersSchema->getRenderingDefinition()->setGroup(RenderingDefinitionInterface::GROUP_SECONDARY);
        $schema->addProperty(static::KEY_PARAMETERS, $parametersSchema);

        $fragmentSchema = new CustomSchema(ValueSchema::TYPE, ValueSchema::createStandardValueConfiguration('constant', [ConstantValueSource::KEY_VALUE => '']));
        $fragmentSchema->getRenderingDefinition()->setLabel('Fragment');
        $fragmentSchema->getRenderingDefinition()->setHint('URL fragment (the part after #).');
        $fragmentSchema->getRenderingDefinition()->setGroup(RenderingDefinitionInterface::GROUP_SECONDARY);
        $schema->addProperty(static::KEY_FRAGMENT, $fragmentSchema);

        return $schema;
    }

    public function buildData(): DataInterface
    {
        $context = $this->getDataProcessorContext();

        $url = (string)($this->dataProcessor->processValue($this->getConfig(static::KEY_URL), $context) ?? '');
        if ($url === '') {
            throw new DigitalMarketingFrameworkException(sprintf('Redirect URL is empty for route "%s" with ID %s.', $this->getKeyword(), $this->routeId));
        }

        $url = $this->frontendUriBuilder->build($url);

        $parameters = [];
        foreach ($this->getMapConfig(static::KEY_PARAMETERS) as $name => $valueConfig) {
            $value = $this->dataProcessor->processValue($valueConfig, $context);
            if ($value instanceof ValueInterface) {
                $value = $value->getValue();
            }

            if ($value === null || $value === '') {
                continue;
            }

            $parameters[$name] = $value;
        }

        $fragment = (string)($this->dataProcessor->processValue($this->getConfig(static::KEY_FRAGMENT), $context) ?? '');

        $finalUrl = $this->assembleUrl($url, $parameters, $fragment);

        return new Data([RedirectDataDispatcher::KEY_URL => $finalUrl]);
    }

    /**
     * @param array<string,mixed> $parameters
     */
    protected function assembleUrl(string $url, array $parameters, string $fragment): string
    {
        $hashPos = strpos($url, '#');
        $existingFragment = '';
        if ($hashPos !== false) {
            $existingFragment = substr($url, $hashPos + 1);
            $url = substr($url, 0, $hashPos);
        }

        if ($parameters !== []) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($parameters);
        }

        $finalFragment = $fragment !== '' ? $fragment : $existingFragment;
        if ($finalFragment !== '') {
            $url .= '#' . $finalFragment;
        }

        return $url;
    }

    protected function getDispatcher(): DataDispatcherInterface
    {
        /** @var RedirectDataDispatcher $dispatcher */
        $dispatcher = $this->registry->getDataDispatcher('redirect');
        $dispatcher->setContext($this->context);

        return $dispatcher;
    }
}
