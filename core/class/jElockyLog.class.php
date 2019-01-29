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
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class jElockyLog {
    
    const LOG_FILE = 'jElocky';
    
    /**
     * @var int $log_level plugin log level. Do not use directly => call getLogLevel.
     */
    private static $log_level;
    
    /**
     * @var array[int] $indentation_level indentation level by pid
     */
    private static $indentation_level;
    
    public static function startStep($step) {
        $pid = getmypid();
        
        self::log($pid, 'debug', '> begin ' . $step);
        
        if (array_key_exists($pid, self::$indentation_level))
            self::$indentation_level[$pid] = self::$indentation_level[$pid] + 1;
        else
            self::$indentation_level[$pid] = 1;

    }
    
    /**
     * Add a message in the plugin log
     * @param string $type type du message à mettre dans les log
     * @param string $msg message à mettre dans les logs
     */
    public static function add($type, $msg, $logicalId = '') {
        self::log(getmypid(), $type, $msg, $logicalId);
    }
    
    /**
     * Add an error message and throw an exception
     * @param string $msg
     * @throw exception
     */
    public static function addException($msg) {
        self::log(getmypid(), 'error', $msg);
        throw new Exception($msg);
    }
    
    public static function endStep() {
        $pid = getmypid();

        if (array_key_exists($pid, self::$indentation_level)) {
            self::$indentation_level[$pid] = self::$indentation_level[$pid] - 1;
            if (self::$indentation_level[$pid] == 0)
                unset(self::$indentation_level[$pid]);
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
                        
        if (array_key_exists($key, self::$indentation_level))
            $msgPrefix = str_repeat(' ', self::$indentation_level[$key]*3);
        else
            $msgPrefix = '';
        
        if (self::getLogLevel() == 'debug')        
            log::add(self::LOG_FILE, $type, $keyPrefix . sprintf('%5d', $key) . '|' . $msgPrefix . $msg, $logicalId);
        else
            log::add(self::LOG_FILE, $type, $msg, $logicalId);
    }
    
    private static function getLogLevel() {
        if (! isset(self::$log_level)) {
            self::$log_level = log::convertLogLevel(log::getLogLevel(self::LOG_FILE));
            self::add('debug', 'get plugin log level: ' . self::$log_level);
        }
        return self::$log_level;
    }
}