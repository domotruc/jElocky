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
    
    // Object type (as per Elocky API)
    const ID_PASSERELLE = 0;
    const ID_SERRURE = 1;
    const ID_BEACON = 2;
    
    // Data keys returned by the Elocky API
    const KEY_BOARD = 'board';
    const KEY_PASSERELLE = 'passerelle';
    const KEY_TYPE_BOARD = 'type_board';
    const KEY_OBJECT_ID = 'reference';
    
    const TYP_PASSERELLE = 'passerelle';
    const TYP_SERRURE = 'serrure';
    const TYP_BEACON = 'beacon';
    const TYPES = array(self::TYP_PASSERELLE, self::TYP_SERRURE, self::TYP_BEACON);
    
    private static $_cmds_def_matrix = array(
        // Passerelle
        array(
            'nbObject' => array('id' => 'nbObject', 'stype' => 'numeric', 'historizeMode' => 'none'),
            'connectionInternet' => array('id' => 'connectionInternet', 'stype' => 'binary', 'historizeMode' => 'none'),
            'address_ip' => array('id' => 'address_ip', 'stype' => 'string'),
            'state' => array('id' => 'state', 'stype' => 'binary', 'historizeMode' => 'none'),
            'vpn' => array('id' => 'vpn', 'stype' => 'string'),
            'address_ip_local' => array('id' => 'address_ip_local', 'stype' => 'string')
        ),
        // Serrure
        array(
            'nbAccess' => array('id' => 'nbAccess', 'stype' => 'numeric', 'historizeMode' => 'none'),
            'battery' => array('id' => 'battery', 'stype' => 'numeric', 'unit' => '%'),
            'version' => array('id' => 'version', 'stype' => 'string'),
            'veille' => array('id' => 'veille', 'stype' => 'numeric', 'historizeMode' => 'none'),
            'connection' => array('id' => 'connection', 'stype' => 'numeric', 'historizeMode' => 'none'),
            'programme' => array('id' => 'programme', 'stype' => 'numeric', 'historizeMode' => 'none'),
            'tension' => array('id' => 'tension', 'stype' => 'numeric', 'process' => array(self::class, 'convertVoltage'), 'unit' => 'V'),
            'maj' => array('id' => 'maj', 'stype' => 'numeric', 'historizeMode' => 'none'),
            'reveille' => array('id' => 'reveille', 'stype' => 'numeric', 'historizeMode' => 'none'),
            'date_battery' => array('id' => 'date_battery', 'stype' => 'string')
        )
    );
    
    /**
     * Create a new object belonging to the given place or return it if already existing.
     * If created, the object is saved.
     * If the given place is locked, null is return
     * @param array $object object data as returned by https://elocky.com/fr/doc-api-test#liste-objets
     * @param array $place_id jeedom id of the place where is located the object
     * @return jElocky_object|null return null if 
     */
    public static function getInstance($object, $place_id) {
        
        /* @var jElocky_object $object_eql*/
        $object_eql = self::byLogicalId($object[self::KEY_OBJECT_ID], self::class);
        
        // Object creation if necessary
        if (is_object($object_eql)) {
            jElockyLog::add('debug', 'object ' . $object_eql->getName() . ' exists (id=' . $object_eql->getId() . ')');
            $is_created = false;
        }
        else {
            $type = self::TYPES[$object[self::KEY_TYPE_BOARD]];
            $object_eql = new jElocky_object();
            $object_eql->setName($object['name']);
            $object_eql->setEqType_name(__CLASS__);
            $object_eql->setLogicalId($object[self::KEY_OBJECT_ID]);
            $object_eql->setIsEnable(1);
            $object_eql->setConfiguration(self::KEY_TYPE_BOARD, $type);
            $object_eql->setConfiguration('place_id', $place_id);

            if ($type == self::TYP_SERRURE) {
                $object_eql->setConfiguration('battery_type', '1x3.6V ER14250');
            }
            
            jElockyLog::add('info', 'creation object ' . $object_eql->getName() . ' de type ' . $type);
            $is_created = true;
        }

        if (jElocky_place::getIsLockedById($place_id))
            return null;

        if ($is_created) {
            // Save the created object
            $object_eql->save(true);
            
            // Inform the UI that a place has been added
            event::add('jElocky::insert', array('eqlogic_type' => $object_eql->getEqType_name(), 'eqlogic_name' => $object_eql->getName()));
        }
        
        return $object_eql;
    }
    
    /**
     * Update this object information
     * @var boolean $to_save whether or not this object shall be saved after update (true by default)
     * @throws \Exception in case of connexion error with the Elocky server
     */
    public function update1($to_save=true) {
        $this->startLogStep(__METHOD__);
        
        if ($this->getIsEnable()) {
            /* @var jElocky_place $place_eql*/
            $place_eql = jElocky_place::byId($this->getConfiguration('place_id'));
            if (($object = $place_eql->requestObjects($this->getLogicalId())) != null) {
                $this->updateConfiguration($object);
                $this->updateCommands($object);
                if ($to_save)
                    $this->save(true);
            }
            else
                jElockyLog::add('warning', "aucun lieu actif trouvé pour mettre à jour l'objet " . $this->getName());
        }
        
        jElockyLog::endStep();
    }

    // Shall be kept
    public function update2() {}
        
    /**
     * Called on object removal
     * Log a message
     */
    public function preRemove() {
        $this->startLogStep(__METHOD__);
        jElockyLog::add('info', 'suppression objet ' . $this->getName() . ' (id=' . $this->getId() . ')');
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
    
    /**
     * Converts battery voltage from mV to V
     * @param number $val tension
     * @return number
     */
    private static function convertVoltage($tension) {
        return round($tension/1000, 3);
    }
}

class jElocky_objectCmd extends cmd {
    public function execute($_options = array()) {
        if ($this->getType() == 'info') {
            return $this->getValue();
        }
    }
}