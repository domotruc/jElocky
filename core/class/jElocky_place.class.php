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
            
            // Create command
            $place_eql->getAlarmTriggeredCmd();
            
            jElockyLog::add('info', 'creating place ' . $place_eql->getName());
        }
        
        // Place update (configuration and commands)
        $place_eql->setConfData($place);
        $place_eql->setPlaceCmdData($place);
                       
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
        $this->requestDataAndUpdate(false);
        jElockyLog::endStep();
    }
    
    public function update2() {
        $this->startLogStep(__METHOD__);
        if ($this->getIsEnable()) {
            try {
                foreach ($this->requestObjects() as $objects) {
                    foreach ($objects as $object) {
                        jElockyLog::add('debug', 'treating object ' . $object['name']);
                        $object_eql = jElocky_object::getInstance($object, $this->getId());
                        //FIXME : can return an exception to be treated
                        $object_eql->save();
                    }
//                     switch ($type) {
//                         case 'board':
//                             foreach ($object as $board) {
//                                 jElockyLog::add('debug', 'treating board ' . $board['name']);
//                                 $object_eql = jElocky_object::getInstance($board, $this->getId());
//                             }
//                             break;
//                         case 'passerelle':
//                             foreach ($object as $psrl) {
//                                 jElockyLog::add('debug', 'treating passerelle ' . $psrl['name']);
//                                 $object_eql = jElocky_object::getInstance($board, $this->getId());
//                             }
//                             break;
//                     }
                }
                    
                    //$object_eql = jElocky_object::getInstance($object);
                    //$object_eql->addUser($this->getId(), $object['admin_address'][0]['state'],
                    //    $object['admin_address'][0]['name']);
                    //$object_eql->save();
         
            }
            catch (Exception $e) {
                $this->processElockyException($e->getMessage(), true);
            }
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

    public static function cronHighFreq() {
        jElockyLog::startStep(__METHOD__);
        foreach (self::byType(__CLASS__, true) as $place_eql) {
            $place_eql->requestDataAndUpdate(true);
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
        $users = $this->getConfigurationUsers();
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
     * Return the users configuration parameter for this place
     * @return array users configuration parameter (can be empty)
     */
    private function getConfigurationUsers() {
        $users = $this->getConfiguration('users');
        if (!isset($users))
            $users = array();
        return $users;
    }
    
    public function triggerAlarm() {
        log::add('jElocky', 'info', $this->getName() . ': alarm triggered');
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
    
    public static function resetTriggeredAlarm($_option) {
        /* @var jElocky_place $eql */
        $eql = self::byId($_option['id']);
        $eql->setAlarmTriggeredCmd(0);
    }
    
    /**
     * @param int|string $_value alarm enable status
     * @return jElocky_placeCmd
     */
    private function setAlarmEnableCmd($_value) {
        $cmd = $this->getCmd(null, jElocky_placeCmd::ALARM_ARMED_ID);
        if (!is_object($cmd)) {
            $cmd = new jElocky_placeCmd();
            $cmd->setName(__('armement alarme', __FILE__));
            $cmd->setEqLogic_id($this->getId());
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setLogicalId(jElocky_placeCmd::ALARM_ARMED_ID);
            $cmd->setIsVisible(1);
            $cmd->save();
        }
        $cmd->event($_value);
        /*if ($this->checkAndUpdateCmd($cmd, $_value)) {
            $cmd->setValue($_value);
            $cmd->save();
        }*/
        return $cmd;
    }
    
    /**
     * Request data from the Elocky server and update this place information
     * This place shall be saved after calling this method
     * @param bool $cmd_only whether or not only commands shall be updated
     * @throws \Exception in case of connexion error with the Elocky server
     */
    private function requestDataAndUpdate($cmd_only) {
        jElockyLog::add('info', 'updating' . ($cmd_only ? ' ' : 'data and ') . 'commands of place ' . $this->getName());
        if (($admin = $this->getAdmin()) != null) {
            try {
                $place = $admin->requestPlaces($this->getLogicalId());
                if ($place != null) {
                    if (! $cmd_only)
                        $this->setConfData($place);
                    $this->setPlaceCmdData($place);
                }
                else {
                    jElockyLog::add('warning', 'place ' . $this->getName() . '[id=' . $this->getLogicalId() .
                        '] not found for admin ' . $admin->getName());
                }
            }
            catch (Exception $e) {
                $this->processElockyException($e->getMessage(), true);
            }
        }
        else {
            jElockyLog::add('warning', 'no admin for place ' . $this->getName());
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
    private function setPlaceCmdData($place) {
        $this->setAlarmEnableCmd($place['alarm']);
    }
    
    private function setAlarmTriggeredCmd($_value) {
        $cmd = $this->getAlarmTriggeredCmd();
        if ($this->checkAndUpdateCmd($cmd, $_value)) {
            $cmd->setValue($_value);
            $cmd->save();
        }
    }

    /**
     * Return the alarm triggerred command
     * Create the command if not existing
     * @return jElocky_placeCmd
     */
    private function getAlarmTriggeredCmd() {
        $cmd = $this->getCmd(null, jElocky_placeCmd::ALARM_TRIGGERED_ID);
        if (is_object($cmd))
            return $cmd;
        
        $cmd = new jElocky_placeCmd();
        $cmd->setName(__('déclenchement alarme', __FILE__));
        $cmd->setIsVisible(1);       
        $cmd->setEqLogic_id($this->getId());
        $cmd->setType('info');
        $cmd->setSubType('binary');
        $cmd->setLogicalId(jElocky_placeCmd::ALARM_TRIGGERED_ID);
        $cmd->save();
        return $cmd;
    }
    
    /**
     * Request and return all the object of this place, or the specified one if $object_id is provided
     * @param int $object_id id of the specific object to retrieve, -1 (default) to retrieve all objects
     * @return array|null null if the place is not enabled or the given object_id is not found
     * @throws \Exception in case of communication error with the Elocky server
     */
    public function requestObjects($object_id=-1) {
        jElockyLog::add('debug', 'requesting ' . ($object_id < 0 ? 'object ' . $object_id : ' objects') .
            ' for place ' . $this->getName());
        $objects = $this->getAPI()->requestObjects($this->getAdmin()->getLogicalId(), $this->getLogicalId())['object'];
        if ($object_id < 0)
            return $objects[0];

        foreach ($objects[0] as $key => $object) {
            switch ($key) {
                case 'board':
                    foreach ($object as $board) {
                        if ($board['reference'] == $object_id)
                            return $board;
                    }
                    break;
                case 'passerelle':
                    foreach ($object as $psrl) {
                        if ($psrl['id'] == $object_id)
                            return $psrl;
                    }
                    break;
            }
        }
            
        return null;
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
