<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
$this->setFrameMode(true);

if(!empty($arResult['COUPONS'])){?>
<p><h6><?=Loc::getMessage('SW24_LOYALTYPROGRAM_AV_COUPONS')?></h6>
<?foreach($arResult['COUPONS'] as $nextCoupon){
    if(!empty($nextCoupon['COUPON'])){?>
    <?=$nextCoupon['DISCOUNT_NAME']?> - <?=$nextCoupon['COUPON']?><br>
<?}
}?>
</p>
<?}?>
