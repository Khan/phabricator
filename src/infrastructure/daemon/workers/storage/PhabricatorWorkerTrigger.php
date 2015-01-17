<?php

final class PhabricatorWorkerTrigger
  extends PhabricatorWorkerDAO {

  protected $triggerVersion;
  protected $clockClass;
  protected $clockProperties;
  protected $actionClass;
  protected $actionProperties;

  private $action = self::ATTACHABLE;
  private $clock = self::ATTACHABLE;
  private $event = self::ATTACHABLE;

  protected function getConfiguration() {
    return array(
      self::CONFIG_TIMESTAMPS => false,
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_SERIALIZATION => array(
        'clockProperties' => self::SERIALIZATION_JSON,
        'actionProperties' => self::SERIALIZATION_JSON,
      ),
      self::CONFIG_COLUMN_SCHEMA => array(
        'triggerVersion' => 'uint32',
        'clockClass' => 'text64',
        'actionClass' => 'text64',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_trigger' => array(
          'columns' => array('triggerVersion'),
          'unique' => true,
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function save() {
    $conn_w = $this->establishConnection('w');

    $this->openTransaction();
      $next_version = LiskDAO::loadNextCounterValue(
        $conn_w,
        PhabricatorTriggerDaemon::COUNTER_VERSION);
      $this->setTriggerVersion($next_version);

      $result = parent::save();
    $this->saveTransaction();

    return $this;
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      PhabricatorWorkerTriggerPHIDType::TYPECONST);
  }

  /**
   * Return the next time this trigger should execute.
   *
   * This method can be called either after the daemon executed the trigger
   * successfully (giving the trigger an opportunity to reschedule itself
   * into the future, if it is a recurring event) or after the trigger itself
   * is changed (usually because of an application edit). The `$is_reschedule`
   * parameter distinguishes between these cases.
   *
   * @param int|null Epoch of the most recent successful event execution.
   * @param bool `true` if we're trying to reschedule the event after
   *   execution; `false` if this is in response to a trigger update.
   * @return int|null Return an epoch to schedule the next event execution,
   *   or `null` to stop the event from executing again.
   */
  public function getNextEventEpoch($last_epoch, $is_reschedule) {
    return $this->getClock()->getNextEventEpoch($last_epoch, $is_reschedule);
  }


  /**
   * Execute the event.
   *
   * @param int|null Epoch of previous execution, or null if this is the first
   *   execution.
   * @param int Scheduled epoch of this execution. This may not be the same
   *   as the current time.
   * @return void
   */
  public function executeTrigger($last_event, $this_event) {
    return $this->getAction()->execute($last_event, $this_event);
  }

  public function getEvent() {
    return $this->assertAttached($this->event);
  }

  public function attachEvent(PhabricatorWorkerTriggerEvent $event = null) {
    $this->event = $event;
    return $this;
  }

  public function setAction(PhabricatorTriggerAction $action) {
    $this->actionClass = get_class($action);
    $this->actionProperties = $action->getProperties();
    return $this->attachAction($action);
  }

  public function getAction() {
    return $this->assertAttached($this->action);
  }

  public function attachAction(PhabricatorTriggerAction $action) {
    $this->action = $action;
    return $this;
  }

  public function setClock(PhabricatorTriggerClock $clock) {
    $this->clockClass = get_class($clock);
    $this->clockProperties = $clock->getProperties();
    return $this->attachClock($clock);
  }

  public function getClock() {
    return $this->assertAttached($this->clock);
  }

  public function attachClock(PhabricatorTriggerClock $clock) {
    $this->clock = $clock;
    return $this;
  }

}
