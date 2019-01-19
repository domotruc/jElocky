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
require_once __DIR__ . '/jElockyLog.class.php';
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
    
    
    /**
     * @throws Exception if 
     */
    public static function event() {
        jElockyLog::add('debug', 'API::$_GET=' . json_encode($_GET));
        if (init('action') == 'test') {
            log::add('jElocky', 'info', 'API::tested ok from ' . getClientIp());
            echo date('r') . ' OK';
            return;
        }
        
        if (init('action') == 'trig_alarm') {
            /* @var jElocky_place $eql */
            $eql = jElocky_place::byId(init('id'));
            
            if (!is_object($eql)) {
                throw new Exception('API::trig_alarm::' . __('aucun lieu ne correspond à l\'id', __FILE__) . ' ' . secureXSS(init('id')));
            }
            if ($eql->getEqType_name() != jElocky_place::class) {
                throw new Exception('API::trig_alarm::id=' . secureXSS(init('id')) . ' ' . __("n'est pas un lieu", __FILE__));
            }
            
            if ($eql->getIsEnable())
                $eql->triggerAlarm();
            else
                throw new Exception('API::trig_alarm::' . __('lieu', __FILE__) . ' ' . $eql->getName() . ' ' . __('est inhibé', __FILE__) .
                    ' (id=' . secureXSS(init('id')) . ')');
                
            return;
        }
        
        throw new Exception('API::' . __("action non définie ou inconnue", __FILE__));
    }
    
    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     */
    public static function cron() {
        jElockyLog::startStep(__METHOD__);
        // To avoid running cron task if cronHourly is in progress
        if (cache::byKey('plugin::cronHourly::inprogress')->getValue() == 0 ||
            cache::byKey('plugin::cronHourly::last')->getValue() != __CLASS__) {
            jElocky_place::cronHighFreq();
            }
        else {
            jElockyLog::add('debug', 'cronHourly is running: exit');
        }
        jElockyLog::endStep();
    }

    /*
     * Fonction exécutée automatiquement toutes heures par Jeedom
     */
    public static function cronHourly() {
        jElockyLog::startStep(__METHOD__);
        
        // Note: perform a full update at user level (which update also places and objects)
        jElocky_user::update_all();
        
        jElockyLog::endStep();
    }

    /**
     * Patch the plugin.template.js core file for core version < 3.3.7
     * Apply the following patch :
     * https://github.com/jeedom/core/commit/1031e01d8e987a4871cfc350c3ff660555c239f2#diff-9a65a6a97d3bca1fefa4486002bcf61a
     * Impact only the desktop interface.
     */
    public static function patch_core() {
        $ver = jeedom::version();
        if (version_compare($ver, '3.3.7', '<')) {
            $f = __DIR__ . '/../../../../core/js/plugin.template.js';
            exec('patch -r - -N -b -i ' . __DIR__ . '/../../plugin_info/plugin.template.js.diff ' . $f);
            $err = 1;
            passthru('grep -q "prePrintEqLogic(\$(this).attr(\'data-eqLogic_id\'));" ' . $f, $err);
            if ($err != 0)
                jElockyLog::add('error', 'patch du core non effectué: le plugin ne fonctionnera pas correctement');
        }
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
