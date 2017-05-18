<?php

final class ManiphestTaskTitleTransaction
  extends ManiphestTaskTransactionType {

  const TRANSACTIONTYPE = 'title';

  public function generateOldValue($object) {
    return $object->getTitle();
  }

  public function applyInternalEffects($object, $value) {
    $object->setTitle($value);
  }

  public function getActionStrength() {
    return 1.4;
  }

  public function getActionName() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();
    if ($old === null) {
      return pht('Created');
    }

    return pht('Retitled');
  }

  public function getTitle() {
    $old = $this->getOldValue();
    if ($old === null) {
      return pht(
        '%s created this task.',
        $this->renderAuthor());
    }

    return pht(
      '%s changed the title from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());

  }

  public function getTitleForFeed() {
    $old = $this->getOldValue();
    if ($old === null) {
      return pht(
        '%s created %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }

    return pht(
      '%s changed %s title from %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();

    if ($this->isEmptyTextTransaction($object->getTitle(), $xactions)) {
      $errors[] = $this->newRequiredError(
        pht('Tasks must have a title.'));
    }

    return $errors;
  }

}
