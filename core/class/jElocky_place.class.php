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
require_once __DIR__ . '/jElocky_object.class.php';
require_once __DIR__ . '/jElockyUtil.class.php';
require_once __DIR__ . '/jElockyLog.class.php';
require_once __DIR__ . '/../../3rparty/vendor/autoload.php';

require_once 'ElockyAPILogger.trait.php';
require_once 'jElockyEqLogic.trait.php';

use ElockyAPI\User as UserAPI;

/**
 * @author domotruc
 *
 */
class jElocky_place extends eqLogic {
    
    use jElockyEqLogic;
   
    const RIGHT_ADMIN = 0;
    const RIGHT_MOD = 1;
    const RIGHT_USER = 2;
    const RIGHT_INVITEE = 3;
    
    private static $_cmds_def_matrix = array(
        'alarm' => array('id' => jElocky_placeCmd::ALARM_ARMED_ID, 'stype' => 'binary')
    );
    
    /**
     * Create a new place or update it if already existing
     * Place is not saved
     * @param array $place place description as returned by https://elocky.com/fr/doc-api-test#liste-lieu
     * @return jElocky_place
     */
    public static function getInstance($place) {
        jElockyLog::startStep(__METHOD__);
        
        /* @var jElocky_place $place_eql*/
        $place_eql = self::byLogicalId($place['id'], self::class);
        
        // Place creation if necessary
        if (!is_object($place_eql)) {
            $place_eql = new jElocky_place();
            $place_eql->setName($place['admin_address'][0]['name']);
            $place_eql->setEqType_name(__CLASS__);
            $place_eql->setLogicalId($place['id']);
            $place_eql->setIsEnable(1);
            
            // Save the place directly: required before creating command
            $place_eql->save(true);

            // Create the alarm triggered command which can be only set through IFTTT
            $place_eql->setCmdData(
                array(
                    jElocky_placeCmd::ALARM_TRIGGERED_ID => array('id' => jElocky_placeCmd::ALARM_TRIGGERED_ID,
                        'stype' => 'binary')), array(jElocky_placeCmd::ALARM_TRIGGERED_ID => 0));
            
            jElockyLog::add('info', 'creating place ' . $place_eql->getName());
        }
        
        if ($place_eql->getIsEnable()) {
            // Place update (configuration and commands)
            $place_eql->setConfData($place);
            $place_eql->setCmdData(self::$_cmds_def_matrix, $place);
        }
        else {
            jElockyLog::add('debug', 'place ' . $place_eql->getName() . ' is disabled');
        }
                       
        jElockyLog::endStep();
        
        return $place_eql;
    }
    
    /**
     * Update this place information
     * This place shall be saved after calling this method
     * @throws \Exception in case of connexion error with the Elocky server
     */
    public function update1() {
        $this->startLogStep(__METHOD__);
        if ($this->getIsEnable()) {
            $this->requestPlaceAndUpdate(false);
        }
        jElockyLog::endStep();
    }
    
    public function update2() {
        $this->startLogStep(__METHOD__);
        if ($this->getIsEnable()) {
            $this->requestObjectsAndUpdate(false);
        }
        jElockyLog::endStep();
    }
    
    public function preSave() {
        $this->startLogStep(__METHOD__);
        jElockyLog::endStep();
    }

    public function postSave() {
        $this->startLogStep(__METHOD__);
        $this->updatePhoto('requestPlacePhoto');
        jElockyLog::endStep();
    }

    /**
     * Update data that shall be updated frequently
     * (only commands are updated)
     */
    public static function cronHighFreq() {
        jElockyLog::startStep(__METHOD__);
        foreach (self::byType(__CLASS__, true) as $place_eql) {
            $place_eql->requestPlaceAndUpdate(true);
            $place_eql->requestObjectsAndUpdate(true);
        }
        jElockyLog::endStep();
    }
    
    /**
     * Add or update a user of this place
     * Update is not saved
     * @param string $_logicId user eqLogic id
     * @param string $_state user state (0 = administrateur, 1 = modérateur, 2 = utilisateur, 3 = invité)
     * @param string $_name name given by the user to this place
     * @param boolean
     */
    public function addUser($id, $state, $name) {
        $users = $this->getConfiguration('users', array());
        $key = jElockyUtil::array_search_ref($users, $id);
        $conf = array('ref' => $id, 'state' => $state, 'name' => $name);
        if ($key === false)
            $users[] = $conf;
        else
            $users[$key] = $conf;
        
        $this->setConfiguration('users', $users);
        
        if ($state == self::RIGHT_ADMIN)
            $this->setConfiguration('admin', $id);
    }
    
    /**
     * Return objects of this place
     * @return array[jElocky_object] array of the objects of this place
     */
    public function getObjects() {
        $conf = json_encode(array('place_id' => $this->getId()));
        return self::byTypeAndSearhConfiguration(jElocky_object::class, substr($conf, 1, -1));
    }
    
    /**
     * Get the administrator of this place
     * @return null|jElocky_user
     */
    public function getAdmin() {
        $admin_id = $this->getConfiguration('admin', '');
        return $admin_id == '' ? null : eqLogic::byId($admin_id);
    }
    
    /**
     * Return the elocky API object of this place  
     * @return null|UserAPI null if the admin is unknown or disabled
     */
    public function getAPI() {
        $admin = $this->getAdmin();
        return isset($admin) ? $admin->getAPI() : null;
    }
  
    /**
     * Trigger the alarm of this place.
     * Schedule a new cron to reset the alarm within 1 min at the latest 
     */
    public function triggerAlarm() {
        $this->setAlarmTriggeredCmd(1);
        $cron = new cron();
        $cron->setClass(__CLASS__);
        $cron->setFunction('resetTriggeredAlarm');
        $cron->setOption(array('id' => $this->getId()));
        // Add 150s => actual delay between 2 and 3min
        $cron->setSchedule(cron::convertDateToCron(strtotime('now')));
        $cron->setOnce(1);
        $cron->save();
    }
    
