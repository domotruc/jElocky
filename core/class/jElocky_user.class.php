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
require_once __DIR__ . '/jElocky.class.php';
require_once __DIR__ . '/jElocky_place.class.php';
require_once __DIR__ . '/jElockyUtil.class.php';
require_once __DIR__ . '/jElockyLog.class.php';
require_once __DIR__ . '/../../3rparty/vendor/autoload.php';

require_once 'ElockyAPILogger.trait.php';
require_once 'jElockyEqLogic.trait.php';

use Psr\Log\LoggerInterface;
use ElockyAPI\User as UserAPI;

class jElocky_user extends eqLogic implements LoggerInterface {

    // The following trait implements the LoggerInterface
    use ElockyAPILogger;
    
    use jElockyEqLogic;
    
    /**
     * @var boolean
     */
    private $_to_update = false;
    
    private $_api;

    public function preInsert() {
        $this->startLogStep(__METHOD__);
        jElockyLog::endStep();
    }

    public function postInsert() {
        $this->startLogStep(__METHOD__);
        jElockyLog::endStep();
    }

    public function preSave() {
        $this->startLogStep(__METHOD__);
        if ($this->_to_update)
            $this->update1();
        jElockyLog::endStep();
    }

    public function postSave() {
        $this->startLogStep(__METHOD__);
        if ($this->_to_update) {
            $this->update2();
            $this->_to_update = false;
        }
        
        // Update the user photo if needed
        $this->updatePhoto('requestUserPhoto');
        jElockyLog::endStep();
    }
   
    /**
     * Update this user information
     * Intended to be called when entering the equipment page or in preSave
     * This user shall be save after calling this method
     * @throws \Exception in case of connexion error with the Elocky server
     */
    public function update1() {
        $this->startLogStep(__METHOD__);
        if (($api = $this->getAPI()) != null) {
            try {
                $userProfile = $api->requestUserProfile();
                $this->setMultipleConfiguration(array('first_name','last_name','phone','created','photo'),
                    $userProfile);
                $this->setLogicalId($userProfile['reference']);
                $this->updateAPI();
            } catch (\Exception $e) {
                $this->processElockyException($e->getMessage(), true);
            }
        }
        jElockyLog::endStep();
    }

    /**
     * Update this user information that are not updated in update1: places,
     * photo (image).
     * Intended to be called in postSave.
     * It is not required to save this user fater calling this method.
     * @throws \Exception in case of connexion error with the Elocky server
     */
    public function update2() {
        $this->startLogStep(__METHOD__);
        if ($this->getIsEnable()) {
            try {
                foreach ($this->requestPlaces() as $place) {
                    jElockyLog::add('debug', 'treating place ' . $place['admin_address'][0]['name']);
                    $place_eql = jElocky_place::getInstance($place);
                    $place_eql->addUser($this->getId(), $place['admin_address'][0]['state'],
                        $place['admin_address'][0]['name']);
                    $place_eql->save();
                }
            }
            catch (Exception $e) {
                $this->processElockyException($e->getMessage(), true);
            }
        }
        jElockyLog::endStep();
    }
    
    public static function cronLowFreq() {
        jElockyLog::startStep(__METHOD__);
        foreach (self::byType(__CLASS__, true) as $user_eql) {
            $user_eql->update1();
            $user_eql->save();
            $user_eql->update2();
        }
        jElockyLog::endStep();
    }
    
    public function setIsEnable($_isEnable) {
        if (!$this->getIsEnable() && $_isEnable)
            $this->_to_update = true;
           
        return parent::setIsEnable($_isEnable);
    }
   
    /**
     * Return places of this user
     * @return array[jElocky_place] array of the places of this user
     */
    public function getPlaces() {       
        $conf = json_encode(array('ref' => $this->getId()));
        return self::byTypeAndSearhConfiguration(jElocky_place::class, substr($conf, 1, -1));
    }
       
    private function isInitialized() {
        return $this->getConfiguration('auth_data', '') != '';
    }

    /**
     * Return the ElockyAPI object related to this user
     * @return null|UserAPI null if this user is not enabled
     */
    public function getAPI() {
        if (! $this->getIsEnable())
            return null;
        
        if (isset($this->_api))
            return $this->_api;
            
        $id = config::byKey('id', 'jElocky');
        $secret = config::byKey('secret', 'jElocky');
        $username = $this->getConfiguration('username');
        $password = $this->getConfiguration('password');
        $auth_data = $this->getConfiguration('auth_data', NULL);

        $this->_api = new UserAPI($id, $secret, $username, $password, $this);
        
        if (isset($auth_data)) {
            $this->_api->setAuthenticationData(json_decode($auth_data, TRUE));
        }

        return $this->_api;
    }
    
    /**
     * Request and return all the places of this user, or the specified one if $place_id is provided
     * @param int $place_id id of the specific place to retrieve, -1 (default) to retrieve all places
     * @return array|null null if the user is not enabled or the given place_id is not found
     * @throws \Exception in case of communication error with the Elocky server
     */
    public function requestPlaces($place_id=-1) {
        jElockyLog::add('debug', 'requesting ' . ($place_id >= 0 ? 'place ' . $place_id : ' places') .
            ' for user ' . $this->getName());
        $places = $this->getAPI()->requestPlaces()['lieux'];
        if ($place_id < 0)
            return $places;
        
        foreach ($places as $place) {
            if ($place['id'] == $place_id)
                return $place;
        }
        
        return null;
    }

    private function updateAPI() {
        $this->setConfiguration('auth_data', json_encode($this->getAPI()->getAuthenticationData()));
    }
}

class jElocky_userCmd extends cmd {
}