<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
$this->setFrameMode(true);
?>
<div style="border: 2px dashed #3498db; padding:10px;">
    <p style="padding:0 0 10px;"><?=Loc::getMessage('SW24_LOYALTYPROGRAM_BBONUSES_TOTAL')?>: <span style="font-size: 120%; color:#3498db"><?=$arResult['BUDGET_FORMAT']?></span></p>
    <?if(!empty($arResult['LAST_ADD_BONUS'])){?>
        <p>
            <?=Loc::getMessage('SW24_LOYALTYPROGRAM_BBONUSES_ADDED')?>:
            <span style="font-size: 120%; color:#3498db"><?=$arResult['LAST_ADD_BONUS']['BONUS_FORMAT']?></span>
            (<?=Loc::getMessage('SW24_LOYALTYPROGRAM_BBONUSES_TRANSACTION_DATE')?> - <span class="date_bonus"><?=$arResult['LAST_ADD_BONUS']['DATE_ADD']?></span>)
        </p>
    <?}?>
    <?if(!empty($arResult['LAST_WRITEOFF_BONUS'])){?>
        <p style="padding:0;">
            <?=Loc::getMessage('SW24_LOYALTYPROGRAM_BBONUSES_WRITEOFF')?>:
            <span style="font-size: 120%; color:#3498db"><?=$arResult['LAST_WRITEOFF_BONUS']['BONUS_FORMAT']?></span>
            (<?=Loc::getMessage('SW24_LOYALTYPROGRAM_BBONUSES_TRANSACTION_DATE')?> - <span class="date_bonus"><?=$arResult['LAST_WRITEOFF_BONUS']['DATE_ADD']?></span>)
        </p>
    <?}?>
</div>
