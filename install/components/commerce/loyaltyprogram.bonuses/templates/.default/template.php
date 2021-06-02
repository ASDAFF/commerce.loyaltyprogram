<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
if($arResult['BONUS']>0){?>
<div class="loyaltyprogram_basket_bonus">
<?=Loc::getMessage('SW24_LOYALTYPROGRAM_BONUSES')?> - <b><?=$arResult['BONUS_FORMAT']?></b>
</div>
<?}?>