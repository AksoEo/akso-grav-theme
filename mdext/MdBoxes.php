<?php

namespace Grav\Plugin\AksoMdExt;

use DiDom\Element;
use Grav\Common\Grav;
use Grav\Common\Markdown\Parsedown;

class MdBoxes {
    public static function register(Parsedown $markdown) {
        $language = Grav::instance()['language'];

        $markdown->addBlockType('[', 'InfoBox', true, true);
        $markdown->blockInfoBox = function($line, $block) use ($language) {
            if (preg_match('/^\[\[(\w+)]]/', $line['text'], $matches)) {
                $boxTypes = array(
                    'informskatolo' => 'infobox',
                    'atentigoskatolo' => 'infobox is-warning',
                    'avertoskatolo' => 'infobox is-error',

                    // we don't call it 'is-ad' because of ad blockers
                    'anonceto' => 'infobox is-ab',
                );
                $tag = $matches[1];
                if (!isset($boxTypes[$tag])) return;
                $typeClass = $boxTypes[$tag];

                $attributes = array(
                    'class' => $typeClass,
                    'data-tag' => $tag,
                );

                if ($tag === 'anonceto') {
                    $attributes['data-ab-label'] = $language->translate('THEME_AKSO.content.info_box_ad_label');
                }

                return array(
                    'char' => $line['text'][0],
                    'element' => array(
                        'name' => 'blockquote',
                        'attributes' => $attributes,
                        // parse markdown inside
                        'handler' => 'lines',
                        // lines handler needs an array of lines
                        'text' => array(),
                    ),
                );
            }
        };
        $markdown->blockInfoBoxContinue = function($line, $block) {
            if (isset($block['complete'])) {
                return;
            }

            // A blank newline has occurred.
            if (isset($block['interrupted'])) {
                array_push($block['element']['text'], "\n");
                unset($block['interrupted']);
            }

            // Check for end of the block.
            if (preg_match('/\[\[\/' . $block['element']['attributes']['data-tag'] . ']]/', $line['text'])) {
                $block['complete'] = true;
                return $block;
            }

            array_push($block['element']['text'], $line['body']);

            return $block;
        };
        $markdown->blockInfoBoxComplete = function($block) {
            unset($block['element']['attributes']['data-tag']);
            return $block;
        };

        $markdown->addBlockType('[', 'Expandable', true, true);
        $markdown->blockExpandable = function($line, $block) {
            if (preg_match('/^\[\[etendeblo\]\]/', $line['text'], $matches)) {
                return array(
                    'char' => $line['text'][0],
                    'element' => array(
                        'name' => 'details',
                        'attributes' => array(
                            'class' => 'unhandled-expandable',
                        ),
                        'handler' => 'lines',
                        'text' => array(),
                    ),
                );
            }
        };
        $markdown->blockExpandableContinue = function($line, $block) {
            if (isset($block['complete'])) return;

            // A blank newline has occurred.
            if (isset($block['interrupted'])) {
                array_push($block['element']['text'], "\n");
                unset($block['interrupted']);
            }

            // Check for end of the block.
            if (preg_match('/\[\[\/etendeblo]]/', $line['text'])) {
                $block['complete'] = true;
                return $block;
            }

            array_push($block['element']['text'], $line['body']);
            return $block;
        };
        $markdown->blockExpandableComplete = function($block) {
            return $block;
        };
    }

    public static function renderInDocument($doc) {
        $unhandledExpandables = $doc->find('.unhandled-expandable');
        foreach ($unhandledExpandables as $exp) {
            $topLevelChildren = $exp->children();
            $summaryNodes = array();
            $didPassFirstBreak = false;
            $remainingNodes = array();
            foreach ($topLevelChildren as $child) {
                if (!$didPassFirstBreak and $child->isElementNode() and $child->tag === 'hr') {
                    $didPassFirstBreak = true;
                    continue;
                }
                if (!$didPassFirstBreak) {
                    $summaryNodes[] = $child;
                } else {
                    $remainingNodes[] = $child;
                }
            }
            $newExpNode = new Element('details');
            $newExpNode->class = 'expandable';
            $summaryNode = new Element('summary');
            foreach ($summaryNodes as $child) {
                $summaryNode->appendChild($child);
            }
            $newExpNode->appendChild($summaryNode);
            foreach ($remainingNodes as $child) {
                $newExpNode->appendChild($child);
            }
            $exp->replace($newExpNode);
        }
    }
}
