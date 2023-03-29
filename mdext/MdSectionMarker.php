<?php
namespace Grav\Plugin\AksoMdExt;

use DiDom\Document;
use DiDom\Element;
use Grav\Common\Markdown\Parsedown;

class MdSectionMarker {
    public static function register(Parsedown $markdown) {
        $markdown->addBlockType('!', 'SectionMarker');
        $markdown->blockSectionMarker = function($line) {
            if (preg_match('/^!###(?:\[([\w\-_]+)\])?\s+(.*)/', $line['text'], $matches)) {
                $attrs = array('class' => 'section-marker');
                if (isset($matches[1]) && !empty($matches[1])) {
                    $attrs['id'] = $matches[1];
                } else {
                    $attrs['id'] = Utils::escapeFileNameLossy($matches[2]); // close enough
                }
                $text = trim($matches[2], ' ');
                return array(
                    'element' => array(
                        'name' => 'h3',
                        'attributes' => $attrs,
                        'handler' => 'line',
                        'text' => $text,
                    ),
                );
            }
        };
    }

    public static function renderInDocument($doc) {
        $secMarkers = $doc->find('.section-marker');
        foreach ($secMarkers as $sm) {
            $innerHtml = $sm->innerHtml();
            $newSM = new Element('h3');
            $newSM->class = $sm->class;
            if (isset($sm->id)) $newSM->id = $sm->id;
            $contentSpan = new Element('span');
            {
                $elements = new Document($innerHtml);
                $elements = $elements->find('body')[0];
                foreach ($elements->children() as $el) {
                    if ($el->isElementNode() && $el->tag === 'p') {
                        foreach ($el->children() as $child) $contentSpan->appendChild($child);
                    } else {
                        $contentSpan->appendChild($el);
                    }
                }
            }
            $contentSpan->class = 'section-marker-inner';
            $newSM->appendChild($contentSpan);
            $fillSpan = new Element('span');
            $fillSpan->class = 'section-marker-fill';
            $newSM->appendChild($fillSpan);
            $sm->replace($newSM);
        }
    }
}