    /**
     * Reset the alarm of the place which is provided in $option['id'] (eqLogic id)
     * @param array $option
     */
    public static function resetTriggeredAlarm($option) {
        /* @var jElocky_place $eql */
        $eql = self::byId($option['id']);
        $eql->setAlarmTriggeredCmd(0);
    }

    /**
     * Request and return all the object of this place, or the specified one if $object_id is provided.
     *
     * If $object_id < 0:
     *    Return an array of objects
     * @param int $object_id id of the specific object to retrieve, -1 (default) to retrieve all objects
     * @return array|null null if the place is not enabled or the given object_id is not found
     * @throws \Exception in case of communication error with the Elocky server
     */
    public function requestObjects($object_id=-1) {
        jElockyLog::add('debug', 'requesting ' . ($object_id < 0 ? 'object ' . $object_id : ' objects') .
            ' for place ' . $this->getName());
        $answer = $this->getAPI()->requestObjects($this->getAdmin()->getLogicalId(), $this->getLogicalId())['object'];
        
        $objs = array();
        foreach ($answer[0] as $type => $objects) {
            foreach ($objects as $object) {
                // Set object type
                switch ($type) {
                    case jElocky_object::KEY_BOARD:
                        $object[jElocky_object::KEY_TYPE_BOARD] = $object[jElocky_object::KEY_TYPE_BOARD]['id'];
                        break;
                    case jElocky_object::KEY_PASSERELLE:
                        $object[jElocky_object::KEY_TYPE_BOARD] = jElocky_object::ID_PASSERELLE;
                        break;
                }
                
                if ($object_id == $object[jElocky_object::KEY_OBJECT_ID])
                    return $object;
                    else
                        $objs[] = $object;
            }
        }
        
        return $objs;
    }
    
    /**
     * Request data from the Elocky server and update this place information.
     * This place shall be saved after calling this method if $cmd_only is false
     *
     * @param bool $cmd_only
     *            whether or not only commands shall be updated
     * @throws \Exception in case of connexion error with the Elocky server
     */
    private function requestPlaceAndUpdate($cmd_only) {
        jElockyLog::add('info', 'updating ' . ($cmd_only ? '' : 'configuration and ') . 'commands of place ' . $this->getName());
        if (($admin = $this->getAdmin()) != null) {
            try {
                $place = $admin->requestPlaces($this->getLogicalId());
                if ($place != null) {
                    if (! $cmd_only) {
                        $this->setConfData($place);
                    }
                    $this->setCmdData(self::$_cmds_def_matrix, $place);
                }
                else {
                    jElockyLog::add('warning',
                        'lieu ' . $this->getName() . '[id=' . $this->getLogicalId() .
                        "] non trouvé, ou l'administrateur " . $admin->getName() . " est désactivé");
                }
            } catch (Exception $e) {
                $this->processElockyException($e->getMessage(), true);
            }
        }
        else {
            jElockyLog::add('warning', 'no admin for place ' . $this->getName());
        }
    }
    
    /**
     * Request objects from the Elocky server and update objects of this place.
     *
     * @param bool $cmd_only
     *            whether or not only object commands shall be updated
     * @throws \Exception in case of connexion error with the Elocky server
     */
    private function requestObjectsAndUpdate($cmd_only) {
        jElockyLog::add('info', 'updating ' . ($cmd_only ? '' : 'configuration and ') . 'commands of all objects of place ' . $this->getName());
        try {
            $objects = $this->requestObjects();
            foreach ($objects as $object) {
                jElockyLog::add('debug', 'treating object ' . $object['name']);
                $object_eql = jElocky_object::getInstance($object, $this->getId());
                $object_eql->updateCommands($object);
                if (! $cmd_only) {
                    $object_eql->updateConfiguration($object);
                    $object_eql->save();
                }
            }
        }
        catch (Exception $e) {
            $this->processElockyException($e->getMessage(), true);
        }
    }
    
    /**
     * Update this place configuration parameters with the information retrieved from the Elocky server
     * Related commands are not updated (see setPlaceCmdData
     * @param array $place
     */
    private function setConfData($place) {
        $this->setMultipleConfiguration(array('address', 'zip_code', 'city', 'photo', 'country'), $place);
    }
    
    /**
     * Update this place command data with the information retrieved from the Elocky server
     * @param array $place
     */
//     private function setCmdData($place) {
//         $this->setAlarmEnableCmd($place['alarm']);
//     }
    
    /**
     * Trigger or reset the alarm
     * @param int|string $value alarm enable status
     * @return jElocky_placeCmd
     */
    private function setAlarmTriggeredCmd($value) {
        $cmd = $this->getCmd(null, jElocky_placeCmd::ALARM_TRIGGERED_ID);
        if (is_object($cmd)) {
            if ($this->checkAndUpdateCmd($cmd, $value)) {
                $cmd->setValue($value);
                $cmd->save();
            }
            jElockyLog::add('info', '->' . $this->getName() . '|' . $cmd->getName() . ': ' . $value);
        }
        else {
            jElockyLog::add('warning',
                'commande ' . jElocky_placeCmd::ALARM_TRIGGERED_ID . ' non trouvée pour la place ' . $this->getName());
            return;
        }
    }
}

class jElocky_placeCmd extends cmd {
    
    const ALARM_ARMED_ID = 'alarm_armed';
    const ALARM_TRIGGERED_ID = 'alarm_triggered';
    
    public function execute($_options = array()) {
        if ($this->getType() == 'info') {
            return $this->getValue();
        }
    }
}
