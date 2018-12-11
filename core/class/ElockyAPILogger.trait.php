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
require_once __DIR__ . '/../../3rparty/vendor/autoload.php';
require_once __DIR__ . '/jElockyLog.class.php';

use Psr\Log\LogLevel;

/**
 * Logger to be passed to the Elocky API to retreive messages logged by this API.
 * Implements the Psr\Log\LoggerInterface interface
 * @author domotruc
 */
trait ElockyAPILogger {
    
    use Psr\Log\LoggerTrait;

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array()) {
        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                $type = 'error';
                break;
            case LogLevel::WARNING:
                $type = 'warning';
                break;
            case LogLevel::NOTICE:
            case LogLevel::INFO:
                $type = 'info';
                break;
            default:
                $type = 'debug';
        }
        
        jElockyLog::add($type, 'ElockyAPI::' . $message);
    }
}
