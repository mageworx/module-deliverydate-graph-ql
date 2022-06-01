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
use Magento\Quote\Model\Quote;
use MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterface;

class DeliveryDateInCart implements ResolverInterface
{
    /**
     * @var \MageWorx\DeliveryDate\Api\QueueManagerInterface
     */
    protected $queueManager;

    /**
     * @var \MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterfaceFactory
     */
    protected $deliveryDateDataFactory;

    /**
     * @param \MageWorx\DeliveryDate\Api\QueueManagerInterface $queueManager
     * @param \MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterfaceFactory $deliveryDateDataFactory
     */
    public function __construct(
        \MageWorx\DeliveryDate\Api\QueueManagerInterface $queueManager,
        \MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterfaceFactory $deliveryDateDataFactory
    ) {
        $this->queueManager = $queueManager;
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
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var Quote $cart */
        $cart = $value['model'];
        $cartId = $cart->getId();

        if (is_numeric($cartId)) {
            $deliveryDateData = $this->queueManager->getSelectedDeliveryDateByCartId((int)$cartId);
        } else {
            $deliveryDateData = $this->queueManager->getSelectedDeliveryDateByGuestCartId($cartId);
        }

        if (!$deliveryDateData) {
            /** @var DeliveryDateDataInterface $deliveryDateData */
            $deliveryDateData = $this->deliveryDateDataFactory->create();
        }

        return [
            'model' => $deliveryDateData
        ];
    }
}
