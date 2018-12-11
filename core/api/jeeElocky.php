<?php

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
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

try {
    if (! jeedom::apiAccess(init('apikey'), 'jElocky')) {
        throw new Exception(__('Clef API non valide, vous n\'êtes pas autorisé à effectuer cette action', __FILE__));
    }

    // log::add('jElocky','debug', 'API::' . file_get_contents("php://input"));

    if (init('test') != '') {
        log::add('jElocky', 'info', 'API::tested ok from ' . getClientIp());
        echo 'OK';
        die();
    }

    if (init('action') == 'trig_alarm') {
        /* @var jElocky_place $eql */
        $eql = eqLogic::byId(init('id'));
        
        if (!is_object($eql)) {
            throw new Exception(__('Aucun équipement ne correspond à l\'ID : ', __FILE__) . secureXSS(init('id')));
        }
        if ($eql->getEqType_name() != jElocky_place::class) {
            throw new Exception(__("L'équipement n'a pas le type ", __FILE__) . jElocky_place::class .
                ' (ID=' . secureXSS(init('id')) . ')');
        }
        
        $eql->triggerAlarm();
    }
}
catch (Exception $e) {
    echo $e->getMessage();
    log::add('jElocky', 'error', 'API::' . $e->getMessage());
}