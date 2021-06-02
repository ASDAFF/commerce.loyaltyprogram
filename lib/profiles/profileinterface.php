<?
namespace Commerce\Loyaltyprogram\Profiles;
interface Profileinterface{
	
	function getParametersMain();
	//function getParametersBonuses();
	//function setBonus($userId);
	function save($params);
	
}
?>