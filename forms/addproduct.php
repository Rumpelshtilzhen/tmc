<?php
use \Bitrix\Main\Loader;
use \bitrix\Crm\Measure;
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
try
{
    if(!$USER->IsAuthorized())
        throw new Exception('Access deny');
    if(!Loader::includeModule('crm'))
        throw new Exception('Error loa module crm');

    //Get section list
    $sections = [];
    $result = \CCrmProductSection::GetList();
    while($row = $result->Fetch())
        $sections[$row['ID']] = $row['NAME'];

    //Get units
    $measures = [];
    $result = Measure::getMeasures();
    foreach($result as $item)
        $measures[$item['ID']] = $item['SYMBOL'];

    //Get currency
    $currencies = [];
    $result = \CCrmCurrency::GetAll();
    foreach($result as $item)
        $currencies[$item['CURRENCY']] = $item['FULL_NAME'];
    ?>
    <div class="container-fluid">
        <form id="form-add-product">
            <div class="row form-group">
                <label class="col">Наименование</label>
                <div class="col">
                    <input name="NAME" class="form-control" type="text" required>
                </div>
            </div>
            <div class="row form-group">
                <label class="col">Описание</label>
                <div class="col">
                    <textarea name="DESCRIPTION" class="form-control"></textarea>
                </div>
            </div>
            <div class="row form-group">
                <label class="col">Раздел</label>
                <div class="col">
                    <select name="SECTION_ID" class="form-control" required>
                        <? foreach($sections as $id => $name) : ?>
                            <option value="<?= $id ?>"><?= $name ?></option>
                        <? endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row form-group">
                <label class="col">Ед. измерения</label>
                <div class="col">
                    <select name="MEASURE" class="form-control" required>
                        <? foreach($measures as $id => $name) : ?>
                            <option value="<?= $id ?>"><?= $name ?></option>
                        <? endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row form-group">
                <label class="col">Цена</label>
                <div class="col">
                    <input name="COST" class="form-control" type="number" min="0.01" step="0.01" required>
                </div>
            </div>
            <div class="row form-group">
                <label class="col">Валюта</label>
                <div class="col">
                    <select name="CURRENCY" class="form-control" required>
                        <? foreach($currencies as $id => $name) : ?>
                            <option value="<?= $id ?>"><?= $name ?></option>
                        <? endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row form-group">
                <button class="btn btn-success" type="submit">Добавить</button>
            </div>
        </form>
    </div>
    <?
}
catch(Exception $e)
{
    echo '<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';
}