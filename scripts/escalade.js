Ext.onReady(function() {
    // Défini les liens à trouver selon la page
    if(location.pathname.indexOf('ticket.form.php')>0) {
       // page des tickets
       searchQuery = 'a[href*="escalade/front/climb_group.php"]';
    } else if(location.pathname.indexOf('plugins/escalade/front/popup_histories.php')>0) {
       // page de l'historique complet
       searchQuery = 'a[href*="../front/climb_group.php"]';
    } else {
       searchQuery = '';
    }

    // N'agit que sur les pages identifiées
    if (searchQuery) {
    	function editLinks() {
            var success = false;
    	  	Ext.select(searchQuery).each(function(el) {
                var href = el.getAttribute('href');
                var newHref = href // deux replace pour les deux types de liens possibles (un seul s'applique)
                        .replace('escalade/front/climb_group.php', 'tickettransfer/front/escalade.form.php')
                        .replace('../front/climb_group.php', '../../tickettransfer/front/escalade.form.php');
                el.set({href : newHref});
                success = true;
    	    });
            return success;
    	}

    	doUntilSuccess(editLinks, 100, 60);
    }


    function doUntilSuccess(func, interval, retry) {
        var i=0;
        var t = setInterval(function() {
            i++;
            if(i > retry && retry!=0 || func()) {
                clearInterval(t);
            }
        }, interval);
    }

});
