<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
\Bitrix\Main\Loader::includeModule('sale');
if(isset($_POST['ajax'])&&$_POST['ajax']=='Y'){
    if(!empty($_POST['summ'])&&!empty($_POST['currency'])){
        echo CCurrencyLang::CurrencyFormat($_POST['summ'],$_POST['currency']);
    }
}
die();