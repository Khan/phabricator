<?php

final class PHUIHeaderView extends AphrontView {

  const PROPERTY_STATUS = 1;

  private $objectName;
  private $header;
  private $tags = array();
  private $image;
  private $subheader;
  private $gradient;
  private $noBackground;
  private $bleedHeader;
  private $properties = array();
  private $policyObject;

  public function setHeader($header) {
    $this->header = $header;
    return $this;
  }

  public function setObjectName($object_name) {
    $this->objectName = $object_name;
    return $this;
  }

  public function setNoBackground($nada) {
    $this->noBackground = $nada;
    return $this;
  }

  public function addTag(PhabricatorTagView $tag) {
    $this->tags[] = $tag;
    return $this;
  }

  public function setImage($uri) {
    $this->image = $uri;
    return $this;
  }

  public function setSubheader($subheader) {
    $this->subheader = $subheader;
    return $this;
  }

  public function setBleedHeader($bleed) {
    $this->bleedHeader = $bleed;
    return $this;
  }

  public function setGradient($gradient) {
    $this->gradient = $gradient;
    return $this;
  }

  public function setPolicyObject(PhabricatorPolicyInterface $object) {
    $this->policyObject = $object;
    return $this;
  }

  public function addProperty($property, $value) {
    $this->properties[$property] = $value;
    return $this;
  }

  public function setStatus($icon, $color, $name) {
    $header_class = 'phui-header-status';

    if ($color) {
      $icon = $icon.'-'.$color;
      $header_class = $header_class.'-'.$color;
    }

    $img = id(new PHUIIconView())
      ->setSpriteSheet(PHUIIconView::SPRITE_STATUS)
      ->setSpriteIcon($icon);

    $tag = phutil_tag(
      'span',
      array(
        'class' => "{$header_class} plr",
      ),
      array(
        $img,
        $name,
      ));

    return $this->addProperty(self::PROPERTY_STATUS, $tag);
  }

  public function render() {
    require_celerity_resource('phui-header-view-css');

    $classes = array();
    $classes[] = 'phui-header-shell';

    if ($this->noBackground) {
      $classes[] = 'phui-header-no-backgound';
    }

    if ($this->bleedHeader) {
      $classes[] = 'phui-bleed-header';
    }

    if ($this->gradient) {
      $classes[] = 'sprite-gradient';
      $classes[] = 'gradient-'.$this->gradient.'-header';
    }

    if ($this->properties || $this->policyObject || $this->subheader) {
      $classes[] = 'phui-header-tall';
    }

    $image = null;
    if ($this->image) {
      $image = phutil_tag(
        'span',
        array(
          'class' => 'phui-header-image',
          'style' => 'background-image: url('.$this->image.')',
        ),
        '');
      $classes[] = 'phui-header-has-image';
    }

    $header = array();
    $header[] = $this->header;

    if ($this->objectName) {
      array_unshift(
        $header,
        phutil_tag(
          'a',
          array(
            'href' => '/'.$this->objectName,
          ),
          $this->objectName),
        ' ');
    }

    if ($this->tags) {
      $header[] = ' ';
      $header[] = phutil_tag(
        'span',
        array(
          'class' => 'phui-header-tags',
        ),
        array_interleave(' ', $this->tags));
    }

    if ($this->subheader) {
      $header[] = phutil_tag(
        'div',
        array(
          'class' => 'phui-header-subheader',
        ),
        $this->subheader);
    }

    if ($this->properties || $this->policyObject) {
      $property_list = array();
      foreach ($this->properties as $type => $property) {
        switch ($type) {
          case self::PROPERTY_STATUS:
            $property_list[] = $property;
          break;
          default:
            throw new Exception('Incorrect Property Passed');
          break;
        }
      }

      if ($this->policyObject) {
        $property_list[] = $this->renderPolicyProperty($this->policyObject);
      }

      $header[] = phutil_tag(
        'div',
        array(
          'class' => 'phui-header-subheader',
        ),
        $property_list);
    }

    return phutil_tag(
      'div',
      array(
        'class' => implode(' ', $classes),
      ),
      array(
        $image,
        phutil_tag(
          'h1',
          array(
            'class' => 'phui-header-view',
          ),
          $header),
      ));
  }

  private function renderPolicyProperty(PhabricatorPolicyInterface $object) {
    $policies = PhabricatorPolicyQuery::loadPolicies(
      $this->getUser(),
      $object);

    $view_capability = PhabricatorPolicyCapability::CAN_VIEW;
    $policy = idx($policies, $view_capability);
    if (!$policy) {
      return null;
    }

    $phid = $object->getPHID();

    $icon = id(new PHUIIconView())
      ->setSpriteSheet(PHUIIconView::SPRITE_STATUS)
      ->setSpriteIcon($policy->getIcon());

    $link = javelin_tag(
      'a',
      array(
        'class' => 'policy-link',
        'href' => '/policy/explain/'.$phid.'/'.$view_capability.'/',
        'sigil' => 'workflow',
      ),
      $policy->getName());

    return array($icon, $link);
  }
}