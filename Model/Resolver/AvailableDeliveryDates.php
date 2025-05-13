<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DeliveryDateGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageWorx\DeliveryDate\Api\DeliveryManagerInterface;

class AvailableDeliveryDates implements ResolverInterface
{
    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var \MageWorx\DeliveryDate\Api\QueueManagerInterface
     */
    protected $queueManager;

    /**
     * @var DeliveryManagerInterface
     */
    protected $deliveryManager;

    /**
     * @var \MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterfaceFactory
     */
    protected $deliveryDateDataFactory;

    /**
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \MageWorx\DeliveryDate\Api\QueueManagerInterface $queueManager
     * @param DeliveryManagerInterface $deliveryManager
     * @param \MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterfaceFactory $deliveryDateDataFactory
     */
    public function __construct(
        \Magento\Framework\Reflection\DataObjectProcessor                $dataObjectProcessor,
        \MageWorx\DeliveryDate\Api\QueueManagerInterface                 $queueManager,
        \MageWorx\DeliveryDate\Api\DeliveryManagerInterface              $deliveryManager,
        \MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterfaceFactory $deliveryDateDataFactory
    ) {
        $this->dataObjectProcessor     = $dataObjectProcessor;
        $this->queueManager            = $queueManager;
        $this->deliveryManager         = $deliveryManager;
        $this->deliveryDateDataFactory = $deliveryDateDataFactory;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed|Value
     * @throws \Exception
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if (!isset($args['cart_id'])) {
            throw new LocalizedException(__('"cart_id" value should be specified'));
        }
        $cartId         = $args['cart_id'];
        $shippingMethod = $args['method'] ?? '';
        $startDayIndex  = $args['start_day_index'] ?? 0;
        $endDayIndex    = $args['end_day_index'] ?? 0;

        $this->deliveryManager->setMaxCalculationDays($endDayIndex);
        $this->deliveryManager->setShippingMethodFilter($shippingMethod);

        if (is_numeric($cartId)) {
            $limits = $this->deliveryManager->getAvailableLimitsForQuoteById($cartId);
        } else {
            $limits = $this->deliveryManager->getAvailableDeliveryDatesForGuestCart($cartId);
        }

        $result = [];
        foreach ($limits as $limit) {
            $dayLimits = [];
            foreach ($limit->getDayLimits() as $dayLimitObj) {
                if ($dayLimitObj->getDayIndex() < $startDayIndex) {
                    continue;
                }
                $dayLimits[] = $this->dataObjectProcessor->buildOutputDataArray(
                    $dayLimitObj,
                    'MageWorx\DeliveryDate\Api\Data\DayLimitInterface'
                );
            }
            $limitData = [
                'method' => $limit->getMethod(),
                'day_limits' => $dayLimits
            ];

            $result[] = $limitData;
        }

        return $result;
    }
}
