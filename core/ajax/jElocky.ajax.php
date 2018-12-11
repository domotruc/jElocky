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

require_once __DIR__ . '/../class/jElockyLog.class.php';

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
    ajax::init();
    
    $action = init('action');
      
    // Check eqLogic identifier
    if (in_array($action, array("update1", "update2"))) {
        $eqLogic = eqLogic::byId(init('eq_id'));
        if (! is_object($eqLogic)) {
            throw new Exception(__('eqLogic inconnu. Vérifiez l\'ID', __FILE__));
        }
    }
    
    if (in_array($action, array("update1", "update2"))) {
        jElockyLog::startStep('ajax action ' . $action . ' on ' . $eqLogic->getName());
        if (method_exists($eqLogic, $action)) {
            $eqLogic->$action();
        }
        if ($action == 'update1')
            $eqLogic->save();

        jElockyLog::endStep();
        ajax::success();
    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}

