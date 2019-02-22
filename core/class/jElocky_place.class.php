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
        'alarm' => array('id' => jElocky_placeCmd::ALARM_ARMED_ID, 'stype' => 'binary', 'historizeMode' => 'none')
    );
    
    /**
     * Create a new place or return it if already existing.
     * If created, the object is saved, otherwise not.
     * @param array $place place description as returned by https://elocky.com/fr/doc-api-test#liste-lieu
     * @return jElocky_place
     */
    public static function getInstance($place) {
        jElockyLog::startStep(__METHOD__);
        
        /* @var jElocky_place $place_eql*/
        $place_eql = self::byLogicalId($place['id'], self::class);
        
        // Place creation if necessary
        if (is_object($place_eql)) {
            jElockyLog::add('debug', 'place ' . $place_eql->getName() . ' exists (id=' . $place_eql->getId() . ')');
        }
        else {
            $place_eql = new jElocky_place();
            $place_eql->setName($place['admin_address'][0]['name']);
            $place_eql->setEqType_name(__CLASS__);
            $place_eql->setLogicalId($place['id']);
            $place_eql->setIsEnable(1);
            jElockyLog::add('info', 'création lieu ' . $place_eql->getName());
            
            // Save the place directly: required before creating command
            $place_eql->save(true);
         	
            // Inform the UI that a place has been added
            event::add('jElocky::insert', array('eqlogic_type' => $place_eql->getEqType_name(), 'eqlogic_name' => $place_eql->getName()));

            // Create the alarm triggered command which can be only set through IFTTT
            $place_eql->setCmdData(
                array(
                    jElocky_placeCmd::ALARM_TRIGGERED_ID => array('id' => jElocky_placeCmd::ALARM_TRIGGERED_ID,
                        'stype' => 'binary', 'historizeMode' => 'none')), array(jElocky_placeCmd::ALARM_TRIGGERED_ID => 0));
        }
                                      
        jElockyLog::endStep();
        
        return $place_eql;
    }
    
    /**
     * Update this place information.
     * @var boolean $to_save whether or not this place shall be saved after update (true by default)
     * @throws \Exception in case of connexion error with the Elocky server
     */
    public function update1($to_save=true) {
        $this->startLogStep(__METHOD__);
        if ($this->getIsEnable()) {
            jElocky::pickLock();
            $this->requestPlaceAndUpdate(true, $to_save);
            jElocky::releaseLock();            
        }
        jElockyLog::endStep();
    }
    
    /**
     * Update this place related information (objects, photo)
     * @throws \Exception in case of connexion error with the Elocky server
     */
    public function update2() {
        $this->startLogStep(__METHOD__);
        if ($this->getIsEnable()) {
            jElocky::pickLock();
            $this->requestObjectsAndUpdate(true);
            $this->updatePhoto('requestPlacePhoto');
            jElocky::releaseLock();            
        }
        jElockyLog::endStep();
    }
    
    /**
     * Called before place removal
     * Pick jElocky lock
     * Removes attached objects
     */
    public function preRemove() {
        $this->startLogStep(__METHOD__);
        jElocky::pickLock();
        jElockyLog::add('info', 'suppression lieu ' . $this->getName() . ' (id=' . $this->getId() . ')');
        $objects = $this->getObjects();
        foreach ($objects as $object) {
            $object->remove();
        }          
        jElockyLog::endStep();
    }
    
    /**
     * Update data that shall be updated frequently
     * (only commands are updated)
     */
    public static function cronHighFreq() {
        jElockyLog::startStep(__METHOD__);
        jElocky::pickLock();
        foreach (self::byType(__CLASS__, true) as $place_eql) {
            if ($place_eql->getIsEnable()) {
                $place_eql->requestPlaceAndUpdate(false);
                $place_eql->requestObjectsAndUpdate(false);
            }
        }
        jElocky::releaseLock();
        jElockyLog::endStep();
    }
    
    /**
     * Update this place configuration parameters with the information retrieved from the Elocky server.
     * This is the responsability of the caller to save the place.
     * Related commands are not updated (see updateCommands)
     * @param array $place object data array retrieved from the Elocky server
     */
    public function updateConfiguration($place) {
        if ($this->getIsEnable()) {
            $this->setMultipleConfiguration(array('address', 'zip_code', 'city', 'photo', 'country'), $place);
        }
    }
    
    /**
     * Update this place commands with the information retrieved from the Elocky server.
     * @param array $place object data array retrieved from the Elocky server
     */
    public function updateCommands($place) {
        if ($this->getIsEnable()) {
            $this->setCmdData(self::$_cmds_def_matrix, $place);
        }
    }
    
    /**
     * Add or update a user of this place
     * Update is not saved
     * @param string $logicId user eqLogic id
     * @param string $state user state (0 = administrateur, 1 = modérateur, 2 = utilisateur, 3 = invité)
     * @param string $name name given by the user to this place
     * @param boolean
     */
    public function addUser($id, $state, $name) {
        $users = $this->getConfiguration('users', array());
        $key = jElockyUtil::array_search_ref($users, $id);
        $conf = array('ref' => $id, 'state' => $state, 'name' => $name);
        if ($key === false) {
            jElockyLog::add('info', "l'utilisateur " . $id . " est ajouté au lieu " . $this->getName());
            $users[] = $conf;
        }
        else
            $users[$key] = $conf;
        
        $this->setConfiguration('users', $users);
        
        if ($state == self::RIGHT_ADMIN)
            $this->setConfiguration('admin', $id);
    }
    
    /**
     * Remove a user from this place
     * Update is not saved
     * @param string $logicId user eqLogic id
     * @param boolean
     */
    public function removeUser($id) {
        jElockyLog::add('info', "l'utilisateur " . $id . " est supprimé du lieu " . $this->getName());
        $users = $this->getConfiguration('users', array());
        $key = jElockyUtil::array_search_ref($users, $id);
        if ($key !== false) {
            unset($users[$key]);
            $this->setConfiguration('users', $users);
        }

        if ($this->getConfiguration('admin') == $id)
            $this->setConfiguration('admin', null);
    }
    
    /**
     * Return whether or not this place has at least 1 user
     * @return boolean
     */
    public function hasUser() {
        $users = $this->getConfiguration('users', array());
        return count($users) > 0 ? true : false;
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
     * Get an enable user for this place
     * A warning is logged if no user is found
     * @return jElocky_user|null
     */
    private function getUser() {
        $users = $this->getConfiguration('users', array());
        foreach ($users as $u) {
            /* @var jElocky_user $eqU */
            $eqU = eqLogic::byId($u['ref']);
            if (is_object($eqU) && $eqU->getIsEnable()) {
                return $eqU;
            }
        }
        jElockyLog::add('warning', "aucun utilisateur actif trouvé pour mettre à jour le lieu " . $this->getName());
        return null;
    }
    
    /**
     * Get the administrator of this place
     * A warning is logged if no administrator is found
     *
     * @return null|jElocky_user null if administrator is unknown or disabled (a warning message is log if null)
     */
    private function getAdmin() {
        if (($admin_id = $this->getConfiguration('admin', '')) != '') {
            /* @var jElocky_user $eqU */
            $eqU = eqLogic::byId($admin_id);
            if (is_object($eqU) && $eqU->getIsEnable()) {
                return $eqU;
            }
        }
        jElockyLog::add('warning', "aucun administrateur actif trouvé pour mettre à jour le lieu " . $this->getName());
        return null;
    }
    
    /**
     * Return the elocky API object of this place  
     * @return null|UserAPI null if no user is enabled (a warning message is log if null)
     */
    private function getAPI() {
        $user = $this->getUser();
        return isset($user) ? $user->getAPI() : null;
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
        // Actual delay between 30s and 
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
     *    Return an array of objects (an empty array if this place does not have objects)
     * @param int $object_id id of the specific object to retrieve, -1 (default) to retrieve all objects
     * @return array|null
     *     null if this place is not enabled or the given object_id is not found (when positive)
     *     empty array if this place does not contain any objects
     * @throws \Exception in case of communication error with the Elocky server
     */
    public function requestObjects($object_id=-1) {
        jElockyLog::add('debug', 'requesting ' . ($object_id < 0 ? 'all objects' : 'object ' . $object_id) .
            ' for place ' . $this->getName());

        $objs = null;
        if (($user = $this->getUser()) != null && ($api = $user->getAPI()) != null) {
            $answer = $api->requestObjects($user->getLogicalId(), $this->getLogicalId())['object'];
            
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
        }
        
        return $objs;
    }
    
    /**
     * Request data from the Elocky server and update this place information.
     *
     * @param bool $does_update_conf
     *            whether or not place configuration parameters shall be updated (if not, only commands
     *            are updated)
     * @param bool $to_save whether or not this place shall be saved
     * @throws \Exception in case of connexion error with the Elocky server
     */
    private function requestPlaceAndUpdate($does_update_conf, $to_save=true) {
        jElockyLog::add('info', 'updating ' . (($does_update_conf ? 'configuration and ' : '')) . 'commands of place ' . $this->getName());
        if (($user = $this->getUser()) != null) {
            /** @var jElocky_user $user */
            try {
                $place = $user->requestPlaces($this->getLogicalId());
                if ($place != null) {
                    if ($does_update_conf) {
                        $this->updateConfiguration($place);
                        if ($to_save)
                            $this->save();
                    }
                    $this->updateCommands($place);
                }
                else {
                    jElockyLog::add('warning',
                        'lieu ' . $this->getName() . '[id=' . $this->getLogicalId() . "] avec l'utilisateur " . $user->getName());
                }
            } catch (Exception $e) {
                $this->processElockyException($e->getMessage(), true);
            }
        }
    }
    
    /**
     * Request objects from the Elocky server and update objects of this place.
     *
     * @param bool $does_update_conf
     *            whether or not object configuration parameters shall be updated (if not, only commands
     *            are updated)
     * @throws \Exception in case of connexion error with the Elocky server
     */
    public function requestObjectsAndUpdate($does_update_conf) {
        jElockyLog::add('info', 'updating ' . ($does_update_conf ? 'configuration and ' : '') . 'commands of all objects of place ' . $this->getName());
        try {
            if (($objects = $this->requestObjects()) !== null) {
                foreach ($objects as $object) {
                    jElockyLog::add('debug', 'treating object ' . $object['name']);
                    $object_eql = jElocky_object::getInstance($object);
                    if ($object_eql != null) {
                        $object_eql->updateCommands($object);
                        $is_updated = $object_eql->setPlace($this->getId());
                        if ($is_updated || $does_update_conf) {
                            $object_eql->updateConfiguration($object);
                            $object_eql->save();
                        }
                    }
                    else {
                        jElockyLog::add('info', 'nothing done as related place ' . $this->getId() . ' is locked');
                    }
                }
            }
            else
                jElockyLog::add('warning', "le lieu " . $this->getName() . " est inactif : impossible de mettre à jour ses objets");
        }
        catch (Exception $e) {
            $this->processElockyException($e->getMessage(), true);
        }
    }
       
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
