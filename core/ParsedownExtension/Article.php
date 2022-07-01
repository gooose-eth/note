<?php
namespace Core\ParsedownExtension;
use Parsedown;

/**
 * ParsedownExtension Article
 * 본문용 마크다운 파서 확장
 */
class Article extends Parsedown
{
  protected function inlineImage($excerpt): ?array
  {
    $image = parent::inlineImage($excerpt);
    if (!($image ?? false)) return null;
    $image['element']['attributes']['lazy'] = 'true';
    return $image;
  }

  protected function blockHeader($line): ?array
  {
    $element = parent::blockHeader($line);
    if (!($element ?? false)) return null;
    $element['element']['attributes']['id'] = str_replace(' ', '-', strtolower($element['element']['text']));
    return $element;
  }

}