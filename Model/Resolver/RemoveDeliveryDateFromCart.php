<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DeliveryDateGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use MageWorx\DeliveryDate\Api\QueueManagerInterface;
use MageWorx\DeliveryDate\Api\Repository\QueueRepositoryInterface;

class RemoveDeliveryDateFromCart implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var QueueManagerInterface
     */
    protected $queueManager;

    /**
     * @var QueueRepositoryInterface
     */
    protected $queueRepository;

    /**
     * @var GetCartForUser
     */
    protected $getCartForUser;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @param QueueManagerInterface $queueManager
     * @param QueueRepositoryInterface $queueRepository
     * @param GetCartForUser $getCartForUser
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        QueueManagerInterface    $queueManager,
        QueueRepositoryInterface $queueRepository,
        GetCartForUser           $getCartForUser,
        CartRepositoryInterface  $cartRepository
    ) {
        $this->queueManager    = $queueManager;
        $this->queueRepository = $queueRepository;
        $this->getCartForUser  = $getCartForUser;
        $this->cartRepository  = $cartRepository;
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

        try {
            $queue = $this->queueManager->getSelectedDeliveryDateByGuestCartId($maskedCartId);
            if ($queue) {
                $this->queueRepository->delete($queue);
            }
        } catch (NoSuchEntityException $noSuchEntityException) {
            // Delivery date has been not set, nothing to do
        }

        $storeId         = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart            = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
        $shippingAddress = $cart->getShippingAddress();
        if ($shippingAddress instanceof \Magento\Quote\Api\Data\AddressInterface) {
            $this->queueManager->cleanDeliveryDateDataByQuoteAddress($shippingAddress);
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
            $this->cartRepository->save($cart);
        }

        return [
            'model' => $cart
        ];
    }
}
