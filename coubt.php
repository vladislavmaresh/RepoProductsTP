private function productsTP()
	{
		if(!CModule::IncludeModule("iblock")) return false;
		if(!CModule::IncludeModule("catalog")) return false;

		if(empty($this->iblockIdTP)) return false;
        if(empty($this->siteURL)) return false;

		$arSelect = Array('ID', 'XML_ID', 'NAME');
		$arFilter = Array("IBLOCK_ID"=>$this->iblockIdTP);
		$res = CIBlockElement::GetList(
		    Array("ID" => "ASC"),
		    $arFilter,
		    false,
		    false,
		    $arSelect
		    );
		  
		while($ob = $res->GetNextElement())
		    {
		    $arFields = $ob->GetFields();
		    $arIdProd[$arFields['XML_ID']] = $arFields['ID'];
		    $arIdProdName[$arFields['NAME']] = $arFields['ID'];
		    $ids[] = $arFields['ID'];
		    }


		if(empty($_SESSION['lastid']['products'])){
			$lastId = 0;
		} else {
			$lastId = $_SESSION['lastid']['products'];
		}
		//Тянем недостающие элементы
		$Products = $this->send("getTP", "products", $lastId);
		// pp($Products);
		if(!empty($Products)) {
			$_SESSION['lastid']['products'] = max(array_keys($Products));
			echo '<script>
			startCountdown();
			function reload (){document.location.href = location.href};setTimeout("reload()", 2000);
			</script>';
		} else {
			$_SESSION['lastid']['products'] = 0;
			echo 'Все необходимые элементы добавлены и обновлены<br>';
		}

		$arFilter = array("IBLOCK_ID" =>$this->iblockIdTP);
		$arSort = array("ID" => "ASC");
		$uf_name = array("UF_ORIGINAL_ID");
		
		$rsSections = CIBlockSection::GetList($arSort, $arFilter, false, $uf_name);
		while($arSection = $rsSections->GetNext())
		{
			$arIdSec[$arSection['UF_ORIGINAL_ID']] = $arSection['ID'];
		}

		$countAdd = 0;
		$countUpdate = 0;
		foreach ($Products as $kProd => $vProd) {
			if(array_key_exists($vProd["ID"], $arIdProd)){
				$ID = $arIdProd[$vProd["ID"]];
			} else {
				// if(array_key_exists($vProd['NAME'], $arIdProdName)){
				// 	$ID = $arIdProd[$vProd["ID"]];
				// }else 
				$ID = 0;
			}

			if(array_key_exists($vProd["IBLOCK_SECTION_ID"], $arIdSec)){
				$IBLOCK_SECTION_ID = $arIdSec[$vProd["IBLOCK_SECTION_ID"]];
			} else {
				$IBLOCK_SECTION_ID = 0;
			}



			$arCatalog = CCatalog::GetByID($this->iblockIdTP);
			if (!$arCatalog) return false;

			$intProductIBlock = $arCatalog['PRODUCT_IBLOCK_ID']; // ID инфоблока товаров
			$intSKUProperty = $arCatalog['SKU_PROPERTY_ID']; // ID свойства в инфоблоке предложений типа "Привязка к товарам (SKU)"

			$arProp = array();
			if ($vProd['ORIGINAL_PRODUCT_ID'])
			{
			   $PROP[$intSKUProperty] = $vProd['ORIGINAL_PRODUCT_ID'];
			}


			$el = new CIBlockElement;
			$PROP = array();
			$PROP['ORIGINAL_ID'] = $vProd['ID'];
			$PROP['ORIGINAL_SEC_ID'] = $vProd['IBLOCK_SECTION_ID'];
;
			$code = randString(2);
			$arFields = Array(
				"SYNC" => true,
				"IBLOCK_SECTION_ID" => $IBLOCK_SECTION_ID,
				"IBLOCK_ID"     	=> $this->iblockIdTP,
				"ID"     			=> $ID,
				"PROPERTY_VALUES"	=> $PROP,
				"NAME"         	 	=> htmlspecialchars_decode($vProd['NAME']),
				"CODE"			 	=> $vProd['CODE'].$code,		
				"ACTIVE"         	=> $vProd['ACTIVE'],
				"PREVIEW_TEXT"		=> htmlspecialchars_decode($vProd['PREVIEW_TEXT']),		
				"DETAIL_TEXT"      	=> htmlspecialchars_decode($vProd['DETAIL_TEXT'])
				);

			if($ID > 0)
			{
				$res = $el->Update($ID, $arFields);
				if ($res){
					// echo 'Элемент обновлен ID: '.$ID.'<br>';
					$countUpdate++;

					$ar_res = CCatalogProduct::GetByID($ID);

					if(empty($ar_res)){

						$arFieldsProd = array(
							"ID" 			=> $ID,
							"AVAILABLE"		=> 'Y',
							"TYPE"			=> 4
							);

				      	CCatalogProduct::update($ID, $arFieldsProd);

						$arFields = array(
							"SYNC" => true,
						    "PRODUCT_ID" => $ID
						);

						CPrice::update($arFields);
				    }
				} 

			}
			  else
			{
				$ID = $el->Add($arFields);
				$res = ($ID > 0);
				if ($res){
					// echo 'Элемент добавлен ID: '.$ID.'<br>';
					$countAdd++;

					$ar_res = CCatalogProduct::GetByID($ID);

					if(empty($ar_res)){

						$arFieldsProd = array(
							"ID" 			=> $ID,
							"AVAILABLE"		=> 'Y',
							"TYPE"			=> 4
							);

				      	CCatalogProduct::Add($arFieldsProd);

						$arFields = array(
							"SYNC" => true,
						    "PRODUCT_ID" => $ID
						);
						CPrice::Add($arFields);
				    }
				}
			}
			if(!$res) echo $el->LAST_ERROR;

		}
		if(count($arIdProd)>0) echo count($arIdProd).' элементов в базе<br>';
		if($countAdd) echo 'Добавлено '.$countAdd.' элементов<br>';
		if($countUpdate) echo 'Обновлено '.$countUpdate.' элементов<br>';

	}
