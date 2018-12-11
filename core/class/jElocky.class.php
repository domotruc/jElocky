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

/* ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../3rparty/vendor/autoload.php';
//require_once __DIR__ . '/jElockyUtil.class.php';  // required for desktop/php/jElocky.php
require_once __DIR__ . '/jElocky_user.class.php';
require_once __DIR__ . '/jElocky_place.class.php';

//use Psr\Log\LogLevel;

class jElocky extends eqLogic {

    /* * *************************Attributs****************************** */

    /* * ***********************Methode static*************************** */

//     Is seems that it is not nessary, cronHourly is called at start by the core 
//     public static function start() {
//         jElockyLog::startStep(__METHOD__);
//         self::cronHourly();
//         jElockyLog::endStep();        
//     }
    
    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     */
    public static function cron() {
        jElockyLog::startStep(__METHOD__);
        if (cache::byKey('plugin::cronHourly::last')->getValue() != __CLASS__)
            jElocky_place::cronHighFreq();
        jElockyLog::endStep();
    }

    /*
     * Fonction exécutée automatiquement toutes heures par Jeedom
     */
    public static function cronHourly() {
        jElockyLog::startStep(__METHOD__);
        
        // Note: perform a full update at user level which update alco places
        jElocky_user::cronLowFreq();
        
        jElockyLog::endStep();
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
     * public function toHtml($_version = 'dashboard') {
     *
     * }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
     * public static function postConfig_<Variable>() {
     * }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
     * public static function preConfig_<Variable>() {
     * }
     */

    /* * **********************Getteur Setteur*************************** */
    /*
     * public static function logElockyAPI($_level, $_msg) {
     * switch ($_level) {
     * case LogLevel::EMERGENCY:
     * case LogLevel::ALERT:
     * case LogLevel::CRITICAL:
     * case LogLevel::ERROR:
     * $level = 'error';
     * break;
     * case LogLevel::WARNING:
     * $level = 'warning';
     * break;
     * case LogLevel::NOTICE:
     * case LogLevel::INFO:
     * $level = 'info';
     * break;
     * default:
     * $level = 'debug';
     * }
     *
     * log::add('jElocky', $level, 'ElockyAPI::' . $_msg);
     * }
     */
}

class jElockyCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
       public function dontRemoveCmd() {
       return true;
       }
     */

    public function execute($_options = array()) {
        
    }

    /*     * **********************Getteur Setteur*************************** */
}
