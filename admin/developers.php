<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$module_id ='commerce.loyaltyprogram';
use \Bitrix\Main\Application,
	Bitrix\Main\Page\Asset,
	Bitrix\Main\Request,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale\Internals;
	Loc::loadMessages(__DIR__ .'/lang.php');



$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$module_id ='commerce.loyaltyprogram';

Asset::getInstance()->addJs('/bitrix/js/'.$module_id.'/script.js');

Asset::getInstance()->addJs('/bitrix/js/main/amcharts/3.3/amcharts.js');
Asset::getInstance()->addJs('/bitrix/js/main/amcharts/3.3/pie.js');
Asset::getInstance()->addJs('/bitrix/js/main/amcharts/3.3/serial.js');
Asset::getInstance()->addJs('/bitrix/js/main/amcharts/3.3/themes/light.js');


\Bitrix\Main\Loader::includeModule($module_id);

global $APPLICATION;
$rights=$APPLICATION->GetGroupRight($module_id);
if($rights<'E'){
	$APPLICATION->AuthFrom(Loc::getMessage("ACCESS_DENIED"));
}


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$APPLICATION->IncludeFile("/bitrix/modules/".$module_id."/include/headerInfo.php", Array());

?>
<h2>Developers</h2>
<section class="developers pages">
	<article class="JShine">
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/documentation.png"></figure>
			<header>Documentation</header>
		</a>
	</article>
	<article class="AzurLane">
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/events.png"></figure>
			<header>Events</header>
		</a>
	</article>
	<article class="KyeMeh">
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/api.png"></figure>
			<header>API</header>
		</a>
	</article>
	<article class="Magic">
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/data.png"></figure>
			<header>Import</header>
		</a>
	</article>
</section>

<h2>Articles</h2>
<section class="developers articles">
	<article>
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/writing.png"></figure>
			<header>Moving from the vbcherepanov.bonus module</header>
		</a>
	</article>
	<article>
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/writing.png"></figure>
			<header>Download bonuses from 1C</header>
		</a>
	</article>
	<article>
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/writing.png"></figure>
			<header>Download bonuses from 1C</header>
		</a>
	</article>
	<article>
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/writing.png"></figure>
			<header>Download bonuses from 1C</header>
		</a>
	</article>
	<article>
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/writing.png"></figure>
			<header>Download bonuses from 1C</header>
		</a>
	</article>
	<article>
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/writing.png"></figure>
			<header>Download bonuses from 1C</header>
		</a>
	</article>
</section>

<h2>Video</h2>
<section class="developers video">
	<article>
		<a href="/bitrix/admin/commerce_loyaltyprogram_developers.php">
			<figure><img src="/bitrix/images/commerce.loyaltyprogram/developers/video.png"></figure>
			<header>Profile setup Registration</header>
		</a>
	</article>
</section>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>