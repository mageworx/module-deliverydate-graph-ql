<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DeliveryDateGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterfaceFactory;
use MageWorx\DeliveryDate\Api\Data\DeliveryDateDataInterface;
use MageWorx\DeliveryDate\Api\QueueManagerInterface;
use MageWorx\DeliveryDate\Exceptions\QueueException;
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
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->validateInput($args['input']);

        $maskedCartId     = $args['input']['cart_id'];
        $deliveryDateData = $args['input']['delivery_date'];

        $deliveryDateDataObject = $this->createDeliveryDateDataObjectFromArray($deliveryDateData);
        try {
            $this->queueManager->setDeliveryDateForGuestCart($maskedCartId, $deliveryDateDataObject, null);
        } catch (QueueException|LocalizedException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

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

        $deliveryDateDataObject->setDeliveryDay($deliveryDateData['day']);

        if (!empty($deliveryDateData['time'])) {
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

        if (!empty($deliveryDateData['comment'])) {
            $deliveryDateDataObject->setDeliveryComment($deliveryDateData['comment']);
        }

        return $deliveryDateDataObject;
    }

    /**
     * @param array $inputData
     * @return void
     * @throws GraphQlInputException
     */
    protected function validateInput(array $inputData): void
    {
        if (empty($inputData['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        if (empty($inputData['delivery_date'])) {
            throw new GraphQlInputException(__('Required parameter "delivery_date" is missing'));
        }

        if (!empty($inputData['delivery_date']['time'])) {
            if (!preg_match('/^\d{1,2}:\d{1,2}_\d{1,2}:\d{1,2}$/', $inputData['delivery_date']['time'])) {
                throw new GraphQlInputException(
                    __('The delivery time must be specified in 00:00_23:59 format, like 10:30_19:00.')
                );
            }
        }

        try {
            new \DateTime($inputData['delivery_date']['day']);
        } catch (\Exception $e) {
            throw new GraphQlInputException(__('The delivery day must be specified in Y-m-d format, like 2025-06-15.'));
        }
    }
}
