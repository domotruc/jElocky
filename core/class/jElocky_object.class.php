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
require_once __DIR__ . '/../../3rparty/vendor/autoload.php';
require_once __DIR__ . '/jElockyUtil.class.php';
require_once __DIR__ . '/jElockyLog.class.php';

require_once 'ElockyAPILogger.trait.php';
require_once 'jElockyEqLogic.trait.php';

use ElockyAPI\User as UserAPI;

/**
 * @author domotruc
 *
 */
class jElocky_object extends eqLogic {
    
    use jElockyEqLogic;
    
    const TYPE_PASSERELLE = 0;
    const TYPE_SERRURE = 1;
    const TYPE_BEACON = 2;
    
    const TYPES = array('passerelle', 'serrure', 'beacon');
    
    private static $_cmds_def_matrix = array(
        // Passerelle
        array(
            'nbObject' => array('id' => 'nbObject', 'stype' => 'numeric'),
            'connectionInternet' => array('id' => 'connectionInternet', 'stype' => 'numeric'),
            'address_ip' => array('id' => 'address_ip', 'stype' => 'string'),
            'connection' => array('id' => 'connection', 'stype' => 'numeric'),
            'state' => array('id' => 'state', 'stype' => 'numeric'),
            'vpn' => array('id' => 'vpn', 'stype' => 'string'),
            'address_ip_local' => array('id' => 'address_ip_local', 'stype' => 'string')
        ),
        // Serrure
        array(
            'nbAccess' => array('id' => 'nbAccess', 'stype' => 'numeric'),
            'battery' => array('id' => 'battery', 'stype' => 'numeric'),
            'version' => array('id' => 'version', 'stype' => 'string'),
            'veille' => array('id' => 'veille', 'stype' => 'numeric'),
            'connection' => array('id' => 'connection', 'stype' => 'numeric'),
            'programme' => array('id' => 'programme', 'stype' => 'numeric'),
            'tension' => array('id' => 'tension', 'stype' => 'numeric'),
            'maj' => array('id' => 'maj', 'stype' => 'numeric'),
            'reveille' => array('id' => 'reveille', 'stype' => 'numeric'),
            'date_battery' => array('id' => 'date_battery', 'stype' => 'string')
        )
    );
    
    /**
     * Create a new object or update it if already existing
     * Place is not saved
     * @param array $object object data as returned by https://elocky.com/fr/doc-api-test#liste-objets
     * @param array $place_id jeedom id of the place where is located the object
     * @return jElocky_object
     */
    public static function getInstance($object, $place_id) {
        jElockyLog::startStep(__METHOD__);
        
        $type = key_exists('type_board', $object) ? $object['type_board']['id'] : self::TYPE_PASSERELLE;
        $id = $type == self::TYPE_PASSERELLE ? $object['id'] : $object['reference'];
        
        /* @var jElocky_place $place_eql*/
        $object_eql = self::byLogicalId($id, self::class);
        
        // Object creation if necessary
        if (!is_object($object_eql)) {
            $object_eql = new jElocky_object();
            $object_eql->setName($object['name']);
            $object_eql->setEqType_name(__CLASS__);
            $object_eql->setLogicalId($id);
            $object_eql->setIsEnable(1);
            $object_eql->setConfiguration('type', $type);
            $object_eql->setConfiguration('place_id', $place_id);
            
            // Save the place directly: required before creating command
            $object_eql->save(true);
                       
            jElockyLog::add('info', 'creating object ' . $object_eql->getName() . ' of type ' . self::TYPES[$type]);
        }
        
        // Place update (configuration and commands)
        $object_eql->setConfData($object);
        $object_eql->setCmdData(self::$_cmds_def_matrix[$type], $object);
        
        jElockyLog::endStep();
        
        return $object_eql;
    }
    
    /**
     * Update this object configuration parameters with the information retrieved from the Elocky server
     * Related commands are not updated (see setCmdData)
     * @param array $object
     */
    private function setConfData($object) {
        $this->setMultipleConfiguration(array('reference'), $object);
        if ($this->getConfiguration('type') != self::TYPE_PASSERELLE) {
           $this->setConfiguration('passerelle', key_exists('passerelle') ? $object['passerelle']['id'] : '');
        }
    }
    
}

class jElocky_objectCmd extends cmd {
    public function execute($_options = array()) {
        if ($this->getType() == 'info') {
            return $this->getValue();
        }
    }
}