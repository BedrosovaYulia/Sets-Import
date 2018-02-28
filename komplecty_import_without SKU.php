<?

//YBWS



AddEventHandler("iblock", "OnAfterIBlockElementUpdate", array('YBWSMoyKomplectyImport', 'onAfterProductUpdate'));
AddEventHandler("iblock", "OnAfterIBlockElementAdd", array('YBWSMoyKomplectyImport', 'onAfterProductUpdate'));


class YBWSMoyKomplectyImport {
	

	public static $disableHandler = false;
	
	
	function onAfterProductUpdate(&$arFields)
	{
		
		if (self::$disableHandler)
            return;
		
		
		$CatalogID=$arFields['IBLOCK_ID'];
		
		$mxResult = CCatalogSKU::GetInfoByProductIBlock(
			$CatalogID
		);
		if (is_array($mxResult))
		{//Если инфоблок является торговым каталогом
	
			//Ищем свойство с реквизитами
			
			
			$properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$arFields['IBLOCK_ID'], "XML_ID"=>"CML2_TRAITS"));
			while ($prop_fields = $properties->GetNext())
			{
			  
			  $CatalogTrPrID= $prop_fields["ID"];
			}
			
			if (isset($CatalogTrPrID)){//если существует свойство с реквизитами
	
	
			
			//смотрим, комплект ли это
			
			$komplect=false;
			
			foreach ($arFields['PROPERTY_VALUES'][$CatalogTrPrID] as $trait){
				/*print "<pre>";
				print_r($trait);
				print "</pre>";*/
				
				if ($trait['DESCRIPTION']=='ТипНоменклатуры' && $trait['VALUE']=='Комплект'){
					
					$komplect=true;
				}
				
			}
			
			if ($komplect){
				
				Cmodule::IncludeModule('catalog');
				Cmodule::IncludeModule('iblock');
				
				
				//Подготавливаю комплектующие к записи/апдейту комплекта
				
					$items=array();
					foreach ($arFields['PROPERTY_VALUES'][$CatalogTrPrID] as $trait){
					
						
						if ($trait['DESCRIPTION']=='Комплектующее'){
							$p_xml_id_arr=explode("*",$trait['VALUE']);
							$p_xml_id=$p_xml_id_arr[1];
							$p_kolvo=$p_xml_id_arr[2];
							
							//Ищем ID товара или торгового предложения по его XML_ID
							
							
								$arSelect = Array("ID", "NAME");
								$arFilter = Array("IBLOCK_ID"=>$CatalogID, "XML_ID"=>$p_xml_id);
								$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
								if($ob = $res->GetNextElement())
								{
									$arFields3 = $ob->GetFields();
									print "<pre>";
									print_r($arFields3);
									print "</pre>";
									
									$items[]=array(
												'ITEM_ID'          => (int)$arFields3['ID'],
												'QUANTITY'         => (int)$p_kolvo,
												'DISCOUNT_PERCENT' => 0,
												'SORT'             => 100,
											);
											
									
									
									
								}//конец если товар найден по xml_id
							
						}//конец Если перебираемый реквизит - это комплектующее
				
					}//конец перебираем реквизиты



				$arSetsByProduct = CCatalogProductSet::getAllSetsByProduct($arFields['ID'], CCatalogProductSet::TYPE_SET);
				$arSetsByProduct = array_shift($arSetsByProduct); 
				
				//define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/log.txt");AddMessage2Log($arFields['ID'], "my_module_id");AddMessage2Log($arSetsByProduct, "my_module_id");
				
				//Запись или апдейт комплекта
				
				if (!isset($arSetsByProduct)){
					
					
					
					self::$disableHandler = true;
					
					$arFields2 = array('TYPE' => 1);
					CCatalogProduct::Update($arFields['ID'], $arFields2);
					
					self::$disableHandler = false;
					
					

					$arSaveSet = array(
						'TYPE'    => 1,
						'ITEM_ID' => $arFields['ID'],
						'ACTIVE'  => "Y",
						'ITEMS'   => $items
					);
					
					
					$setId = CCatalogProductSet::add($arSaveSet); // создание самого "комплекта"
					
					
					
					CCatalogProductSet::recalculateSetsByProduct($arFields['ID']);

				}//конец если комплект еще не создан
				else{
					
					
					$arSaveSet = array(
						//'TYPE'    => 1,
						//'ITEM_ID' => $arFields['ID'],
						//'ACTIVE'  => "Y",
						'ITEMS'   => $items
					);
					
				/*	print "<pre>";
					print_r(array($arSetsByProduct['SET_ID'],$arSaveSet));
					print "</pre>";
					
					die();*/
					
					CCatalogProductSet::update($arSetsByProduct['SET_ID'],$arSaveSet); // апднйт "комплекта"
					CCatalogProductSet::recalculateSetsByProduct($arFields['ID']);
					
					
					
				}//конец если апдейт компелкта
				
				
				
			}//Конец, если в реквизитах указано, что данный товар - это комплект
			
			
			
			
			}//конец если существует свойство с реквизитами
		}//конец если инфоблок является торговым каталогом
	}//конец после апдейта
	
}//конец класса

?>