<?php

namespace Thelia\Model;

use Propel\Runtime\ActiveQuery\Criteria;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Cart\CartItemDuplicationItem;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Base\Cart as BaseCart;

class Cart extends BaseCart
{
    /**
     * Duplicate the current existing cart. Only the token is changed
     *
     * @param $token
     * @param  Customer $customer
     * @return Cart
     */
    public function duplicate($token, Customer $customer = null, Currency $currency = null, EventDispatcherInterface $dispatcher)
    {
        $cartItems = $this->getCartItems();

        $cart = new Cart();
        $cart->setAddressDeliveryId($this->getAddressDeliveryId());
        $cart->setAddressInvoiceId($this->getAddressInvoiceId());
        $cart->setToken($token);
        $discount = 0;

        if (null === $currency) {
            $currencyQuery = CurrencyQuery::create();
            $currency = $currencyQuery->findPk($this->getCurrencyId()) ?: $currencyQuery->findOneByByDefault(1);
        }

        $cart->setCurrency($currency);

        if ($customer) {
            $cart->setCustomer($customer);

            if ($customer->getDiscount() > 0) {
                $discount = $customer->getDiscount();
            }
        }

        $cart->save();
        foreach ($cartItems as $cartItem) {

            $product = $cartItem->getProduct();
            $productSaleElements = $cartItem->getProductSaleElements();
            if ($product &&
                $productSaleElements &&
                $product->getVisible() == 1 &&
                ($productSaleElements->getQuantity() >= $cartItem->getQuantity() || ! ConfigQuery::checkAvailableStock()))
            {

                $item = new CartItem();
                $item->setCart($cart);
                $item->setProductId($cartItem->getProductId());
                $item->setQuantity($cartItem->getQuantity());
                $item->setProductSaleElements($productSaleElements);
                $prices = $productSaleElements->getPricesByCurrency($currency, $discount);
                $item
                    ->setPrice($prices->getPrice())
                    ->setPromoPrice($prices->getPromoPrice())
                    ->setPromo($productSaleElements->getPromo());

                $item->save();
                $dispatcher->dispatch(TheliaEvents::CART_ITEM_DUPLICATE, new CartItemDuplicationItem($item, $cartItem));
            }

        }
        $this->delete();

        return $cart;
    }

    /**
     * Retrieve the last item added in the cart
     *
     * @return CartItem
     */
    public function getLastCartItemAdded()
    {
        return CartItemQuery::create()
            ->filterByCartId($this->getId())
            ->orderByCreatedAt(Criteria::DESC)
            ->findOne()
        ;
    }

    /**
     *
     * Retrieve the total taxed amount.
     *
     * By default, the total include the discount
     *
     * /!\ The postage amount is not available so it's the total with or without discount an without postage
     *
     * @param  Country   $country
     * @param  bool      $discount
     * @return float|int
     */
    public function getTaxedAmount(Country $country, $discount = true)
    {
        $total = 0;

        foreach ($this->getCartItems() as $cartItem) {
            $total += $cartItem->getRealTaxedPrice($country) * $cartItem->getQuantity();
        }

        if ($discount) {
            $total -= $this->getDiscount();
        }

        return $total;
    }

    /**
     *
     * @see getTaxedAmount same as this method but the amount is without taxes
     * @param  bool      $discount
     * @return float|int
     */
    public function getTotalAmount($discount = true)
    {
        $total = 0;

        foreach ($this->getCartItems() as $cartItem) {
            $subtotal = $cartItem->getRealPrice();
            $subtotal *= $cartItem->getQuantity();

            $total += $subtotal;
        }

        if ($discount) {
            $total -= $this->getDiscount();
        }

        return $total;
    }

    /**
     * Retrieve the total weight for all products in cart
     *
     * @return float|int
     */
    public function getWeight()
    {
        $weight = 0;

        foreach ($this->getCartItems() as $cartItem) {
            $itemWeight = $cartItem->getProductSaleElements()->getWeight();
            $itemWeight *= $cartItem->getQuantity();

            $weight += $itemWeight;
        }

        return $weight;
    }
}
