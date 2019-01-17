var refreshTimeout = undefined;

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

//Return the plugin base URL
//Parameters id, saveSuccessFull, removeSuccessFull are removed if present
function initPluginUrl(_filter=['id','saveSuccessFull','removeSuccessFull']) {
    var vars = getUrlVars();
    var url = '';
    for (var i in vars) {
        if ($.inArray(i,_filter) < 0) {
            if (url.length > 0)
                url += '&';
            url += i + '=' + vars[i].replace('#', '');
        }
    }
    return 'index.php?' + url;
}

//$(document).ready(function() {
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=photo]').on('change', function () {
        if($(this).value() != '' && $('.li_eqLogic.active').attr('data-eqlogic_id') != '') {
            $('.eqLogic:visible #photo_place,#photo_user').attr("src", DATA_DIR + "/" + $(this).value());
        }
    });
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=type_board]').on('change', function () {
        if($(this).value() != '' && $('.li_eqLogic.active').attr('data-eqlogic_id') != '') {
            $('.eqLogic:visible #photo_object').attr("src", "plugins/jElocky/resources/" + $(this).value() + '.png');
        }
    });
//    $('.li_eqLogic.active').attr('data-eqlogic_id') != '') {
//        
//    }
//});

//Override plugin template to rewrite the URL to avoid keeping the successfull save message
if (getUrlVars('saveSuccessFull') == 1) {
    $('#div_alert').showAlert({message: '{{Sauvegarde effectuée avec succès}}', level: 'success'});
    history.replaceState(history.state, '', initPluginUrl(['saveSuccessFull']));
}

//Override plugin template to rewrite the URL to avoid keeping the successfull delete message
if (getUrlVars('removeSuccessFull') == 1) {
    $('#div_alert').showAlert({message: '{{Suppression effectuée avec succès}}', level: 'success'});
    history.replaceState(history.state, '', initPluginUrl(['removeSuccessFull']));
}

function callAjax(_action_id, _eq_id, _async) {
	$.ajax({
		type: "POST", 
		url: "plugins/jElocky/core/ajax/jElocky.ajax.php", 
		data: {
			action: _action_id,
			eq_id: _eq_id,
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) { 
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
		},
		async: _async
	});
}

/*
 * Callback called by plugin template before displaying an eqLogic
 */
function prePrintEqLogic(_eq_id) {
	callAjax('update1', _eq_id, false);
	callAjax('update2', _eq_id, true);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Management of the addition of an eqLogic (jElocky_user, jElocky_place)
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// This is a workaround to allow creation of an eqLogic subtype in plugin.template
$('.eqLogicAction[data-action=add]').on('click', function () {
	eqType = $(this).attr('data-eqLogic_type');
});

// Once the eqLogic creation by plugin.template is done, a message is displayed in #div_alert.
// We catch this event to reset the eqType variable to the plugin name (mandatory for plugin.template
// to work well)
var observer = new MutationObserver(function(mutations) {
	mutations.forEach(function(mutation) {
		if (mutation.addedNodes !== null) {
			if (eqType.startsWith('jElocky')) {
				eqType = 'jElocky';
			}
		}
	});    
});

observer.observe($("#div_alert")[0], { 
	attributes: true
});

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*
 * Override eqLogic page tab management to synchronise the tabs in all eqLogic
 */
$('.eqLogic a[data-toggle=tab]').on('click', function () {
    selected_tab = this.hash;
    $('.eqLogic').each(function() {
        if ($(this).find('a[href="' + selected_tab + '"]').length > 0) {
            $(this).find('a[data-toggle=tab]').each(function() {
                if (this.hash == selected_tab)
                    $(this).attr('aria-expanded', true).closest('li').addClass("active");
                else
                    $(this).attr('aria-expanded', false).closest('li').removeClass("active");
            }).end().find('.tab-pane').each(function() {
                if ($(this).is(selected_tab))
                    $(this).addClass("active");
                else
                    $(this).removeClass("active");
            });
        }
    });
});

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$('body').off('jElocky::insert').on('jElocky::insert', function (_event,_options) {

    var msg = '{{L\'équipement}} <b>' + _options['eqlogic_name'] + '</b> {{vient d\'être inclu}}';

    // If the page is being modified or an equipment is being consulted or a dialog box is shown: display a simple alert message
    // Otherwise: display an alert message and reload the page
    if (modifyWithoutSave || $('div[role="dialog"]').filter(':visible').length != 0) {
        $('#div_newEqptMsg').showAlert({
            message: msg + '. {{Réactualiser la page}}.',
            level: 'warning'});
    }
    else {
        $('#div_newEqptMsg').showAlert({
            message: msg + '. {{La page va se réactualiser automatiquement}}.',
            level: 'warning'
        });
        // Reload the page after a delay to let the user read the message
        if (refreshTimeout === undefined) {
            console.log('refresh is sheduled');
            refreshTimeout = setTimeout(function() {
                refreshTimeout = undefined;
                console.log('refresh');
                window.location.reload();
            }, 2000);
        }
    }
});

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    //var disabled = (init(_cmd.configuration.virtualAction) == '1') ? 'disabled' : '';

    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom}}">';
    tr += '</td>';
    tr += '<td>';
	tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled style="margin-bottom:5px;width:120px;" />';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="form-control input-sm" data-key="value" placeholder="{{Valeur}}" readonly=true />';
    tr += '</td>';
    tr += '<td>';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}">';
    tr += '</td>';
    tr += '<td>';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    //tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    tr += '</td>';
    tr += '</tr>';
    
    id = '#' + _cmd.eqType.substr(_cmd.eqType.indexOf('_')+1) + '_table_cmd';
    
    $(id + ' tbody').append(tr);
    $(id + ' tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $(id + ' tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($(id + ' tbody tr:last'), init(_cmd.subType));
    //$(id + ' tbody tr:last .cmdAttr[data-l1key=value]').show();
    jeedom.cmd.update[_cmd.id] = function(_options) {
        $('.cmd[data-cmd_id=' + _cmd.id + '] .form-control[data-key=value]').value(_options.display_value);
    }
}
