<?php
use \Bitrix\Main\Loader;
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
try
{
    if(!$USER->IsAuthorized())
        throw new Exception('Access deny');
    if(!Loader::includeModule('crm'))
        throw new Exception('Error loa module crm');

    $data = [];
    $result = \CCrmProductSection::GetList();
    while($row = $result->Fetch())
    {
        $row['PARENT_ID'] = null;
        $row['CHILDS']    = [];
        $row['PRODUCTS']  = [];
        $data[$row['ID']] = $row;
    }
    foreach($data as $id => $row)
    {
        $pid = intval($row['SECTION_ID']);
        if($pid > 0)
        {
            $data[$id]['PARENT_ID'] = $pid;
            $data[$pid]['CHILDS'][] = &$data[$id];
        }
    }

    function showSectionTree($data, $parent = null)
    {
        echo '<ul style="list-style-type: square">';
        foreach($data as $id => $row)
        {
            if($row['PARENT_ID'] == $parent)
            {
                if(empty($row['CHILDS']))
                    echo '<li><a href="#" class="select-product-section" data-section-id="'.$row['ID'].'">'.$row['NAME'].'</a></li>';
                else
                {
                    echo '<li><a href="#" class="select-product-section" data-section-id="'.$row['ID'].'">'.$row['NAME'].'</a>';
                    showSectionTree($row['CHILDS'], $id);
                    echo '</li>';
                }
            }
        }
        echo '</ul>';
    }
    ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-3 p-1">
                <? showSectionTree($data); ?>
            </div>
            <div class="col">
                <input type="text" class="form-control product-search">
                <div style="max-height: 500px; overflow-y: auto;">
                <table id="product-table" class="table table-sm table-striped table-hover text-center align-middle table-bordered">
                    <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Наименование</th>
                        <th>Выбрать</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    <?
}
catch(Exception $e)
{
    echo '<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';
}