(function(){
BX24.init(function() {
	console.log('Инициализация завершена!', BX24.isAdmin());
	//BX24.installFinish();
	var props = {
		PLACEMENT	: 'CRM_DEAL_DETAIL_TAB',
		HANDLER		: '.../tmc/index.php'
	};
	var bindPlacement = function() {
		BX24.callMethod(
			'placement.bind',
			Object.assign({}, props, {
				TITLE       : 'Смета фактическая',
				DESCRIPTION : '',
			}),
			function(result) {
				console.log('bind');
				console.log(result.data());
				if(result.data()) BX24.installFinish();
				else alert(result.error());
			}
		);
	};

	document.querySelector('#btn-install').addEventListener('click', function(evt) { 
		evt.preventDefault();
		//BX24.installFinish();
		BX24.callMethod(
			'placement.get',
			{},
			function(result) {
				console.log('get');
				if(result.error()) console.log(result.error());
				else {
					console.log(result.data());
					BX24.callMethod(
						'placement.unbind',
						props,
						function(result) {
							console.log('unbind');
							if(result.error()) console.log(result.error());
							else {
								console.log(result.data());
								bindPlacement();
							}
						}
					);
				}
			}
		);
	});
});
}());