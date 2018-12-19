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
    
    // Object type
    const ID_PASSERELLE = 0;
    const ID_SERRURE = 1;
    const ID_BEACON = 2;
    
    const KEY_BOARD = 'board';
    const KEY_PASSERELLE = 'passerelle';
    const KEY_TYPE_BOARD = 'type_board';
    const KEY_OBJECT_ID = 'reference';
    
    const TYPES = array('passerelle', 'serrure', 'beacon');
    
    private static $_cmds_def_matrix = array(
        // Passerelle
        array(
            'nbObject' => array('id' => 'nbObject', 'stype' => 'numeric'),
            'connectionInternet' => array('id' => 'connectionInternet', 'stype' => 'numeric'),
            'address_ip' => array('id' => 'address_ip', 'stype' => 'string'),
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
     * Create a new object or return it if already existing.
     * If created, the object is saved.
     * @param array $object object data as returned by https://elocky.com/fr/doc-api-test#liste-objets
     * @param array $place_id jeedom id of the place where is located the object
     * @return jElocky_object
     */
    public static function getInstance($object, $place_id) {
        
        /* @var jElocky_place $place_eql*/
        $object_eql = self::byLogicalId($object[self::KEY_OBJECT_ID], self::class);
        
        // Object creation if necessary
        if (is_object($object_eql)) {
            jElockyLog::add('debug', 'object ' . $object_eql->getName() . ' exists');
        }
        else {
            $object_eql = new jElocky_object();
            $object_eql->setName($object['name']);
            $object_eql->setEqType_name(__CLASS__);
            $object_eql->setLogicalId($object[self::KEY_OBJECT_ID]);
            $object_eql->setIsEnable(1);
            $object_eql->setConfiguration(self::KEY_TYPE_BOARD, self::TYPES[$object[self::KEY_TYPE_BOARD]]);
            $object_eql->setConfiguration('place_id', $place_id);
            
            // Save the place directly: required before creating command
            $object_eql->save(true);
                       
            jElockyLog::add('info', 'creating object ' . $object_eql->getName() . ' of type ' . self::TYPES[$type]);
        }
         
        return $object_eql;
    }
    
    /**
     * Update this object information
     * This place shall be saved after calling this method
     * @throws \Exception in case of connexion error with the Elocky server
     */
    public function update1() {
        $this->startLogStep(__METHOD__);
        
        if ($this->getIsEnable()) {
            /* @var jElocky_place $place_eql*/
            $place_eql = jElocky_place::byId($this->getConfiguration('place_id'));
            $object = $place_eql->requestObjects($this->getLogicalId());
            $this->updateConfiguration($object);
            $this->updateCommands($object);
        }
        
        jElockyLog::endStep();
    }
    
    /**
     * Return the photo pathname of this jElocky_object
     * @return string full filename on the jeedom server
     */
    public function getPhoto() {
        return 'plugins/jElocky/resources/' . $this->getConfiguration(self::KEY_TYPE_BOARD) . '.png';
    }
    
    /**
     * Update this object configuration parameters with the information retrieved from the Elocky server.
     * This is the responsability of the caller to save the object.
     * Related commands are not updated (see updateCommands)
     * @param array $object object data array retrieved from the Elocky server
     */
    public function updateConfiguration($object) {
        if ($this->getIsEnable()) {
            $keys = array('reference');
            if (array_key_exists('id', $object))
                $keys[] = 'id';            
            $this->setMultipleConfiguration($keys, $object);
        }
        //jElockyLog::add('debug', 'place ' . $place_eql->getName() . ' is disabled');
    }
    
    /**
     * Update this object commands with the information retrieved from the Elocky server.
     * @param array $object object data array retrieved from the Elocky server
     */
    public function updateCommands($object) {
        if ($this->getIsEnable()) {
            $this->setCmdData(self::$_cmds_def_matrix[$object[self::KEY_TYPE_BOARD]], $object);
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