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

/* ****************************** Includes ********************************* */
//require_once __DIR__ . '/../../../../core/php/core.inc.php';

class jElockyLog {
    
    const LOG_FILE = 'jElocky';
    
    private static $levels;
    
    public static function startStep($step) {
        $pid = getmypid();
        
        self::log($pid, 'debug', '> begin ' . $step);
        
        if (array_key_exists($pid, self::$levels))
            self::$levels[$pid] = self::$levels[$pid] + 1;
        else
            self::$levels[$pid] = 1;

    }
    
    /**
     * Add a message in the plugin log
     * @param string $type type du message à mettre dans les log
     * @param string $msg message à mettre dans les logs
     */
    public static function add($type, $msg, $logicalId = '') {
        self::log(getmypid(), $type, $msg, $logicalId);
    }
    
    public static function endStep() {
        $pid = getmypid();

        if (array_key_exists($pid, self::$levels)) {
            self::$levels[$pid] = self::$levels[$pid] - 1;
            if (self::$levels[$pid] == 0)
                unset(self::$levels[$pid]);
        }
        
        self::log($pid, 'debug', '< end ');
    }
    
    private static function log($key, $type, $msg, $logicalId='') {
        
        switch ($type) {
            case 'debug':
            case 'error':
                $keyPrefix = '  ';
                break;
            case 'info':
                $keyPrefix = '   ';
                break;
            default:
                $keyPrefix = '';
        }
                
        if (array_key_exists($key, self::$levels))
            $msgPrefix = str_repeat(' ', self::$levels[$key]*3);
        else
            $msgPrefix = '';
        
        log::add(self::LOG_FILE, $type, $keyPrefix . sprintf('%5d', $key) . '|' . $msgPrefix . $msg, $logicalId);
    }
}