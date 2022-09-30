<?php

namespace Grav\Plugin\AksoMdExt;

use DiDom\Element;
use Grav\Common\Grav;
use Grav\Common\Markdown\Parsedown;

class MdNewsSidebar {
    public static function register(Parsedown $markdown) {
        $markdown->addBlockType('[', 'AksoNews');
        $markdown->blockAksoNews = function($line, $block) {
            if (preg_match('/^\[\[aktuale\s+([^\s]+)\s+(\d+)(?:\s+"([^"]+)")?]]/', $line['text'], $matches)) {
                $target = $matches[1];
                $count = (int) $matches[2];
                $title = isset($matches[3]) ? "$matches[3]" : '';

                return array(
                    'element' => array(
                        'name' => 'script',
                        'attributes' => array(
                            'class' => 'news-sidebar',
                            'type' => 'application/json',
                        ),
                        'text' => json_encode(array(
                            'title' => $title,
                            'target' => $target,
                            'count' => $count,
                        )),
                    ),
                );
            }
        };
    }

    private static function createError($doc) {
        $el = $doc->createElement('div', Grav::instance()['language']->translate('THEME_TEJO.content.render_error'));
        $el->class = 'md-render-error';
        return $el;
    }

    public static function renderInDocument($doc) {
        $language = Grav::instance()['language'];

        $unhandledNews = $doc->find('.news-sidebar');
        foreach ($unhandledNews as $news) {
            $textContent = $news->text();
            if (strncmp($textContent, '!', 1) === 0) {
                // this is an error; skip
                $news->replace(MdNewsSidebar::createError($doc));
                continue;
            }

            $newNews = new Element('aside');
            $newNews->class = 'news-sidebar';

            try {
                $readMoreLabel = $language->translate('THEME_TEJO.content.news_read_more');
                $moreNewsLabel = $language->translate('THEME_TEJO.content.news_sidebar_more_news');

                $params = json_decode($textContent, true);

                $newsTitle = $params['title'];
                $newsPath = $params['target'];
                $newsCount = $params['count'];

                $newNews->setAttribute('aria-label', $newsTitle);

                $newsPage = Grav::instance()['pages']->find($newsPath);
                $newsPostCollection = $newsPage->collection();

                $newsPages = [];
                for ($i = 0; $i < min($newsCount, $newsPostCollection->count()); $i++) {
                    $newsPostCollection->next();
                    // for some reason, calling next() after current() causes the first item
                    // to duplicate
                    $newsPages[] = $newsPostCollection->current();
                }
                $hasMore = count($newsPages) < $newsPostCollection->count();

                $moreNews = new Element('div');
                $moreNews->class = 'more-news-container';
                $moreNewsLink = new Element('a', $moreNewsLabel);
                $moreNewsLink->class = 'more-news-link link-button';
                $moreNewsLink->href = $newsPath;
                $moreNews->appendChild($moreNewsLink);

                $title = new Element('h4', $newsTitle);
                $title->class = 'news-title';
                if ($hasMore) $title->appendChild($moreNews);
                $newNews->appendChild($title);

                $newNewsList = new Element('ul');
                $newNewsList->class = 'news-items';

                foreach ($newsPages as $page) {
                    $li = new Element('li');
                    $li->class = 'news-item';
                    $pageLink = new Element('a', $page->title());
                    $pageLink->class = 'item-title';
                    $pageLink->href = $page->url();
                    $li->appendChild($pageLink);
                    $pageDate = $page->date();
                    $itemMeta = new Element('div', Utils::formatDate((new \DateTime("@$pageDate"))->format('Y-m-d')));
                    $itemMeta->class = 'item-meta';
                    $li->appendChild($itemMeta);
                    $itemDescription = new Element('div');
                    $itemDescription->class = 'item-description';
                    $itemDescription->setInnerHTML($page->summary());
                    $li->appendChild($itemDescription);
                    $itemReadMore = new Element('div');
                    $itemReadMore->class = 'item-read-more';
                    $itemReadMoreLink = new Element('a', $readMoreLabel);
                    $itemReadMoreLink->href = $page->url();
                    $itemReadMore->appendChild($itemReadMoreLink);
                    $li->appendChild($itemReadMore);
                    $newNewsList->appendChild($li);
                }
                $newNews->appendChild($newNewsList);

                if ($hasMore) {
                    $moreNews->class = 'more-news-container is-footer-container';
                    $newNews->appendChild($moreNews);
                }

                $news->replace($newNews);
            } catch (\Exception $e) {
                // oh no
                $newNews->class .= ' is-error';
                $news->replace($newNews);
            }
        }
    }
}
