<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

Core::load("Core_Content_ArticleBase");

abstract class CoreContentItem extends CoreContentArticleBase
{
    public $isPublished;

    public $printIconVisible;

    public $emailIconVisible;

    public $editIconVisible;

    public $introVisible;

    public $readmore;

    public $readmoreLink;

    protected function __construct($component, $componentParams, $article, $articleParams)
    {
        parent::__construct($component, $componentParams, $article, $articleParams);
        $this->title = $this->_articleParams->get('show_title') ? $this->_article->title : '';
        $this->showIcons = $this->_articleParams->get('show_icons');
        $this->printIconVisible = $this->_articleParams->get('show_print_icon');
        $this->emailIconVisible = $this->_articleParams->get('show_email_icon');
        $this->editIconVisible = $this->_articleParams->get('access-edit');
        $this->introVisible = $this->_articleParams->get('show_intro');
        $this->images = $this->_buildImages($article, $articleParams);
    }

    private function _buildImages($article, $params) {
        $images = (isset($this->_article->images) && is_string($this->_article->images)) ? json_decode($this->_article->images) : null;
        return array(
            'intro' => $this->_buildImageInfo('intro', $params, $images),
            'fulltext' => $this->_buildImageInfo('fulltext', $params, $images));
    }

    private function _buildImageInfo($type, $params, $images) {
        $image = array('image' => '', 'float' => '', 'class' => '', 'caption' => '', 'alt' => '');
        if (is_null($images))
            return $image;
        $properties = array(
            'image' => 'image_' . $type,
            'float' => 'float_' . $type,
            'caption' => 'image_' . $type . '_caption',
            'alt' => 'image_' . $type . '_alt'
        );
        if (isset($images->{$properties['image']}) && !empty($images->{$properties['image']})) {
            $image['image'] = $images->{$properties['image']};
            if ($image['image'] != '') {
                $image['image'] = preg_match('/http/', $image['image']) ? $image['image'] : Uri::root() . $image['image'];
            }
            $image['float'] = empty($images->{$properties['float']})
                ? $params->get($properties['float'])
                : $images->{$properties['float']};
            $image['class'] = 'img-' . $type . '-' . htmlspecialchars($image['float']);
            if ($images->{$properties['caption']})
                $image['caption'] = htmlspecialchars($images->{$properties['caption']});
            $image['alt'] = $images->{$properties['alt']};
        }
        return $image;
    }

    private function _buildIcon($text, $file, $alt, $wrapUpTooltip = false) {
        $app = Factory::getApplication();
        $src = Uri::root(true) . '/templates/' . $app->getTemplate();
        preg_match('/<a[^>]*>([\s\S]*?)<\/a>/', $text, $matches);
        $linkContent = $matches[1];
        $newLinkContent = '<img src="' . $src . '/images/system/' . $file . '" alt="' . $alt . '" />';
        $text = str_replace($linkContent, $newLinkContent, $text);
        if ($wrapUpTooltip) {
            preg_match('/title="([^"]*)"/', $linkContent, $matches);
            $tooltipText = $matches[1];
            $tooltipText = preg_replace('/<strong>(.*?)<\/strong><br \/>/', '$1 :: ', $tooltipText);
            $text = '<span class="hasTip" title="' . $tooltipText . '">' . $text . '</span>';
        }
        return $text;
    }

    /**
     * @see $emailIconVisible
     */
    public function emailIcon()
    {
        $text = HTMLHelper::_('icon.email', $this->_article, $this->_articleParams);
        if ($this->showIcons) {
            $text = $this->_buildIcon($text, 'emailButton.png', 'Email');
        }
        return $text;
    }

    /**
     * @see $editIconVisible
     */
    public function editIcon()
    {
        $text = HTMLHelper::_('icon.edit', $this->_article, $this->_articleParams);
        if ($this->showIcons) {
            $text = $this->_buildIcon($text, 'edit.png', 'Edit', true);
        }
        return $text;
    }

    /**
     * @see $printIconVisible
     */
    public function printIcon()
    {
        $text = HTMLHelper::_('icon.print_popup', $this->_article, $this->_articleParams);
        if ($this->showIcons) {
            $text = $this->_buildIcon($text, 'printButton.png', 'Print');
        }
        return $text;
    }

    /**
     * Returns decoration for unpublished articles.
     *
     * Together with endUnpublishedArticle() this function decorates
     * the unpublished article with <div class="system-unpublished">...</div>.
     * By default, this decoration is applied only to articles in lists.
     */
    public function beginUnpublishedArticle() { return '<div class="system-unpublished">'; }

    public function endUnpublishedArticle() { return '</div>'; }

    public function readmore($readmore, $readmoreLink)
    {
        return '<p class="readmore">' . funcLinkButton(array(
                'classes' => array('a' => 'readon'),
                'link' => $readmoreLink,
                'content' => str_replace(' ', '&#160;', $readmore))) . '</p>';
    }

    public function image($image) {
        $imgTagAttrs = array('src' => $image['image'], 'alt' => $image['alt'], 'itemprop' => 'image');
        if ($image['caption']) {
            $imgTagAttrs['class'] = 'caption';
            $imgTagAttrs['title'] = $image['caption'];
        }
        return funcTagBuilder('div', array('class' => $image['class']),
            funcTagBuilder('img', array('src' => $image['image'], 'alt' => $image['alt'])
                + ($image['caption'] ? array('class' => 'caption', 'title' => $image['caption']) : array())));
    }
}
