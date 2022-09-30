<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Plugin\AksoMdExt\MarkdownExt;
use RocketTheme\Toolbox\Event\Event;

class TEJO extends Theme {
    public static function getSubscribedEvents() {
        return [
            'onThemeInitialized' => ['onThemeInitialized', 0],
            'onMarkdownInitialized' => ['onMarkdownInitialized', 0],
            'onOutputGenerated' => ['onOutputGenerated', 0],
        ];
    }

    public function onThemeInitialized() {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    private $markdownExt = null;
    private function loadMarkdownExt() {
        if (!$this->markdownExt) {
            $this->markdownExt = new MarkdownExt();
        }
        return $this->markdownExt;
    }

    public function onMarkdownInitialized(Event $event) {
        $markdownExt = $this->loadMarkdownExt();
        $markdownExt->register($event['markdown']);
    }

    public function onOutputGenerated(Event $event) {
        $markdownExt = $this->loadMarkdownExt();
        $markdownExt->renderHTML();
    }
}
