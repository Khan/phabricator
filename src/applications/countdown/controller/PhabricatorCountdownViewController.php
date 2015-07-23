<?php

final class PhabricatorCountdownViewController
  extends PhabricatorCountdownController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $countdown = id(new PhabricatorCountdownQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$countdown) {
      return new Aphront404Response();
    }

    $countdown_view = id(new PhabricatorCountdownView())
      ->setUser($viewer)
      ->setCountdown($countdown)
      ->setHeadless(true);

    $id = $countdown->getID();
    $title = $countdown->getTitle();

    $crumbs = $this
      ->buildApplicationCrumbs()
      ->addTextCrumb("C{$id}");

    $epoch = $countdown->getEpoch();
    if ($epoch >= PhabricatorTime::getNow()) {
      $icon = 'fa-clock-o';
      $color = '';
      $status = pht('Running');
    } else {
      $icon = 'fa-check-square-o';
      $color = 'dark';
      $status = pht('Launched');
    }

    $header = id(new PHUIHeaderView())
      ->setHeader($title)
      ->setUser($viewer)
      ->setPolicyObject($countdown)
      ->setStatus($icon, $color, $status);

    $actions = $this->buildActionListView($countdown);
    $properties = $this->buildPropertyListView($countdown, $actions);

    $object_box = id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->addPropertyList($properties);

    $timeline = $this->buildTransactionTimeline(
      $countdown,
      new PhabricatorCountdownTransactionQuery());
    $timeline->setShouldTerminate(true);

    $content = array(
      $crumbs,
      $object_box,
      $countdown_view,
      $timeline,
    );

    return $this->buildApplicationPage(
      $content,
      array(
        'title' => $title,
      ));
  }

  private function buildActionListView(PhabricatorCountdown $countdown) {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $id = $countdown->getID();

    $view = id(new PhabricatorActionListView())
      ->setObject($countdown)
      ->setUser($viewer);

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $countdown,
      PhabricatorPolicyCapability::CAN_EDIT);

    $view->addAction(
      id(new PhabricatorActionView())
        ->setIcon('fa-pencil')
        ->setName(pht('Edit Countdown'))
        ->setHref($this->getApplicationURI("edit/{$id}/"))
        ->setDisabled(!$can_edit)
        ->setWorkflow(!$can_edit));

    $view->addAction(
      id(new PhabricatorActionView())
        ->setIcon('fa-times')
        ->setName(pht('Delete Countdown'))
        ->setHref($this->getApplicationURI("delete/{$id}/"))
        ->setDisabled(!$can_edit)
        ->setWorkflow(true));

    return $view;
  }

  private function buildPropertyListView(
    PhabricatorCountdown $countdown,
    PhabricatorActionListView $actions) {

    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setUser($viewer)
      ->setObject($countdown)
      ->setActionList($actions);

    $view->addProperty(
      pht('Author'),
      $viewer->renderHandle($countdown->getAuthorPHID()));

    return $view;
  }

}
