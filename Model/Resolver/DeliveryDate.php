<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DeliveryDateGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterface;

class DeliveryDate implements \Magento\Framework\GraphQl\Query\ResolverInterface
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
        if (empty($args['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }
        $maskedCartId = $args['cart_id'];
        $deliveryDateData = $this->queueManager->getSelectedDeliveryDateByGuestCartId($maskedCartId);
        if (!$deliveryDateData) {
            /** @var DeliveryDateDataInterface $deliveryDateData */
            $deliveryDateData = $this->deliveryDateDataFactory->create();
        }

        return [
            'model' => $deliveryDateData
        ];
    }
}
