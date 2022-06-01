//load product table
function loadProductTable(section_id, search = null) {
    let table = $('#product-table tbody');
    let request = {
        cmd : 'product-list',
        section_id : section_id,
    };
    if(search !== null)
        request.search = search;
    table.empty();
    $.post(
        'ajax.php',
        request,
        function(result) {
            if(result.STATUS) {
                if(result.DATA.length)
                {
                    for(let id in result.DATA){
                        table.append(
                            '<tr>' +
                            '<td>' + result.DATA[id].ID + '</td>' +
                            '<td>' + result.DATA[id].NAME + '</td>' +
                            '<td><button class="btn btn-secondary btn-select-product" data-product-id="' + result.DATA[id].ID + '" data-product-name="' + result.DATA[id].NAME + '">Выбрать</button></td>' +
                            '</tr>'
                        );
                    }
                }
                else
                {
                    table.append('<tr><td colspan="3">Нет данных</td></tr>');
                }
            }
            else {
                alert(result.MESSAGE);
            }
        }
    ).fail(function(){
        alert('AJAX Error');
    });
}

//show popup form
function showPopup(title, data) {
    let form = $('#popup');
    let body = form.find('.modal-body');
    let header = form.find('.modal-title');
    header.text(title);
    body.html(data);
    form.modal('show');
}

BX24.init(function() {
    BX24.ready(function(){
        BX24.fitWindow();

        //Change position cnt
        $(document).on('change', '.cnt-edit', function() {
            console.log('change');
            let tr = $(this).closest('tr');
            let plan_cnt = Number($(this).data('plan-cnt'));
            let cnt      = Number($(this).val());
            let over_cnt = tr.find('.over-cnt-edit');

            if(cnt > plan_cnt)
            {
                tr.addClass('table-danger');
                over_cnt.val(cnt - plan_cnt);
            }
            else
            {
                tr.removeClass();
                over_cnt.val(0);
            }
        });

        //Add product from catalog form
        $(document).on('click', '.add-from-catalog-form', function(evt) {
            evt.preventDefault();
            let form = $('#popup');
            let body = form.find('.modal-body');

            $.get(
                'forms/selectfromcatalog.php',
                {},
                function(result) {
                    showPopup('Выбрать товар из каталога', result);
                }
            ).fail(function(){
                alert('AJAX Error');
            });
        });

        //Select section
        $(document).on('click', '.select-product-section', function(evt) {
            evt.preventDefault();
            let section_id = $(this).data('section-id');

            let search = $('.product-search');
            $.each(search, function(i, v) {
                $(v).data('section-id', section_id);
                $(v).val('');
            });

            loadProductTable(section_id);
        });

        //Search by name
        $(document).on('keypress', '.product-search', function(evt) {
            let section_id = $(this).data('section-id');
            let search = $(this).val();

            loadProductTable(section_id, search);
        });

        //Select product
        $(document).on('click', '.btn-select-product', function(evt) {
            evt.preventDefault();
            let table = $('#params-table tbody');
            let product_id   = $(this).data('product-id');
            let product_name = $(this).data('product-name');
            let allow_add = true;
            $.each(table.children('tr'), function(i, v) {
                let id = $(v).find('[name="PRODUCT_ID[]"]').val();
                if(Number(id) === Number(product_id))
                    allow_add = false;
            });
            if(allow_add)
            {
                table.prepend(
                    '<tr class="table-danger">' +
                    '<td>' + product_name + '' +
                    '<input type="hidden" name="ID[]" value="0">' +
                    '<input type="hidden" name="ROW_ID[]" value="0">' +
                    '<input type="hidden" name="PRODUCT_ID[]" value="' + product_id + '">' +
                    '</td>' +
                    '<td>0.0000</td>' +
                    '<td><input name="CNT[]" data-plan-cnt="0.0000" class="form-control text-center cnt-edit" type="number" min="0" step="0.0001" value="1"></td>' +
                    '<td><input class="form-control text-center over-cnt-edit" type="number" min="0" step="0.0001" value="1" readonly=""></td>' +
                    '<td><button class="btn btn-danger delete-item">x</button></td>' +
                    '</tr>');
            }
            else {
                alert('Товар #' + product_id + ' уже есть в смете');
            }
            BX24.fitWindow();
        });

        //Delete custom row
        $(document).on('click', '.delete-item', function(evt){
            evt.preventDefault();

            $(this).closest('tr').remove();
        });

        //delete estimate row
        $(document).on('click', '.delete-row', function(evt){
            evt.preventDefault();
            let row = $(this).closest('tr');

            if(confirm('Удалить товар из таблицы ?'))
            {
                let request = {};
                request.cmd = 'delete-row';
                request.id = $(this).data('id');
                request.rowid = $(this).data('rowid');

                $.post(
                    'ajax.php',
                    request,
                    function(result) {
                        if(result.STATUS) {
                            row.remove();
                            console.log(result.MESSAGE);
                        }
                        else {
                            console.log(result.MESSAGE);
                        }
                    }
                ).fail(function(){
                    alert('AJAX Error');
                });
            }
        });

        //Show add product form
        $(document).on('click', '.add-product-form', function(evt) {
            evt.preventDefault();

            $.get(
                'forms/addproduct.php',
                {},
                function(result) {
                    showPopup('Добавить товар', result);
                }
            ).fail(function(){
                alert('AJAX Error');
            });
        });

        //Add product to catalog
        $(document).on('submit', '#form-add-product', function(evt) {
            evt.preventDefault();

            let request = {};
            request.cmd = 'add-product';
            let raw_request = $(this).serializeArray();
            $.each(raw_request, function(i, v) {
                request[v.name] = v.value;
            });
            $.post(
                'ajax.php',
                request,
                function(result) {
                    if(result.STATUS) {
                        alert(result.MESSAGE);
                        $('#popup').modal('hide');
                    }
                    else {
                        alert(result.MESSAGE);
                    }
                }
            ).fail(function(){
                alert('AJAX Error');
            });
        });

        //Save estimate
        $(document).on('click', '.save-estimate', function(evt) {
            evt.preventDefault();

            let form     = $('#estimate-form');
            let rows     = $('#params-table tbody tr');
            let request  = {};
            request.cmd  = 'save-estimate';
            request.deal_id = form.find('[name="DEAL_ID"]').val();
            request.data = [];

            $.each(rows, function(i, v) {
                let id         = $(v).find('[name="ID[]"]').val();
                let row_id     = $(v).find('[name="ROW_ID[]"]').val();
                let product_id = $(v).find('[name="PRODUCT_ID[]"]').val();
                let cnt        = $(v).find('[name="CNT[]"]').val();
                request.data.push({
                    ID : id,
                    ROW_ID : row_id,
                    PRODUCT_ID : product_id,
                    CNT : cnt
                });
            });

            console.log(request);

            $.post(
                'ajax.php',
                request,
                function(result) {
                    if(result.STATUS) {
                        alert(result.MESSAGE);
                        window.location.reload(true);
                    }
                    else {
                        alert(result.MESSAGE);
                    }
                }
            ).fail(function(xhr, status){
                console.log(xhr);
                alert('AJAX Error: '+status);
            });
        });
    });
});