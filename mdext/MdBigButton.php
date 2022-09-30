<?php

namespace Grav\Plugin\AksoMdExt;

use Grav\Common\Markdown\Parsedown;

class MdBigButton{
    public static function register(Parsedown $markdown) {
        $markdown->addBlockType('[', 'AksoBigButton');
        $markdown->blockAksoBigButton = function($line, $block) {
            if (preg_match('/^\[\[butono(!)?(!)?\s+([^\s]+)\s+(.+?)]]/', $line['text'], $matches)) {
                $emphasis = $matches[1];
                $emphasis2 = $matches[2];
                $linkTarget = $matches[3];
                $label = $matches[4];

                $emphasisClass = '';
                if ($emphasis) $emphasisClass .= ' is-primary has-emphasis';
                if ($emphasis2) $emphasisClass .= ' has-big-emphasis';

                return array(
                    'element' => array(
                        'name' => 'div',
                        'attributes' => array(
                            'class' => 'big-actionable-button-container' . $emphasisClass,
                        ),
                        'handler' => 'elements',
                        'text' => [
                            array(
                                'name' => 'a',
                                'attributes' => array(
                                    'class' => 'link-button big-actionable-button' . $emphasisClass,
                                    'href' => $linkTarget,
                                ),
                                'handler' => 'elements',
                                'text' => [
                                    array(
                                        'name' => 'span',
                                        'text' => $label,
                                    ),
                                    array(
                                        'name' => 'span',
                                        'attributes' => array(
                                            'class' => 'action-arrow-icon',
                                        ),
                                        'text' => '',
                                    ),
                                    array(
                                        'name' => 'span',
                                        'attributes' => array(
                                            'class' => 'action-button-shine',
                                        ),
                                        'text' => '',
                                    ),
                                ],
                            ),
                        ],
                    ),
                );
            }
        };
    }
}
