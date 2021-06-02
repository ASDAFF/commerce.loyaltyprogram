<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc;


class Documentation{
	
	private $effectClasses;
	private $currenEffect;
	function __construct (){
		$this->rootUrl='https://skyweb24.ru/marketplace/';
		$this->effectClasses=['Alive', 'MoonPurple', 'AzurLane', 'UltraVoilet', 'Ohhappiness'];
		$this->currenEffect=0;
	}

	public function setRootUrl($url){
		if(!empty($url)){
			$this->rootUrl=$url;
		}
	}

	public function setEffect(array $effects){
		$this->effectClasses=$effects;
		$this->currenEffect=0;
	}

	public function getImgUrl($imgUrl){
		return $this->rootUrl.$imgUrl;
	}

	public function getEffect(){
		$cClass=$this->effectClasses[$this->currenEffect];
		$this->currenEffect++;
		if(count($this->effectClasses)<=$this->currenEffect){
			$this->currenEffect=0;
		}
		return $cClass;
	}

	public static function getLangString($str){
		return LANG_CHARSET=='windows-1251'?iconv("UTF-8", LANG_CHARSET, $str):$str;
	}
    
}