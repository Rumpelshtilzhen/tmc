<?php
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
use \Bitrix\Main\Loader;
use \Itbizon\Main\EstimateManager;
use \Bitrix\Crm\DealTable;
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
header('Content-Type: application/json');

try
{
	// if(!$USER->IsAuthorized())
	//     throw new Exception('Access deny');
	// if(!Loader::includeModule('crm'))
	//     throw new Exception('Error load module crm');
	// if(!Loader::includeModule('bizproc'))
	//     throw new Exception('Error load module bizproc');
	// if(!Loader::includeModule('itbizon.main'))
	//     throw new Exception('Error load module itbizon.main');

	$cmd = $_POST['cmd'];

	//Get product list
	if($cmd == 'product-list')
	{
		$section_id = (isset($_POST['section_id'])) ? intval($_POST['section_id']) : 0;
		$search     = (isset($_POST['search'])) ? strval($_POST['search']) : '';
		$filter = [];
		if($section_id > 0)
			$filter['=SECTION_ID'] = $section_id;
		if(!empty($search))
			$filter['NAME'] = '%'.$search.'%';
		$products = [];
		$result = \CCrmProduct::GetList(
			['NAME' => 'ASC'],
			$filter,
			['ID', 'NAME']
		);
		while($row = $result->Fetch())
		{
			$row['NAME'] = htmlspecialchars($row['NAME']);
			$products[] = $row;
		}
		echo json_encode(['STATUS' => true, 'MESSAGE' => '', 'DATA' => $products]);
	}
	//Add product
	else if($cmd == 'add-product')
	{
		$name = strval($_POST['NAME']);
		if(empty($name))
			throw new Exception('Наименование товара не заполнено');

		$description = strval($_POST['DESCRIPTION']);

		$section_id = intval($_POST['SECTION_ID']);
		if($section_id <= 0)
			throw new Exception('Некорректный раздел');

		$measure = intval($_POST['MEASURE']);
		if($measure <= 0)
			throw new Exception('Некорректно заданы единицы измерения');

		$price = floatval($_POST['COST']);
		if($price < 0.01)
			throw new Exception('Некорректно заданы цена');

		$currency_id = strval($_POST['CURRENCY']);
		if(empty($currency_id))
			throw new Exception('Некорректно заданы валюта');

			$id = \CCrmProduct::Add([
				'NAME'        => $name,
				'ACTIVE'      => 'Y',
				'DESCRIPTION' => $description,
				'SECTION_ID'  => $section_id,
				'MEASURE'     => $measure,
				'PRICE'       => $price,
				'CURRENCY_ID' => $currency_id
			]);
			if($id === false)
				throw new Exception(\CCrmProduct::GetLastError());

			echo json_encode(['STATUS' => true, 'MESSAGE' => 'Товар успешно добавлен (ID: '.$id.')']);
	}
	//Save estimate
	else if($cmd == 'save-estimate')
	{
		$deal_id = intval($_POST['deal_id']);
		$data    = (is_array($_POST['data'])) ? $_POST['data'] : [];

		EstimateManager::save($deal_id, $data);

		$overrun = EstimateManager::check($deal_id);

		if(!empty($overrun))
		{
			$arErrorsTmp = [];
			$workflow_id = CBPDocument::StartWorkflow(
				53,
				['crm', 'CCrmDocumentDeal', 'DEAL_'.$deal_id],
				['TargetUser'  => 'user_'.$USER->GetID()],
				$arErrorsTmp
			);
			if(!empty($arErrorsTmp))
				throw new Exception('BP: '.print_r($arErrorsTmp, true));

			echo json_encode(['STATUS' => true, 'MESSAGE' => 'Смета успешно обновлена и отправлена на согласование']);
		}
		else
		{
			echo json_encode(['STATUS' => true, 'MESSAGE' => 'Смета успешно обновлена']);
		}
	}
	elseif($cmd == 'delete-row')
	{
		$id = $_POST['id'];
		$rowId = $_POST['rowid'];

		CCrmProductRow::Delete($rowId);

		$result = \Itbizon\Main\EstimateTable::delete($id);
		if(!$result->isSuccess())
		{
			echo json_encode(['STATUS'=>false, 'MESSAGE'=>array_shift($result->getErrorMessages())]);
		}
		else
			echo json_encode(['STATUS'=>true, 'MESSAGE'=>$_POST]);
	}
	else
		throw new Exception('Invalid command');
}
catch(Exception $e)
{
	echo json_encode(['STATUS' => false, 'MESSAGE' => $e->getMessage()]);
}

