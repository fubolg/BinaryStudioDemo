<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Mapper;

use BinaryStudioDemo\CoreBundle\Interfaces\ContextInterface;
use BinaryStudioDemo\ImportExportBundle\Interfaces\StepExecutionContextInterface;
use BinaryStudioDemo\IntegrationBundle\Traits\ErrorMessageAwaredTrait;
use BinaryStudioDemo\MappingBundle\Converter\ConverterInterface;
use BinaryStudioDemo\MappingBundle\Event\MappingRequestSuccessEvent;
use BinaryStudioDemo\MappingBundle\Interfaces\MapperInterface;
use BinaryStudioDemo\MappingBundle\MappingTypes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OrderImportMapper
 * @package BinaryStudioDemo\MappingBundle\Mapper
 */
class OrderExportMapper implements MapperInterface, EventSubscriberInterface
{
    use ErrorMessageAwaredTrait;

    private ConverterInterface $converter;
    private array $convertedCache = [];
    private ?int $channelId = null;

    /**
     * OrderExportMapper constructor.
     *
     * @param ConverterInterface $converter
     */
    public function __construct(
        ConverterInterface $converter
    ) {
        $this->converter = $converter;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MappingRequestSuccessEvent::NAME => 'clearCache'
        ];
    }

    /**
     * @param MappingRequestSuccessEvent $event
     */
    final public function clearCache(MappingRequestSuccessEvent $event): void
    {
        if($event->getChannelId() === $this->channelId) {
            $this->convertedCache = [];
        }
    }

//    /**
//     * @param $automappingTypes
//     */
//    final public function allowAutomapping($automappingTypes): void
//    {
//    }

    /**
     * @param array                         $subject
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function supports(array $subject, ContextInterface $context): bool
    {
        $direction = $context->getValue('direction');
        if($direction !== 'export') {
            return false;
        }

        return array_key_exists('number', $subject) &&
            array_key_exists('items', $subject) &&
            array_key_exists('id', $subject) && null !== $subject['id'];
    }

    /**
     * @param array                         $order
     * @param ContextInterface $context
     *
     * @return array|null
     */
    public function run(array $order, ContextInterface $context): ?array
    {
        # initialize
        $this->channelId = $context->getValue(StepExecutionContextInterface::CHANNEL_ID);
        $this->converter->setChannelId($this->channelId);

        $this->resetErrorMessage();

        ##### Convert order

        if(array_key_exists('requestedCarrier', $order)) {
            $order['requestedCarrier'] = !empty($order['requestedCarrier']) && isset($order['requestedCarrier']['id'])
                ? $this->convertCarrier($order['requestedCarrier']['id'])
                : null;
        }

        if(array_key_exists('shipments', $order) && !empty($order['shipments'])) {
            foreach($order['shipments'] as &$shipment) {
                $shipment = $this->processCarrier($shipment);
                $shipment = $this->processLocation($shipment);
            }
        }

        if(array_key_exists('returns', $order) && !empty($order['returns'])) {
            foreach($order['returns'] as &$return) {
                $return = $this->processCarrier($return);
                $return = $this->processLocation($return);
            }
        }

        if(array_key_exists('cancellations', $order) && !empty($order['cancellations'])) {
            foreach($order['cancellations'] as &$cancellation) {
                $cancellation = $this->processCarrier($cancellation);
                $cancellation = $this->processLocation($cancellation);
            }
        }

        $order = $this->processCarrier($order);
        $order = $this->processLocation($order);

        return $order;
    }

    /**
     * @param array $subject
     *
     * @return array
     */
    private function processLocation(array $subject)
    {
        if (array_key_exists('location', $subject)) {
            $locationId = is_array($subject['location'])
                ? ($subject['location']['id'] ?? null)
                : null;

            $subject['location'] = empty($locationId)
                ? null
                : $this->convertLocation($locationId);
        }

        return $subject;
    }

    /**
     * @param int $locationId
     *
     * @return mixed|null
     */
    private function convertLocation(int $locationId) {
        $converted = $this->convertedCache[MappingTypes::TYPE_LOCATION][$locationId] ?? false;
        if (false === $converted) {
            $this->convertedCache[MappingTypes::TYPE_LOCATION][$locationId] = $this->converter->convert([
                'type' => MappingTypes::TYPE_LOCATION,
                'id' => $locationId
            ]);
        }

        return $this->convertedCache[MappingTypes::TYPE_LOCATION][$locationId] ?? null;
    }

    /**
     * @param array $subject
     *
     * @return array
     */
    private function processCarrier(array $subject)
    {
        if (array_key_exists('carrier', $subject)) {

            $carrierId = is_array($subject['carrier'])
                ? ($subject['carrier']['id'] ?? null)
                : null;

            $subject['carrier'] = empty($carrierId)
                ? null
                : $this->convertCarrier($carrierId);
        }

        return $subject;
    }

    /**
     * @param int $carrierId
     *
     * @return mixed|null
     */
    private function convertCarrier(int $carrierId) {
        $converted = $this->convertedCache[MappingTypes::TYPE_CARRIER][$carrierId] ?? false;
        if (false === $converted) {
            $this->convertedCache[MappingTypes::TYPE_CARRIER][$carrierId] = $this->converter->convert([
                'type' => MappingTypes::TYPE_CARRIER,
                'id' => $carrierId
            ]);
        }

        return $this->convertedCache[MappingTypes::TYPE_CARRIER][$carrierId] ?? null;
    }
}
