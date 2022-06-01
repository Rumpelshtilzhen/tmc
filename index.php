<?
	use \Bitrix\Main\Loader;
	use \Itbizon\Main\EstimateTable;
	use \Bitrix\Crm\DealTable;
	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<script src="//api.bitrix24.com/api/v1/"></script>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	<script src="js/main.js?<?= date('YmdHis') ?>"></script>
	<title>ТМЦ</title>
</head>
<body>
	<div class="container-fluid p-2">
<?
	try
	{
		// if(!Loader::includeModule('itbizon.main'))
		//     throw new Exception('Ошибка инициализации модуля');
		// if(!Loader::includeModule('crm'))
		//     throw new Exception('Ошибка инициализации модуля');

		if(isset($_REQUEST['PLACEMENT']))
		{
			$placement = $_REQUEST['PLACEMENT'];
			$placement_options = [];
			if(isset($_REQUEST['PLACEMENT_OPTIONS']))
				$placement_options = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true);
			if($placement == 'CRM_DEAL_DETAIL_TAB')
			{
				$id = intval($placement_options['ID']);
				if(!$id)
					throw new Exception('Некорректный идентификатор сделки');

				$deal = DealTable::getList([
						'select' => ['ID', 'TITLE', 'UF_CRM_1565616311'],
						'filter' => ['ID' => $id],
						'limit'  => 1
				])->fetch();
				if(!$deal)
					throw new Exception('Ошибка получения информации о сделке');
				$workflow = (!empty($deal['UF_CRM_1565616311']));

				$product_ids = [];

				$data = [];
				$result = EstimateTable::getList([
					'select' => [
						'*',
						'ROW_PRODUCT_ID'    => 'ROW.PRODUCT_ID',
						'ROW_PRODUCT_NAME'  => 'ROW.PRODUCT_NAME',
						'ROW_PRODUCT_NAME2' => 'ROW.CP_PRODUCT_NAME',
						'PLAN_CNT'          => 'ROW.QUANTITY'
					],
					'filter' => [
						'ENTITY_ID' => $id
					]
				]);
				while($row = $result->fetch())
				{
					//echo '<pre>'.print_r($row, true).'</pre>';
					if(empty($row['ROW_PRODUCT_NAME']))
						$row['ROW_PRODUCT_NAME'] = $row['ROW_PRODUCT_NAME2'];

					if(intval($row['ROW_PRODUCT_ID']) == 0)
						$row['ROW_PRODUCT_ID']   = $row['PRODUCT_ID'];

					$row['CNT']      = floatval($row['CNT']);
					$row['PLAN_CNT'] = floatval($row['PLAN_CNT']);
					$delta = $row['CNT'] - $row['PLAN_CNT'];
					$row['OVER_CNT'] = ($delta > 0) ? $delta : 0;

					if(empty($row['ROW_PRODUCT_NAME']))
					{
						$product_ids[] = $row['ROW_PRODUCT_ID'];
					}

					$data[] = $row;
				}

				//Get product names
				if(count($product_ids))
				{
					$result = \CCrmProduct::GetList(
						['NAME' => 'ASC'],
						['ID' => $product_ids,],
						['ID', 'NAME']
					);
					while($row = $result->Fetch())
					{
						foreach($data as &$item)
						{
							if($item['PRODUCT_ID'] == $row['ID'])
								$item['ROW_PRODUCT_NAME'] = $row['NAME'];
						}
						unset($item);
					}
				}

?>

		<div class="btn-group p-2" role="group">
			<button class="btn btn-secondary" onclick="window.location.reload(true)">Обновить</button>

<? 			if (!$workflow) : ?>

			<button class="btn btn-primary add-from-catalog-form">Выбрать товар из каталога</button>
			<button class="btn btn-primary add-product-form">Добавить товар в каталог</button>
			<button class="btn btn-success save-estimate">Сохранить</button>

<? 			endif; ?>

		</div>

<? 			if($workflow) : ?>

		<div class="alert alert-warning" role="alert">Смета отправлена на согласование</div>

<? 			endif; ?>

		<form id="estimate-form" <?= ($workflow) ? 'disabled' : '' ?> >
			<table id="params-table" class="table table-sm table-striped table-hover text-center align-middle table-bordered">
				<thead class="thead-dark">
					<tr>
						<th rowspan="2">Наименование</th>
						<th colspan="2">Количество</th>
						<th rowspan="2">Перерасход</th>
						<th rowspan="2"></th>
					</tr>
					<tr>
						<th>План</th>
						<th>Факт</th>
					</tr>
				</thead>
				<tbody>

<? 			foreach($data as $item) : ?>
	
					<tr class="<?= ($item['OVER_CNT'] > 0) ? 'table-danger' : '' ?>">
						<td><?= $item['ROW_PRODUCT_NAME'] ?>
							<input type="hidden" name="ID[]" value="<?= $item['ID'] ?>">
							<input type="hidden" name="ROW_ID[]" value="<?= $item['ROW_ID'] ?>">
							<input type="hidden" name="PRODUCT_ID[]" value="<?= $item['ROW_PRODUCT_ID'] ?>">
						</td>
						<td><?= $item['PLAN_CNT'] ?></td>
						<td><input name="CNT[]" data-plan-cnt="<?= $item['PLAN_CNT'] ?>" class="form-control text-center cnt-edit" <?= ($workflow) ? 'disabled' : '' ?> type="number" min="0" step="0.0001" value="<?= $item['CNT'] ?>"></td>
						<td><input class="form-control text-center over-cnt-edit" type="number" min="0" step="0.0001" value="<?= $item['OVER_CNT'] ?>" readonly></td>
						<td>

<? 				if(!$workflow) : ?>
	
							<button class="btn btn-danger delete-row" type="button" data-id="<?=$item['ID']?>" data-rowid="<?=$item['ROW_ID']?>">X</button>

<? 				endif; ?>

						</td>
					</tr>

<? 			endforeach; ?>

				</tbody>
			</table>
			<input type="hidden" name="DEAL_ID" value="<?= $id ?>">
		</form>

<?
			}
			else
			{
				echo '<div class="alert alert-success" role="alert">Приложение установлено</div>';
			}
		}
	}
	catch(Exception $e)
	{
		echo '<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';
	}
?>

	</div>
	<div id="popup" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body"></div>
				<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
				</div>
			</div>
		</div>
	</div>
</body>
</html>

