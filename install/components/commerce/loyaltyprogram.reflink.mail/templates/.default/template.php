<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
$this->setFrameMode(true);

if(!empty($arResult['REF_LINK'])){?>
<?=Loc::getMessage('SW24_LOYALTYPROGRAM_REF_LINK')?>: <?=$arResult['REF_LINK']?>
<?}?>
