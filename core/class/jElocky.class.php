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
    
    const LOCK_CACHE = 'jElocky::lock';
    
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
        
        if (init('action') == 'trigger_alarm') {
            /* @var jElocky_place $eql */
            $eql = jElocky_place::byId(init('id'));
            
            if (!is_object($eql)) {
                throw new Exception('API::trigger_alarm::' . __('aucun lieu ne correspond à l\'id', __FILE__) . ' ' . secureXSS(init('id')));
            }
            if ($eql->getEqType_name() != jElocky_place::class) {
                throw new Exception('API::trigger_alarm::id=' . secureXSS(init('id')) . ' ' . __("n'est pas un lieu", __FILE__));
            }
            
            if ($eql->getIsEnable())
                $eql->triggerAlarm();
            else
                throw new Exception('API::trigger_alarm::' . __('lieu', __FILE__) . ' ' . $eql->getName() . ' ' . __('est inhibé', __FILE__) .
                    ' (id=' . secureXSS(init('id')) . ')');
                
            return;
        }
        
        throw new Exception('API::' . __("action non définie ou inconnue", __FILE__));
    }
    
    /**
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

    /**
     * Fonction exécutée automatiquement toutes heures par Jeedom
     */
    public static function cronHourly() {
        jElockyLog::startStep(__METHOD__);
        
        // Note: perform a full update at user level (which update also places and objects)
        //jElocky_user::update_all();
        
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
    
    /**
     * Get the jElocky lock.
     * A lock system is implement to avoid concurrent task to modify the jElocky objects at the same time (e.g.
     * cron task and object removal from the interface).
     * If the lock is already picked the calling tack will wait for the other task to release the lock for 10s
     * max. After 10s, an exception is raise signifying to the calling task that it shall stop its execution.
     * A protection mechanism is implemented to avoid dead lock : lock will be released after 100s max. 
     * @throws Exception if lock cannot be obtained after 10s
     * @return boolean true if success to pick the lock
     */
    public static function pickLock() {
        $pid = getmypid();
        $lock = cache::byKey(self::LOCK_CACHE)->getValue();
        if (is_array($lock) && !posix_kill($lock['pid'], 0)) {
            jElockyLog::add('warning', "verrouillé par une tâche qui n'existe plus: déverrouille");
            $lock = array('pid' => $pid, 'nb' => 1);
        }
        else if (is_array($lock)) {
            if ($lock['pid'] == $pid) {
                $lock['nb'] += 1;
            }
            else {
                jElockyLog::add('debug', 'wait lock (picked by pid=' . $lock['pid'] . ')');
                $lock = self::waitLock($lock);
            }
        }
        else {
            $lock = array('pid' => $pid, 'nb' => 1);
        }
        
        cache::set(self::LOCK_CACHE, $lock);
        jElockyLog::add('debug', 'pick lock ' . json_encode($lock));
        return true;
    }
    
    /**
     * Release the lock (for the current task)
     */
    public static function releaseLock() {
        $pid = getmypid();
        $lock = cache::byKey(self::LOCK_CACHE)->getValue();
        if (is_array($lock)) {
            if ($lock['pid'] == $pid) {
                if (($lock['nb'] -= 1) == 0) {
                    jElockyLog::add('debug', 'lock is released (pid=' . $pid . ')');
                    cache::set(self::LOCK_CACHE, null);
                }
                else {
                    jElockyLog::add('debug', 'release lock ' . json_encode($lock));
                    cache::set(self::LOCK_CACHE, $lock);
                }
            }
            else {
                jElockyLog::add('warning', "verrouillé par tâche " . $lock['pid'] . ": dévérouillage non effectué");
            }
        }
        else {
            jElockyLog::add('warning', "dévérouillage demandé alors que non vérouillé");
        }
    }
    
    /**
     * Wait the lock is released and pick it
     * @param array $lock lock array
     * @throws Exception if lock cannot be obtained after 10s
     * @return array lock array
     */
    private static function waitLock($lock) {
        $dt = 0;
        do {
            usleep(100000);
            $dt +=  0.1;
            if ($dt >= 10) {
                $cron = new cron();
                $cron->setClass(__CLASS__);
                $cron->setFunction('resetLock');
                $cron->setOption($lock['pid']);
                $cron->setOnce(1);
                // Actual delay between 30s and 90s
                $cron->setSchedule(cron::convertDateToCron(strtotime('now')+30));
                $cron->save();
                jElockyLog::addException("verrouillage impossible, la tâche est abandonnée");
            }
            $lock = cache::byKey(self::LOCK_CACHE)->getValue();
        } while (is_array($lock));
        
        // Lock is released. Picked it again with the current pid.
        return array('pid' => getmypid(), 'nb' => 1);
    }
    
    
    /**
     * Reset the lock of the given task.
     * Does nothing if no lock or locked by another task.
     * Note: this method shall be public to be called as callback of a cron task, and in a class visible
     * from the core (does not work if located in jElockyUtil).  
     * @param int $pid
     */
    public static function resetLock($pid) {
        $lock = cache::byKey(self::LOCK_CACHE)->getValue();
        if (is_array($lock) && $lock['pid'] == $pid) {
            cache::set(self::LOCK_CACHE, null);
            jElockyLog::add('warning', "force le déverrouillage de la tâche pid " . $pid);
        }
        else
            jElockyLog::add('info', "la tâche pid " . $pid . " s'est finalement déverouillée");
    }
}

class jElockyCmd extends cmd {
    public function execute($_options = array()) {  
    }
}
