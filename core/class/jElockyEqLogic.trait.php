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

require_once __DIR__ . '/jElockyUtil.class.php';

trait jElockyEqLogic {
    
    /**
     * @var boolean
     */
    private $_to_update = false;
    
    /**
     * Override setIsEnable to memorize that the eqLogic shall be updated on save when just enabled (except at creation).
     * See preSave and postSave.
     * @param boolean $isEnable
     * @return boolean
     */
    public function setIsEnable($isEnable) {
        if (isset($this->id) && !$this->getIsEnable() && $isEnable)
            $this->_to_update = true;
            
        return parent::setIsEnable($isEnable);
    }
    
    /**
     * Lock this object. Lock status is memorized in the cache only.
     * @param boolean $isLocked
     */
    public function setIsLocked($isLocked) {
        $this->setCache('isLocked', $isLocked);
    }
    
    /**
     * Return whether or not this jElockyEqLogic is locked.
     * @return boolean
     */
    public function getIsLocked() {
        return $this->getCache('isLocked', false);
    }
    
    /**
     * Return whether or not the given jElockyEqLogic object is locked.
     * It is an efficient way to retrieve the lock status if only its id is known
     * @param int $id
     * @return boolean
     */
    public static function getIsLockedById($id) {
        $eql = new eqLogic();
        return $eql->setId($id)->getCache('isLocked', false);
    }
      
    public function preSave() {
        $this->startLogStep(__METHOD__);
        if ($this->_to_update)
            $this->update1(false);
        jElockyLog::endStep();
    }
    
    public function postSave() {
        $this->startLogStep(__METHOD__);
        if ($this->_to_update) {
            $this->update2();
            $this->_to_update = false;
        }
        jElockyLog::endStep();
    }
    
    /**
     * Return the photo pathname of this eqLogic
     * @return string full filename on the jeedom server
     */
    public function getPhoto() {
        $photo = $this->getConfiguration('photo', null);
        if (isset($photo))
            return jElockyUtil::getRelativeDataDir() . '/' . $photo;
        else
            return 'core/img/no_image.gif';
    }
    
    /**
     * Override eqLogic method to points towards jElocky and not jElocky_place
     * @return string
     * {@inheritDoc}
     * @see eqLogic::getLinkToConfiguration()
     */
    public function getLinkToConfiguration() {
        return 'index.php?v=d&p=jElocky&m=jElocky&id=' . $this->getId();
    }
    
    /**
     * Override the getImage method
     * @return string
     */
    public function getImage() {
        $plugin = plugin::byId('jElocky');
        return $plugin->getPathImgIcon();
    }

    /**
     * @param array[string] $keys
     * @param array[string] $values
     */
    private function setMultipleConfiguration($keys, $values) {
        foreach ($keys as $key) {
            $this->setConfiguration($key, $values[$key]);
            jElockyLog::add('info', '->' . $this->getName() . '|' . $key . ': ' . json_encode($values[$key]));
        }
    }
       
    /**
     * Updates the commands of this object with the information retrieved from the Elocky server
     * Commands are created if not already existing
     * @param array $defs array listing/defining info from $data to be converted into commands
     * @param array $data array data array retrieved from the Elocky server
     */
    private function setCmdData($defs, $data) {
        foreach($defs as $key => $def) {
            if (key_exists($key, $data)) {
                $cmd = $this->getCmd(null, $def['id']);
                if (!is_object($cmd)) {
                    $cmd = new jElocky_objectCmd();
                    $cmd->setName($key);
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setType('info');
                    $cmd->setSubType($def['stype']);
                    $cmd->setLogicalId($def['id']);
                    $cmd->setIsVisible(1);
                    if (key_exists('unit', $def))
                        $cmd->setUnite($def['unit']);
                    if (key_exists('historizeMode', $def))
                        $cmd->setConfiguration('historizeMode', $def['historizeMode']);
                    $cmd->save();    
                }
                $val = key_exists('process', $def) ? call_user_func($def['process'], $data[$key]) : $data[$key];
                $cmd->event($val);
                if ($def['id'] == 'battery')
                    $this->batteryStatus($val);
                jElockyLog::add('info', '->' . $this->getName() . '|' . $cmd->getName() . ' ' . $data[$key]);
            }
            else {
                jElockyLog::add('warning', 'key "' . $key . '" not found in object ' . $data['name']);
            } 
        }
    }
    
    /**
     * Update if needed the photo of this eqLogic.
     * If the photo has changed the $_api_function is called to upload from the Elocky server
     * and saved it in the self::DATA_DIR.
     *
     * @param string $api_func
     *            ElockyAPI\User API method to call to update the photo
     */
    private function updatePhoto($api_func) {
        $photo = $this->getConfiguration('photo', null);
        if (isset($photo)) {
            $f = jElockyUtil::DATA_DIR . '/' . $photo;
            if (! file_exists($f)) {
                jElockyLog::add('debug', 'loading ' . $this->getName() . "'s photo");
                if (($api = $this->getAPI()) != null)
                    call_user_func(array($api, $api_func), $photo, jElockyUtil::DATA_DIR);
            }
            else
                jElockyLog::add('debug', $this->getName() . "'s photo is up to date");
        }
    }
    
    /**
     * @param string $msg message to log
     * @param bool $is_log_step_ended
     * @throws \Exception throw a new exception
     */
    private function processElockyException($msg, $is_log_step_ended) {
        jElockyLog::add('error', $msg);
        if ($is_log_step_ended) {
            jElockyLog::endStep();
        }
        throw new \Exception('Echec de la connexion aux serveurs Elocky');
    }
    
    /**
     * Log a start step. Add the name of this eqLogic.
     * @param string $method method name
     */
    private function startLogStep($method) {
        jElockyLog::startStep($method . ' for ' . $this->getName());
    }
}

