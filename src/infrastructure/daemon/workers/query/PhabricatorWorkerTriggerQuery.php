<?php

final class PhabricatorWorkerTriggerQuery
  extends PhabricatorOffsetPagedQuery {

  const ORDER_EXECUTION = 'execution';
  const ORDER_VERSION = 'version';

  private $versionMin;
  private $versionMax;
  private $nextEpochMin;
  private $nextEpochMax;

  private $needEvents;
  private $order = self::ORDER_EXECUTION;

  public function withVersionBetween($min, $max) {
    $this->versionMin = $min;
    $this->versionMax = $max;
    return $this;
  }

  public function withNextEventBetween($min, $max) {
    $this->nextEpochMin = $min;
    $this->nextEpochMax = $max;
    return $this;
  }

  public function needEvents($need_events) {
    $this->needEvents = $need_events;
    return $this;
  }

  public function setOrder($order) {
    $this->order = $order;
    return $this;
  }

  public function execute() {
    $task_table = new PhabricatorWorkerTrigger();

    $conn_r = $task_table->establishConnection('r');

    $rows = queryfx_all(
      $conn_r,
      'SELECT t.* FROM %T t %Q %Q %Q %Q',
      $task_table->getTableName(),
      $this->buildJoinClause($conn_r),
      $this->buildWhereClause($conn_r),
      $this->buildOrderClause($conn_r),
      $this->buildLimitClause($conn_r));

    $triggers = $task_table->loadAllFromArray($rows);

    if ($triggers) {
      if ($this->needEvents) {
        $ids = mpull($triggers, 'getID');

        $events = id(new PhabricatorWorkerTriggerEvent())->loadAllWhere(
          'triggerID IN (%Ld)',
          $ids);
        $events = mpull($events, null, 'getTriggerID');

        foreach ($triggers as $key => $trigger) {
          $event = idx($events, $trigger->getID());
          $trigger->attachEvent($event);
        }
      }

      foreach ($triggers as $key => $trigger) {
        $clock_class = $trigger->getClockClass();
        if (!is_subclass_of($clock_class, 'PhabricatorTriggerClock')) {
          unset($triggers[$key]);
          continue;
        }

        try {
          $argv = array($trigger->getClockProperties());
          $clock = newv($clock_class, $argv);
        } catch (Exception $ex) {
          unset($triggers[$key]);
          continue;
        }

        $trigger->attachClock($clock);
      }


      foreach ($triggers as $key => $trigger) {
        $action_class = $trigger->getActionClass();
        if (!is_subclass_of($action_class, 'PhabricatorTriggerAction')) {
          unset($triggers[$key]);
          continue;
        }

        try {
          $argv = array($trigger->getActionProperties());
          $action = newv($action_class, $argv);
        } catch (Exception $ex) {
          unset($triggers[$key]);
          continue;
        }

        $trigger->attachAction($action);
      }
    }

    return $triggers;
  }

  private function buildJoinClause(AphrontDatabaseConnection $conn_r) {
    $joins = array();

    if (($this->nextEpochMin !== null) ||
        ($this->nextEpochMax !== null) ||
        ($this->order == PhabricatorWorkerTriggerQuery::ORDER_EXECUTION)) {
      $joins[] = qsprintf(
        $conn_r,
        'JOIN %T e ON e.triggerID = t.id',
        id(new PhabricatorWorkerTriggerEvent())->getTableName());
    }

    return implode(' ', $joins);
  }

  private function buildWhereClause(AphrontDatabaseConnection $conn_r) {
    $where = array();

    if ($this->versionMin !== null) {
      $where[] = qsprintf(
        $conn_r,
        't.triggerVersion >= %d',
        $this->versionMin);
    }

    if ($this->versionMax !== null) {
      $where[] = qsprintf(
        $conn_r,
        't.triggerVersion <= %d',
        $this->versionMax);
    }

    if ($this->nextEpochMin !== null) {
      $where[] = qsprintf(
        $conn_r,
        'e.nextEventEpoch >= %d',
        $this->nextEpochMin);
    }

    if ($this->nextEpochMax !== null) {
      $where[] = qsprintf(
        $conn_r,
        'e.nextEventEpoch <= %d',
        $this->nextEpochMax);
    }

    return $this->formatWhereClause($where);
  }

  private function buildOrderClause(AphrontDatabaseConnection $conn_r) {
    switch ($this->order) {
      case self::ORDER_EXECUTION:
        return qsprintf(
          $conn_r,
          'ORDER BY e.nextEventEpoch ASC, e.id ASC');
      case self::ORDER_VERSION:
        return qsprintf(
          $conn_r,
          'ORDER BY t.triggerVersion ASC');
      default:
        throw new Exception(
          pht(
            'Unsupported order "%s".',
            $this->order));
    }
  }

}
