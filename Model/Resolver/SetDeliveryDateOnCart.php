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
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterfaceFactory;
use MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterface;
use MageWorx\DeliveryDate\Api\QueueManagerInterface;
use MageWorx\DeliveryDate\Helper\Data as Helper;

class SetDeliveryDateOnCart implements ResolverInterface
{
    /**
     * @var QueueManagerInterface
     */
    protected $queueManager;

    /**
     * @var DeliveryDateDataInterfaceFactory
     */
    protected $deliveryDateDataFactory;

    /**
     * @var GetCartForUser
     */
    protected $getCartForUser;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @param QueueManagerInterface $queueManager
     * @param DeliveryDateDataInterfaceFactory $deliveryDateDataFactory
     * @param GetCartForUser $getCartForUser
     * @param Helper $helper
     */
    public function __construct(
        QueueManagerInterface            $queueManager,
        DeliveryDateDataInterfaceFactory $deliveryDateDataFactory,
        GetCartForUser                   $getCartForUser,
        Helper                           $helper
    ) {
        $this->queueManager            = $queueManager;
        $this->deliveryDateDataFactory = $deliveryDateDataFactory;
        $this->getCartForUser          = $getCartForUser;
        $this->helper                  = $helper;
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
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }
        $maskedCartId = $args['input']['cart_id'];

        if (empty($args['input']['delivery_date'])) {
            throw new GraphQlInputException(__('Required parameter "delivery_date" is missing'));
        }
        $deliveryDateData = $args['input']['delivery_date'];

        $deliveryDateDataObject = $this->createDeliveryDateDataObjectFromArray($deliveryDateData);
        $this->queueManager->setDeliveryDateForGuestCart($maskedCartId, $deliveryDateDataObject);

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart    = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);

        return [
            'model' => $cart
        ];
    }

    /**
     * @param array $deliveryDateData
     * @return DeliveryDateDataInterface
     */
    protected function createDeliveryDateDataObjectFromArray(array $deliveryDateData): DeliveryDateDataInterface
    {
        /** @var DeliveryDateDataInterface $deliveryDateDataObject */
        $deliveryDateDataObject = $this->deliveryDateDataFactory->create();

        if ($deliveryDateData['day']) {
            $deliveryDateDataObject->setDeliveryDay($deliveryDateData['day']);
        }

        if ($deliveryDateData['time']) {
            $deliveryDateDataObject->setDeliveryTime($deliveryDateData['time']);
            $parts       = $this->helper->parseFromToPartsFromTimeString($deliveryDateData['time']);
            $hoursFrom   = $parts['from']['hours'] ?? '';
            $minutesFrom = $parts['from']['minutes'] ?? '';
            $hoursTo     = $parts['to']['hours'] ?? '';
            $minutesTo   = $parts['to']['minutes'] ?? '';
            $deliveryDateDataObject->setDeliveryHoursFrom($hoursFrom)
                                   ->setDeliveryMinutesFrom($minutesFrom)
                                   ->setDeliveryHoursTo($hoursTo)
                                   ->setDeliveryMinutesTo($minutesTo);
        }

        if ($deliveryDateData['comment']) {
            $deliveryDateDataObject->setDeliveryComment($deliveryDateData['comment']);
        }

        return $deliveryDateDataObject;
    }
}
