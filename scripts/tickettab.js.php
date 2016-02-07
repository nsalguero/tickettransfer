
<script>
Ext.onReady(function() {
	var ajaxUrl = '<?php global $CFG_GLPI; echo $CFG_GLPI["root_doc"] . "/plugins/tickettransfer/ajax/transferOptions.php"?>';

	Ext.get('dropdown_transfer_type_tickettransfer').on('change', refreshTransferZone);
	Ext.get('dropdown_entities_id_tickettransfer').on('change', refreshCategories);
	Ext.get('dropdown_type_tickettransfer').on('change', refreshCategories);
	Ext.get('dropdown_itilcategories_id_tickettransfer').on('change', refreshTransferOptions);

	refreshTransferZone();

	/**
	 * Cette fonction modifie l'affichage des champs en fonction du mode de transfert sélectionné
	 */
	function refreshTransferZone() {
		var type = Ext.get('dropdown_transfer_type_tickettransfer').getValue();
		var ntype = type === 'entity' ? 'group' : 'entity';

		Ext.select('.tickettransfer'+type).each(function(el) {
			el.setVisibilityMode(Ext.Element.DISPLAY).show();
		});
		
		Ext.select('.tickettransfer'+ntype).each(function(el) {
			el.setVisibilityMode(Ext.Element.DISPLAY).hide();
		});
		
	}

	/**
	 * Cette fonction rafraichit la liste des catégories
	 * Si le rafraichissement de la liste change la catégorie sélectionnée (se produit lorsqu'elle n'est plus disponible), les options de transfert sont rafraichies elles aussi
	 */
	function refreshCategories() {
		var currentcategory = Ext.get('dropdown_itilcategories_id_tickettransfer').getValue();
		Ext.get('selectZone_itilcategories_id_tickettransfer').load({
			url : ajaxUrl,
			scripts: true,
			params: {
				'request': 'itilcategories',
				'entities_id': Ext.get('dropdown_entities_id_tickettransfer').getValue(),
				'type': Ext.get('dropdown_type_tickettransfer').getValue()
			},
			callback: function() {
				var newcategory = Ext.get('dropdown_itilcategories_id_tickettransfer').getValue();
				if(currentcategory !== newcategory) {
					refreshTransferOptions();
				}
				Ext.get('dropdown_itilcategories_id_tickettransfer').on('change', refreshTransferOptions);
			}
		});
	}

	/**
	 * Rafraichit les options de transfert
	 */
	function refreshTransferOptions() {
		Ext.get('selectZone_transfer_options_tickettransfer').load({
			url : ajaxUrl,
			scripts: true,
			params: {
				'request': 'transfer_options',
				'itilcategories_id': Ext.get('dropdown_itilcategories_id_tickettransfer').getValue()
			}
		});
	}

});
</script>