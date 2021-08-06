<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Mapper;

use BinaryStudioDemo\ChannelBundle\Interfaces\ChannelInterface;
use BinaryStudioDemo\CoreBundle\Interfaces\ContextInterface;
use BinaryStudioDemo\ImportExportBundle\Interfaces\StepExecutionContextInterface;
use BinaryStudioDemo\MappingBundle\Interfaces\MapperInterface;
use BinaryStudioDemo\MappingBundle\MappingTypes;

/**
 * Class OrderImportMapper
 * @package BinaryStudioDemo\MappingBundle\Mapper
 */
class OrderImportMapper extends AbstractMapper implements MapperInterface
{
    protected ?ContextInterface $context = null;

    /**
     * @param array            $subject
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function supports(array $subject, ContextInterface $context): bool
    {
        $direction = $context->getValue('direction');
        if ($direction !== 'import') {
            return false;
        }

        return array_key_exists('number', $subject) &&
            array_key_exists('items', $subject) &&
            !array_key_exists('id', $subject);
    }

    /**
     * @param array            $order
     * @param ContextInterface $context
     *
     * @return array|null
     */
    public function run(array $order, ContextInterface $context): ?array
    {
        # initialize
        $this->channelId = $context->getValue(StepExecutionContextInterface::CHANNEL_ID);
        $this->converter->setChannelId($this->channelId);

        ##### Convert order

        if (array_key_exists('requestedCarrier', $order)) {

            $order['requestedCarrier'] = !empty($order['requestedCarrier'])
                ? $this->convertCarrier($order['requestedCarrier'])
                : null;
        }

        if (array_key_exists('shipments', $order) && !empty($order['shipments'])) {
            foreach ($order['shipments'] as &$shipment) {
                $shipment = $this->processCarrier($shipment);
                $shipment = $this->processLocation($shipment);
            }
        }

        return $order;
    }

    /**
     * @param array $subject
     *
     * @return array|null
     */
    protected function processLocation(array $subject): ?array
    {
        if (array_key_exists('location', $subject)) {
            # convert
            $converted = $this->convertedCache[MappingTypes::TYPE_LOCATION][$subject['location']] ?? false;
            if (false === $converted) {

                $data = [];
                if (is_numeric($subject['location'])) {
                    $data['id'] = (string) $subject['location'];
                } else {
                    $data['name'] = $subject['location'];
                }

                $this->convertedCache[MappingTypes::TYPE_LOCATION][$subject['location']] = $this->converter->convert(
                    [
                        'channel' => $this->channelId,
                        'label' => $subject['location'],
                        'type' => MappingTypes::TYPE_LOCATION,
                        'data' => $data
                    ]
                );
            }

            # automap
//                    $this->automap(MappingTypes::TYPE_LOCATION, $subject['location']);

            $result = $this->convertedCache[MappingTypes::TYPE_LOCATION][$subject['location']] ?? null;
            # replace with converted value
            if (null !== $result) {
                $subject['location'] = $result;
            }
        }

        return $subject;
    }

    /**
     * @param array $subject
     *
     * @return array
     */
    public function processCarrier(array $subject)
    {
        if (array_key_exists('carrier', $subject) && is_string($subject['carrier'])) {
            $result = $this->convertCarrier($subject['carrier']);

            if (null !== $result) {
                $subject['carrier'] = $result;

                return $subject;
            }

            $subject['carrier'] = null;
        }

        if (($defaultCarrier = $this->getDefaultInboundCarrier()) && !empty($defaultCarrier)) {
            $result = $this->convertCarrier($defaultCarrier);

            if (null !== $result) {
                $subject['carrier'] = $result;

                return $subject;
            }

            $subject['carrier'] = null;
        }

        return $subject;
    }

    /**
     * @return string|null
     */
    private function getDefaultInboundCarrier(): ?string
    {
        if ($this->context === null) {
            return null;
        }

        /** @var ChannelInterface|null $channel */
        $channel = $this->context->getValue(StepExecutionContextInterface::CHANNEL);
        if ($channel === null) {
            return null;
        }

        return $channel->getSettings('defaultInboundCarrier');
    }

    /**
     * @param string $carrierName
     *
     * @return array|null
     */
    private function convertCarrier(string $carrierName): ?array
    {
        # convert
        if (
            !array_key_exists(MappingTypes::TYPE_CARRIER, $this->convertedCache) ||
            !array_key_exists($carrierName, $this->convertedCache[MappingTypes::TYPE_CARRIER])
        ) {
            $this->convertedCache[MappingTypes::TYPE_CARRIER][$carrierName] = $this->converter->convert(
                [
                    'channel' => $this->channelId,
                    'label' => $carrierName,
                    'type' => MappingTypes::TYPE_CARRIER,
                    'data' => [
                        'name' => $carrierName
                    ]
                ]
            );

//            $this->automap(MappingTypes::TYPE_CARRIER, $carrierName);
        }

        # automap
        return $this->convertedCache[MappingTypes::TYPE_CARRIER][$carrierName] ?? null;
    }
}
