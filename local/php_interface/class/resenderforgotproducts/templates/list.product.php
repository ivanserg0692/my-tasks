<?php
/** @var \Bitrix\Sale\BasketItem[] $arItems */
?>
<ul class="list-products">
    <? foreach ($arItems as $item) : ?>
        <li class="list-products__item item">
            <a class="item__link" href="<?=  '//' . SITE_SERVER_NAME . $item->getField('DETAIL_PAGE_URL') ?>"><?= $item->getField('NAME') ?></a>
        </li>
    <? endforeach; ?>
</ul>