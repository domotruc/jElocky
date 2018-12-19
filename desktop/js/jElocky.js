
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

$(document).ready(function() {
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
});


$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

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
				console.log('MutationObserver: eqType=' + eqType);
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
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="value" placeholder="{{Valeur}}" readonly=true />';
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
    console.log(_cmd);
    console.log(tr);
    
    $(id + ' tbody').append(tr);
    $(id + ' tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $(id + ' tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($(id + ' tbody tr:last'), init(_cmd.subType));
    $(id + ' tbody tr:last .cmdAttr[data-l1key=value]').show();
}
