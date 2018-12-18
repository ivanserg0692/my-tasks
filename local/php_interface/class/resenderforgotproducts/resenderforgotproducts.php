<?php
\Bitrix\Main\Loader::includeModule('sale');

class ReSenderForgotProducts
{
    const TypeEvent = 'PRODUCT_FORGOT';

    /**
     * @param int $userID
     * @return int[] IDS заказов за последний месяц
     * @throws \Bitrix\Main\ArgumentException
     */
    protected static function getActualOrders($userID)
    {
        //дата месяц назад
        $dateFilter = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());
        $dateFilter->add('-1M');

        $arOrders = \Bitrix\Sale\Order::getList([
            'filter' => [
                '>=DATE_INSERT' => $dateFilter,
                'USER_ID' => $userID,
                'LID' => \Bitrix\Main\Context::getCurrent()->getSite()
            ],
            'select' => ['ID']
        ])->fetchAll(); //получаем список заказов за последний месяц
        return $arOrders;
    }

    /**
     * @param int $userID
     * @return int[] IDS товаров которые были в заказах в течении последнего месяца
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     */
    protected static function getProductsFromActualOrders($userID)
    {
        $arOrders = static::getActualOrders(intval($userID));
        $arItemsIgnore = []; //товары которые были в заказах в течении последнего месяца
        if (count($arOrders)) {
            //устанавливаем товары которые не рассылать
            foreach ($arOrders as $arOrder) {
                $order = \Bitrix\Sale\Order::load(intval($arOrder['ID']));
                $basket = $order->getBasket();
                foreach ($basket->getBasketItems() as $basketItem) {
                    $itemID = intval($basketItem->getProductId());
                    $arItemsIgnore[$itemID] = $itemID;
                }
            }
        }
        return $arItemsIgnore;
    }

    /**
     * @param array $arItems
     * @return false|string
     */
    protected static function getTemplateProducts($arItems)
    {
        ob_start();
        include 'templates/list.product.php';
        return ob_get_clean();
    }

    static function execute()
    {
        $arUsers = \Bitrix\Main\UserTable::getList([
            'select' => ['ID', 'EMAIL', 'NAME', 'LAST_NAME'],
            'filter' => ['ACTIVE' => 'Y']
        ])->fetchAll();
        foreach ($arUsers as $arUser) {
            $itemsIgnore = static::getProductsFromActualOrders($arUser['ID']);
            $arItemsResent = [];

            $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId($arUser['ID']),
                \Bitrix\Main\Context::getCurrent()->getSite());
            $items = $basket->getBasketItems();

            foreach ($items as $index => $itemBasket) {
                if (array_search(intval($itemBasket->getProductId()), $itemsIgnore) === false) {
                    $arItemsResent[] = $itemBasket;
                }
            }
            if (count($arItemsResent)) {
                CEvent::Send(static::TypeEvent, \Bitrix\Main\Context::getCurrent()->getSite(), [
                    'EMAIL_TO' => $arUser['EMAIL'],
                    'FIRST_NAME' => $arUser['NAME'],
                    'LAST_NAME' => $arUser['LAST_NAME'],
                    'ITEMS_LIST' => static::getTemplateProducts($arItemsResent),
                ]);
            }

        }
    }
}